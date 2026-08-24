import { randomUUID } from "node:crypto";
import type { Request, Response } from "express";
import sharp from "sharp";
import { and, asc, eq } from "drizzle-orm";
import { auditLogs, mediaAssets } from "../drizzle/schema";
import { requireStoreRole } from "./access";
import { getDb } from "./db";
import { storageGetSignedUrl, storagePut } from "./storage";
import { sdk } from "./_core/sdk";

const ACCEPTED_MEDIA_TYPES = new Set(["image/jpeg", "image/png", "image/webp", "image/avif"]);
const MAX_MEDIA_BYTES = 20 * 1024 * 1024;
const widths = [480, 960, 1440] as const;

export function isAcceptedMedia(contentType: string, byteLength: number): boolean {
  return ACCEPTED_MEDIA_TYPES.has(contentType) && byteLength > 0 && byteLength <= MAX_MEDIA_BYTES;
}

export function safeMediaFilename(value: string | undefined): string {
  const candidate = (value ?? "image").replace(/[^a-zA-Z0-9._-]/g, "-").replace(/-+/g, "-").replace(/^\.+/, "").slice(0, 120);
  return candidate || "image";
}

export async function uploadOriginalMedia(req: Request, res: Response) {
  try {
    const user = await sdk.authenticateRequest(req);
    const storeId = Number(req.params.storeId);
    const contentType = String(req.header("content-type") ?? "").split(";", 1)[0].toLowerCase();
    const bytes = Buffer.isBuffer(req.body) ? req.body : Buffer.alloc(0);
    if (!Number.isInteger(storeId) || storeId <= 0 || !isAcceptedMedia(contentType, bytes.length)) return res.status(400).json({ error: "unsupported_media" });
    const { db } = await requireStoreRole(storeId, user.id, ["owner", "catalog"]);
    const filename = safeMediaFilename(req.header("x-file-name") ?? undefined);
    const extension = contentType.split("/")[1] ?? "image";
    const stored = await storagePut(`stores/${storeId}/originals/${randomUUID()}-${filename}.${extension}`, bytes, contentType);
    const [created] = await db.insert(mediaAssets).values({ storeId, originalStorageKey: stored.key, originalUrl: stored.url, mimeType: contentType, status: "queued" }).$returningId();
    await db.insert(auditLogs).values({ storeId, actorUserId: user.id, action: "media.original_uploaded", targetType: "media_asset", targetId: String(created?.id ?? ""), outcome: "success", metadata: { mimeType: contentType, size: bytes.length } });
    return res.status(202).json({ mediaId: created?.id ?? null, status: "queued" });
  } catch {
    return res.status(500).json({ error: "media_upload_failed" });
  }
}

type VariantSet = Record<string, { jpeg: string; webp: string; avif: string }>;

export async function processOneQueuedMedia() {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const [asset] = await db.select().from(mediaAssets).where(eq(mediaAssets.status, "queued")).orderBy(asc(mediaAssets.createdAt)).limit(1);
  if (!asset) return { processed: false } as const;
  await db.update(mediaAssets).set({ status: "processing", errorMessage: null }).where(and(eq(mediaAssets.id, asset.id), eq(mediaAssets.status, "queued")));
  try {
    const sourceUrl = await storageGetSignedUrl(asset.originalStorageKey);
    const response = await fetch(sourceUrl);
    if (!response.ok) throw new Error("original_unavailable");
    const original = Buffer.from(await response.arrayBuffer());
    const transformed = sharp(original, { failOn: "error" }).rotate();
    const variants: VariantSet = {};
    for (const width of widths) {
      const base = `stores/${asset.storeId}/variants/${asset.id}-${width}`;
      const resized = transformed.clone().resize({ width, withoutEnlargement: true });
      const [jpeg, webp, avif] = await Promise.all([
        resized.clone().jpeg({ quality: 82, mozjpeg: true }).toBuffer(),
        resized.clone().webp({ quality: 80 }).toBuffer(),
        resized.clone().avif({ quality: 55 }).toBuffer(),
      ]);
      const [jpegStored, webpStored, avifStored] = await Promise.all([
        storagePut(`${base}.jpg`, jpeg, "image/jpeg"),
        storagePut(`${base}.webp`, webp, "image/webp"),
        storagePut(`${base}.avif`, avif, "image/avif"),
      ]);
      variants[String(width)] = { jpeg: jpegStored.url, webp: webpStored.url, avif: avifStored.url };
    }
    await db.transaction(async (tx) => {
      await tx.update(mediaAssets).set({ status: "ready", variants, errorMessage: null }).where(eq(mediaAssets.id, asset.id));
      await tx.insert(auditLogs).values({ storeId: asset.storeId, action: "media.variants_ready", targetType: "media_asset", targetId: String(asset.id), outcome: "success", metadata: { widths: [...widths] } });
    });
    return { processed: true, mediaId: asset.id, status: "ready" as const };
  } catch {
    await db.transaction(async (tx) => {
      await tx.update(mediaAssets).set({ status: "failed", errorMessage: "Le traitement des variantes a échoué ; l’original reste disponible comme fallback." }).where(eq(mediaAssets.id, asset.id));
      await tx.insert(auditLogs).values({ storeId: asset.storeId, action: "media.processing_failed", targetType: "media_asset", targetId: String(asset.id), outcome: "failed" });
    });
    return { processed: true, mediaId: asset.id, status: "failed" as const };
  }
}

export async function processQueuedMediaRequest(req: Request, res: Response) {
  try {
    const user = await sdk.authenticateRequest(req);
    if (!user.isCron || !user.taskUid) return res.status(403).json({ error: "cron_only" });
    return res.status(200).json({ ok: true, ...(await processOneQueuedMedia()) });
  } catch (error) {
    return res.status(500).json({ error: "media_worker_failed", detail: error instanceof Error ? error.message : "unknown", timestamp: new Date().toISOString() });
  }
}
