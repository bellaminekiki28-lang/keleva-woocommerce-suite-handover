import { TRPCError } from "@trpc/server";
import { desc, eq } from "drizzle-orm";
import { z } from "zod";
import { syncRuns, webhookEvents } from "../../drizzle/schema";
import { requireStoreRole } from "../access";
import { enforceRateLimit } from "../rateLimit";
import { enqueueSync } from "../sync";
import { enqueueWebhookReplay } from "../webhooks";
import { protectedProcedure, router } from "../_core/trpc";

const storeId = z.object({ storeId: z.number().int().positive() });

export const syncRouter = router({
  list: protectedProcedure.input(storeId).query(async ({ ctx, input }) => {
    const { db } = await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog", "viewer"]);
    return db.select().from(syncRuns).where(eq(syncRuns.storeId, input.storeId)).orderBy(desc(syncRuns.createdAt)).limit(50);
  }),
  enqueue: protectedProcedure.input(storeId.extend({ kind: z.enum(["full", "products", "orders", "stock", "media"]) })).mutation(async ({ ctx, input }) => {
    await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog"]);
    await enforceRateLimit(`user:${ctx.user.id}:store:${input.storeId}`, "sync.enqueue", 6);
    const run = await enqueueSync(input.storeId, ctx.user.id, input.kind);
    return { runId: run?.id ?? null, queued: true };
  }),
  webhooks: protectedProcedure.input(storeId).query(async ({ ctx, input }) => {
    const { db } = await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog", "viewer"]);
    return db.select({ deliveryId: webhookEvents.deliveryId, topic: webhookEvents.topic, resourceId: webhookEvents.resourceId, status: webhookEvents.status, signatureVerified: webhookEvents.signatureVerified, processingError: webhookEvents.processingError, receivedAt: webhookEvents.receivedAt }).from(webhookEvents).where(eq(webhookEvents.storeId, input.storeId)).orderBy(desc(webhookEvents.receivedAt)).limit(50);
  }),
  replay: protectedProcedure.input(storeId.extend({ deliveryId: z.string().min(1).max(128), confirm: z.literal(true) })).mutation(async ({ ctx, input }) => {
    await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator"]);
    await enforceRateLimit(`user:${ctx.user.id}:store:${input.storeId}`, "webhook.replay", 5);
    try {
      const run = await enqueueWebhookReplay(input.storeId, ctx.user.id, input.deliveryId);
      return { queued: true, runId: run?.id ?? null };
    } catch {
      throw new TRPCError({ code: "NOT_FOUND", message: "Cette livraison webhook n’est pas disponible pour une reprise contrôlée." });
    }
  }),
});
