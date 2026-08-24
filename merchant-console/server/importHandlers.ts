import type { Request, Response } from "express";
import { auditLogs, importJobs, importRows } from "../drizzle/schema";
import { requireStoreRole } from "./access";
import { validateCatalogCsv, importObjectKey } from "./imports";
import { storagePut } from "./storage";
import { sdk } from "./_core/sdk";

const MAX_CSV_BYTES = 5 * 1024 * 1024;

export async function uploadCatalogImport(req: Request, res: Response) {
  try {
    const user = await sdk.authenticateRequest(req);
    const storeId = Number(req.params.storeId);
    const contentType = String(req.header("content-type") ?? "").split(";", 1)[0].toLowerCase();
    const source = Buffer.isBuffer(req.body) ? req.body : Buffer.alloc(0);
    if (!Number.isInteger(storeId) || storeId <= 0 || source.length === 0 || source.length > MAX_CSV_BYTES || !["text/csv", "application/csv"].includes(contentType)) return res.status(400).json({ error: "invalid_csv_upload" });
    const { db } = await requireStoreRole(storeId, user.id, ["owner", "catalog"]);
    const validation = validateCatalogCsv(source.toString("utf8"));
    const stored = await storagePut(importObjectKey(storeId), source, "text/csv");
    const invalid = validation.filter((row) => row.status === "invalid");
    const [job] = await db.insert(importJobs).values({ storeId, initiatedByUserId: user.id, sourceStorageKey: stored.key, mode: "validate", status: invalid.length ? "invalid" : "ready", errorCount: invalid.length }).$returningId();
    if (!job) throw new Error("Import job insert failed");
    if (validation.length > 0) {
      await db.insert(importRows).values(validation.map((row) => ({ importJobId: job.id, rowNumber: row.rowNumber, status: row.status, errorCode: row.errorCode ?? null, errorMessage: row.errorMessage ?? null, normalizedPayload: row.normalizedPayload ?? null })));
    }
    await db.insert(auditLogs).values({ storeId, actorUserId: user.id, action: "import.csv_validated", targetType: "import_job", targetId: String(job.id), outcome: invalid.length ? "rejected" : "success", metadata: { rows: validation.length, invalidRows: invalid.length } });
    return res.status(202).json({ importJobId: job.id, status: invalid.length ? "invalid" : "ready", errorCount: invalid.length });
  } catch (error) {
    return res.status(500).json({ error: "import_validation_failed", detail: error instanceof Error && error.message.startsWith("Colonne") ? error.message : undefined });
  }
}
