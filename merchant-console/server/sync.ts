import { randomUUID } from "node:crypto";
import type { Request, Response } from "express";
import { and, asc, eq } from "drizzle-orm";
import { auditLogs, stores, syncRuns } from "../drizzle/schema";
import { getDb } from "./db";
import { sdk } from "./_core/sdk";
import { listWooOrders, listWooProducts } from "./woocommerceClient";

export type SyncKind = "full" | "products" | "orders" | "stock" | "media";

export function canTransitionSync(from: string, to: string): boolean {
  return (from === "queued" && (to === "running" || to === "cancelled")) || (from === "running" && (to === "completed" || to === "failed"));
}

export async function enqueueSync(storeId: number, actorUserId: number, kind: SyncKind, replayKey?: string) {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const idempotencyKey = replayKey ?? `manual:${storeId}:${kind}:${randomUUID()}`;
  const [existing] = await db.select().from(syncRuns).where(eq(syncRuns.idempotencyKey, idempotencyKey)).limit(1);
  if (existing) return existing;
  const [created] = await db.insert(syncRuns).values({ storeId, kind, requestedByUserId: actorUserId, idempotencyKey, status: "queued" }).$returningId();
  await db.insert(auditLogs).values({ storeId, actorUserId, action: "sync.enqueued", targetType: "sync_run", targetId: String(created?.id ?? ""), outcome: "success", metadata: { kind, idempotencyKey } });
  return created;
}

export async function processOneQueuedSync() {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const [run] = await db.select().from(syncRuns).where(eq(syncRuns.status, "queued")).orderBy(asc(syncRuns.createdAt)).limit(1);
  if (!run) return { processed: false } as const;
  await db.update(syncRuns).set({ status: "running", startedAt: new Date(), progress: 5 }).where(and(eq(syncRuns.id, run.id), eq(syncRuns.status, "queued")));
  try {
    if (run.kind === "orders") await listWooOrders(run.storeId, 1);
    else if (run.kind === "products" || run.kind === "stock" || run.kind === "media") await listWooProducts(run.storeId, 1);
    else {
      await Promise.all([listWooOrders(run.storeId, 1), listWooProducts(run.storeId, 1)]);
    }
    await db.transaction(async (tx) => {
      await tx.update(syncRuns).set({ status: "completed", progress: 100, completedAt: new Date(), errorMessage: null }).where(eq(syncRuns.id, run.id));
      await tx.update(stores).set({ lastSyncedAt: new Date(), lastSyncError: null }).where(eq(stores.id, run.storeId));
      await tx.insert(auditLogs).values({ storeId: run.storeId, actorUserId: run.requestedByUserId, action: "sync.completed", targetType: "sync_run", targetId: String(run.id), outcome: "success", metadata: { kind: run.kind } });
    });
    return { processed: true, runId: run.id, status: "completed" as const };
  } catch (error) {
    const message = error instanceof Error && error.message.startsWith("WooCommerce") ? "WooCommerce a refusé la synchronisation." : "La synchronisation a échoué sans exposer les identifiants.";
    await db.transaction(async (tx) => {
      await tx.update(syncRuns).set({ status: "failed", completedAt: new Date(), errorMessage: message }).where(eq(syncRuns.id, run.id));
      await tx.update(stores).set({ lastSyncError: message }).where(eq(stores.id, run.storeId));
      await tx.insert(auditLogs).values({ storeId: run.storeId, actorUserId: run.requestedByUserId, action: "sync.failed", targetType: "sync_run", targetId: String(run.id), outcome: "failed", reason: message, metadata: { kind: run.kind } });
    });
    return { processed: true, runId: run.id, status: "failed" as const };
  }
}

export async function processQueuedSyncRequest(req: Request, res: Response) {
  try {
    const user = await sdk.authenticateRequest(req);
    if (!user.isCron || !user.taskUid) return res.status(403).json({ error: "cron_only" });
    const result = await processOneQueuedSync();
    return res.status(200).json({ ok: true, ...result });
  } catch (error) {
    return res.status(500).json({ error: "sync_worker_failed", detail: error instanceof Error ? error.message : "unknown", timestamp: new Date().toISOString() });
  }
}
