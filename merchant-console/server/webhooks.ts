import { createHash, createHmac, timingSafeEqual } from "node:crypto";
import type { Request, Response } from "express";
import { and, eq } from "drizzle-orm";
import { auditLogs, storeConnections, syncRuns, webhookEvents } from "../drizzle/schema";
import { getDb } from "./db";
import { decryptConnectionSecret } from "./security";

export function isValidWebhookSignature(rawBody: Buffer, secret: string, received: string | undefined): boolean {
  if (!received) return false;
  const expected = createHmac("sha256", secret).update(rawBody).digest("base64");
  const expectedBuffer = Buffer.from(expected);
  const receivedBuffer = Buffer.from(received);
  return expectedBuffer.length === receivedBuffer.length && timingSafeEqual(expectedBuffer, receivedBuffer);
}

function idempotencyKey(storeId: number, deliveryId: string) {
  return `webhook:${storeId}:${deliveryId}`;
}

export async function receiveWooWebhook(req: Request, res: Response) {
  const storeId = Number(req.params.storeId);
  const rawBody = Buffer.isBuffer(req.body) ? req.body : Buffer.alloc(0);
  const deliveryId = String(req.header("x-wc-webhook-delivery-id") ?? "").slice(0, 128);
  const topic = String(req.header("x-wc-webhook-topic") ?? "unknown").slice(0, 160);
  if (!Number.isInteger(storeId) || storeId <= 0 || rawBody.length === 0 || !deliveryId) return res.status(400).json({ error: "invalid_webhook_request" });

  const db = await getDb();
  if (!db) return res.status(503).json({ error: "database_unavailable" });
  const [connection] = await db.select().from(storeConnections).where(and(eq(storeConnections.storeId, storeId), eq(storeConnections.isRevoked, false))).limit(1);
  if (!connection) return res.status(404).json({ error: "unknown_store" });

  const secret = decryptConnectionSecret(connection.encryptedWebhookSecret);
  if (!isValidWebhookSignature(rawBody, secret, req.header("x-wc-webhook-signature") ?? undefined)) {
    await db.insert(auditLogs).values({ storeId, action: "webhook.rejected", targetType: "webhook", targetId: deliveryId, outcome: "rejected", reason: "invalid_signature", metadata: { topic } });
    return res.status(401).json({ error: "invalid_signature" });
  }

  const digest = createHash("sha256").update(rawBody).digest("hex");
  let resourceId: string | null = null;
  try {
    const payload = JSON.parse(rawBody.toString("utf8")) as { id?: string | number };
    resourceId = payload.id === undefined ? null : String(payload.id).slice(0, 128);
  } catch {
    return res.status(400).json({ error: "invalid_json" });
  }

  const [existing] = await db.select({ id: webhookEvents.id }).from(webhookEvents).where(and(eq(webhookEvents.storeId, storeId), eq(webhookEvents.deliveryId, deliveryId))).limit(1);
  if (existing) return res.status(200).json({ ok: true, duplicate: true });

  try {
    await db.transaction(async (tx) => {
      await tx.insert(webhookEvents).values({ storeId, deliveryId, topic, resourceId, signatureVerified: true, status: "processed", payloadDigest: digest, processedAt: new Date() });
      await tx.insert(auditLogs).values({ storeId, action: "webhook.processed", targetType: "webhook", targetId: deliveryId, outcome: "success", metadata: { topic, resourceId, digest } });
    });
    return res.status(202).json({ ok: true, idempotencyKey: idempotencyKey(storeId, deliveryId) });
  } catch (error) {
    const message = error instanceof Error ? error.message : "unknown";
    if (message.toLowerCase().includes("duplicate")) return res.status(200).json({ ok: true, duplicate: true });
    return res.status(500).json({ error: "webhook_processing_failed" });
  }
}

export async function enqueueWebhookReplay(storeId: number, actorUserId: number, deliveryId: string) {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const [event] = await db.select().from(webhookEvents).where(and(eq(webhookEvents.storeId, storeId), eq(webhookEvents.deliveryId, deliveryId))).limit(1);
  if (!event) throw new Error("Webhook event not found");
  const key = `replay:${storeId}:${deliveryId}`;
  const [existing] = await db.select().from(syncRuns).where(eq(syncRuns.idempotencyKey, key)).limit(1);
  if (existing) return existing;
  const [created] = await db.insert(syncRuns).values({ storeId, kind: event.topic.startsWith("order.") ? "orders" : "products", status: "queued", requestedByUserId: actorUserId, idempotencyKey: key }).$returningId();
  await db.insert(auditLogs).values({ storeId, actorUserId, action: "webhook.replay_requested", targetType: "webhook", targetId: deliveryId, outcome: "success", metadata: { syncRunId: created?.id ?? null } });
  return created;
}
