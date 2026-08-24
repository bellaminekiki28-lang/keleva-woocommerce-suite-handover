import { desc, eq } from "drizzle-orm";
import { z } from "zod";
import { auditLogs, mediaAssets } from "../../drizzle/schema";
import { requireStoreRole } from "../access";
import { enforceRateLimit } from "../rateLimit";
import { protectedProcedure, router } from "../_core/trpc";

const storeId = z.object({ storeId: z.number().int().positive() });

export const mediaRouter = router({
  list: protectedProcedure.input(storeId).query(async ({ ctx, input }) => {
    const { db } = await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog", "viewer"]);
    return db.select().from(mediaAssets).where(eq(mediaAssets.storeId, input.storeId)).orderBy(desc(mediaAssets.createdAt)).limit(100);
  }),
  retry: protectedProcedure.input(storeId.extend({ mediaId: z.number().int().positive(), confirm: z.literal(true) })).mutation(async ({ ctx, input }) => {
    const { db } = await requireStoreRole(input.storeId, ctx.user.id, ["owner", "catalog"]);
    await enforceRateLimit(`user:${ctx.user.id}:store:${input.storeId}`, "media.retry", 8);
    const [asset] = await db.select().from(mediaAssets).where(eq(mediaAssets.id, input.mediaId)).limit(1);
    if (!asset || asset.storeId !== input.storeId || asset.status !== "failed") throw new Error("Cette ressource média n’est pas disponible pour une reprise.");
    await db.transaction(async (tx) => {
      await tx.update(mediaAssets).set({ status: "queued", errorMessage: null, retryCount: asset.retryCount + 1 }).where(eq(mediaAssets.id, input.mediaId));
      await tx.insert(auditLogs).values({ storeId: input.storeId, actorUserId: ctx.user.id, action: "media.retry_requested", targetType: "media_asset", targetId: String(input.mediaId), outcome: "success" });
    });
    return { queued: true } as const;
  }),
});
