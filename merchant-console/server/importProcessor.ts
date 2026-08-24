import type { Request, Response } from "express";
import { and, asc, eq, or } from "drizzle-orm";
import { auditLogs, importJobs, importRows } from "../drizzle/schema";
import { getDb } from "./db";
import { sdk } from "./_core/sdk";
import { createWooProduct, deleteWooProduct, findWooProductBySku, type WooProduct, updateWooProduct } from "./woocommerceClient";

type CatalogPayload = { sku: string; name: string; regular_price: string; stock_quantity?: number; manage_stock?: boolean; stock_status?: "instock" | "outofstock"; status?: "publish" | "draft" | "private" };
type SnapshotEntry = { rowNumber: number; action: "created" | "updated"; productId: number; previous?: CatalogPayload };
export type ImportSnapshot = { version: 1; entries: SnapshotEntry[] };
type ImportJob = typeof importJobs.$inferSelect;

function didUpdate(result: unknown): boolean {
  const header = Array.isArray(result) ? result[0] : result;
  if (typeof header !== "object" || header === null) return false;
  return Number((header as { affectedRows?: unknown }).affectedRows) === 1;
}

export function catalogPayloadFromRow(row: Record<string, unknown>): CatalogPayload {
  const payload: CatalogPayload = { sku: String(row.sku ?? "").trim(), name: String(row.name ?? "").trim(), regular_price: String(row.regular_price ?? "").trim() };
  if (row.status) payload.status = String(row.status) as CatalogPayload["status"];
  if (row.stock_quantity !== undefined && String(row.stock_quantity).trim() !== "") {
    const quantity = Number(row.stock_quantity);
    payload.stock_quantity = quantity;
    payload.manage_stock = true;
    payload.stock_status = quantity > 0 ? "instock" : "outofstock";
  }
  return payload;
}

export function rollbackPayload(product: WooProduct): CatalogPayload {
  return { sku: product.sku, name: product.name, regular_price: product.regular_price, status: product.status as CatalogPayload["status"], stock_quantity: product.stock_quantity ?? undefined, manage_stock: Boolean(product.manage_stock), stock_status: product.stock_status === "outofstock" ? "outofstock" : "instock" };
}

export async function queueImportApply(storeId: number, importJobId: number, actorUserId: number) {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const [job] = await db.select().from(importJobs).where(and(eq(importJobs.id, importJobId), eq(importJobs.storeId, storeId))).limit(1);
  if (!job) throw new Error("Import is unavailable");
  const update = await db.update(importJobs).set({ mode: "apply", status: "ready", errorCount: 0, appliedCount: 0, rollbackSnapshot: { version: 1, entries: [] } satisfies ImportSnapshot }).where(and(eq(importJobs.id, job.id), eq(importJobs.status, "ready"), eq(importJobs.mode, "validate")));
  if (!didUpdate(update)) throw new Error("Import is not ready to apply");
  await db.insert(auditLogs).values({ storeId, actorUserId, action: "import.apply_queued", targetType: "import_job", targetId: String(job.id), outcome: "success", metadata: { importJobId: job.id } });
  return { importJobId: job.id, queued: true };
}

export async function queueImportRollback(storeId: number, importJobId: number, actorUserId: number) {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const [job] = await db.select().from(importJobs).where(and(eq(importJobs.id, importJobId), eq(importJobs.storeId, storeId))).limit(1);
  if (!job || !job.rollbackSnapshot) throw new Error("Import rollback is unavailable");
  const update = await db.update(importJobs).set({ mode: "rollback", status: "ready" }).where(and(eq(importJobs.id, job.id), eq(importJobs.mode, "apply"), or(eq(importJobs.status, "completed"), eq(importJobs.status, "failed"))));
  if (!didUpdate(update)) throw new Error("Import rollback is unavailable");
  await db.insert(auditLogs).values({ storeId, actorUserId, action: "import.rollback_queued", targetType: "import_job", targetId: String(job.id), outcome: "success", metadata: { importJobId: job.id } });
  return { importJobId: job.id, queued: true };
}

async function rollbackSnapshot(storeId: number, importJobId: number, snapshot: ImportSnapshot) {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  for (const entry of [...snapshot.entries].reverse()) {
    const [row] = await db.select({ status: importRows.status }).from(importRows).where(and(eq(importRows.importJobId, importJobId), eq(importRows.rowNumber, entry.rowNumber))).limit(1);
    if (row?.status === "rolled_back") continue;
    if (entry.action === "created") await deleteWooProduct(storeId, entry.productId);
    else if (entry.previous) await updateWooProduct(storeId, entry.productId, entry.previous);
    await db.update(importRows).set({ status: "rolled_back" }).where(and(eq(importRows.importJobId, importJobId), eq(importRows.rowNumber, entry.rowNumber)));
  }
}

async function applyImport(job: ImportJob) {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const rows = await db.select().from(importRows).where(and(eq(importRows.importJobId, job.id), eq(importRows.status, "valid"))).orderBy(asc(importRows.rowNumber));
  const snapshot: ImportSnapshot = { version: 1, entries: [] };
  let activeRow: typeof importRows.$inferSelect | undefined;
  try {
    for (const row of rows) {
      activeRow = row;
      const payload = catalogPayloadFromRow((row.normalizedPayload ?? {}) as Record<string, unknown>);
      const existing = await findWooProductBySku(job.storeId, payload.sku);
      const mutated = existing ? await updateWooProduct(job.storeId, existing.id, payload) : await createWooProduct(job.storeId, payload);
      snapshot.entries.push({ rowNumber: row.rowNumber, action: existing ? "updated" : "created", productId: mutated.id, previous: existing ? rollbackPayload(existing) : undefined });
      await db.update(importJobs).set({ rollbackSnapshot: snapshot, appliedCount: snapshot.entries.length }).where(eq(importJobs.id, job.id));
      await db.update(importRows).set({ status: "applied", errorCode: null, errorMessage: null }).where(eq(importRows.id, row.id));
      activeRow = undefined;
    }
    await db.update(importJobs).set({ status: "completed", mode: "apply", rollbackSnapshot: snapshot, errorCount: 0 }).where(eq(importJobs.id, job.id));
    await db.insert(auditLogs).values({ storeId: job.storeId, actorUserId: job.initiatedByUserId, action: "import.applied", targetType: "import_job", targetId: String(job.id), outcome: "success", metadata: { rows: snapshot.entries.length } });
    return { importJobId: job.id, status: "completed" as const };
  } catch (error) {
    if (activeRow) await db.update(importRows).set({ status: "invalid", errorCode: "apply_failed", errorMessage: "La ligne n’a pas pu être appliquée." }).where(eq(importRows.id, activeRow.id));
    try { await rollbackSnapshot(job.storeId, job.id, snapshot); } catch { /* Preserve the original failure without exposing connection information. */ }
    const message = "L’import a échoué ; les mutations déjà appliquées ont reçu une tentative de rollback contrôlé.";
    await db.update(importJobs).set({ status: "failed", errorCount: 1, rollbackSnapshot: snapshot }).where(eq(importJobs.id, job.id));
    await db.insert(auditLogs).values({ storeId: job.storeId, actorUserId: job.initiatedByUserId, action: "import.failed", targetType: "import_job", targetId: String(job.id), outcome: "failed", reason: message, metadata: { appliedBeforeFailure: snapshot.entries.length } });
    throw error;
  }
}

async function rollbackImport(job: ImportJob) {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const snapshot = job.rollbackSnapshot as ImportSnapshot | null;
  if (!snapshot || snapshot.version !== 1) throw new Error("Import rollback snapshot unavailable");
  try {
    await rollbackSnapshot(job.storeId, job.id, snapshot);
    await db.update(importJobs).set({ status: "rolled_back", mode: "rollback" }).where(eq(importJobs.id, job.id));
    await db.insert(auditLogs).values({ storeId: job.storeId, actorUserId: job.initiatedByUserId, action: "import.rolled_back", targetType: "import_job", targetId: String(job.id), outcome: "success", metadata: { rows: snapshot.entries.length } });
    return { importJobId: job.id, status: "rolled_back" as const };
  } catch (error) {
    const message = "Le rollback n’a pas pu être finalisé ; une nouvelle tentative contrôlée reste possible.";
    await db.update(importJobs).set({ status: "failed", mode: "apply" }).where(eq(importJobs.id, job.id));
    await db.insert(auditLogs).values({ storeId: job.storeId, actorUserId: job.initiatedByUserId, action: "import.rollback_failed", targetType: "import_job", targetId: String(job.id), outcome: "failed", reason: message, metadata: { rows: snapshot.entries.length } });
    throw error;
  }
}

async function claimReadyImport(job: ImportJob): Promise<ImportJob | undefined> {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const claim = await db.update(importJobs).set({ status: "applying" }).where(and(eq(importJobs.id, job.id), eq(importJobs.status, "ready"), eq(importJobs.mode, job.mode)));
  if (!didUpdate(claim)) return undefined;
  const [claimed] = await db.select().from(importJobs).where(and(eq(importJobs.id, job.id), eq(importJobs.status, "applying"), eq(importJobs.mode, job.mode))).limit(1);
  return claimed;
}

export async function processOneQueuedImport() {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const [applyJob] = await db.select().from(importJobs).where(and(eq(importJobs.status, "ready"), eq(importJobs.mode, "apply"))).orderBy(asc(importJobs.createdAt)).limit(1);
  const [rollbackJob] = applyJob ? [undefined] : await db.select().from(importJobs).where(and(eq(importJobs.status, "ready"), eq(importJobs.mode, "rollback"))).orderBy(asc(importJobs.createdAt)).limit(1);
  const next = applyJob ?? rollbackJob;
  if (!next) return { processed: false } as const;
  const claimed = await claimReadyImport(next);
  if (!claimed) return { processed: false, skipped: "claimed_elsewhere" } as const;
  try {
    return claimed.mode === "rollback" ? await rollbackImport(claimed) : await applyImport(claimed);
  } catch {
    return { importJobId: claimed.id, status: "failed" as const };
  }
}

export async function processQueuedImportRequest(req: Request, res: Response) {
  try {
    const user = await sdk.authenticateRequest(req);
    if (!user.isCron || !user.taskUid) return res.status(403).json({ error: "cron_only" });
    return res.status(200).json({ ok: true, ...(await processOneQueuedImport()) });
  } catch (error) {
    console.error("[imports] scheduled worker failed", error);
    return res.status(500).json({ error: "import_worker_failed", message: "Le worker d’import a échoué ; consulter les journaux sécurisés.", timestamp: new Date().toISOString() });
  }
}
