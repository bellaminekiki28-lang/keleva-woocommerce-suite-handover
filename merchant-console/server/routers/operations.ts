import { TRPCError } from "@trpc/server";
import { z } from "zod";
import { auditLogs } from "../../drizzle/schema";
import { requireStoreRole } from "../access";
import { enforceRateLimit } from "../rateLimit";
import { listWooOrders, listWooProducts, updateWooProduct } from "../woocommerceClient";
import { protectedProcedure, router } from "../_core/trpc";

const storeInput = z.object({ storeId: z.number().int().positive(), limit: z.number().int().min(1).max(50).default(20) });
const confirmation = z.object({ storeId: z.number().int().positive(), productId: z.number().int().positive(), confirm: z.literal(true), reason: z.string().trim().min(3).max(500).optional() });

function safeWooFailure(error: unknown): never {
  throw new TRPCError({ code: "BAD_GATEWAY", message: error instanceof Error && error.message.startsWith("WooCommerce request failed") ? "WooCommerce a rejeté l’opération. Vérifiez l’état du magasin dans la console." : "L’opération WooCommerce a échoué sans exposer les données de connexion." });
}

export const operationsRouter = router({
  orders: protectedProcedure.input(storeInput).query(async ({ ctx, input }) => {
    await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog", "viewer"]);
    try { return await listWooOrders(input.storeId, input.limit); } catch (error) { return safeWooFailure(error); }
  }),
  products: protectedProcedure.input(storeInput).query(async ({ ctx, input }) => {
    await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog", "viewer"]);
    try { return await listWooProducts(input.storeId, input.limit); } catch (error) { return safeWooFailure(error); }
  }),
  setStock: protectedProcedure.input(confirmation.extend({ quantity: z.number().int().min(0).max(1_000_000) })).mutation(async ({ ctx, input }) => {
    const { db } = await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog"]);
    await enforceRateLimit(`user:${ctx.user.id}:store:${input.storeId}`, "stock.update", 20);
    try {
      const product = await updateWooProduct(input.storeId, input.productId, { manage_stock: true, stock_quantity: input.quantity });
      await db.insert(auditLogs).values({ storeId: input.storeId, actorUserId: ctx.user.id, action: "product.stock_updated", targetType: "product", targetId: String(input.productId), outcome: "success", reason: input.reason, metadata: { quantity: input.quantity } });
      return product;
    } catch (error) {
      await db.insert(auditLogs).values({ storeId: input.storeId, actorUserId: ctx.user.id, action: "product.stock_updated", targetType: "product", targetId: String(input.productId), outcome: "failed", reason: input.reason });
      return safeWooFailure(error);
    }
  }),
  setProductStatus: protectedProcedure.input(confirmation.extend({ status: z.enum(["publish", "draft", "private"]) })).mutation(async ({ ctx, input }) => {
    const { db } = await requireStoreRole(input.storeId, ctx.user.id, ["owner", "catalog"]);
    await enforceRateLimit(`user:${ctx.user.id}:store:${input.storeId}`, "product.status", 10);
    try {
      const product = await updateWooProduct(input.storeId, input.productId, { status: input.status });
      await db.insert(auditLogs).values({ storeId: input.storeId, actorUserId: ctx.user.id, action: "product.status_updated", targetType: "product", targetId: String(input.productId), outcome: "success", reason: input.reason, metadata: { status: input.status } });
      return product;
    } catch (error) {
      await db.insert(auditLogs).values({ storeId: input.storeId, actorUserId: ctx.user.id, action: "product.status_updated", targetType: "product", targetId: String(input.productId), outcome: "failed", reason: input.reason });
      return safeWooFailure(error);
    }
  }),
});
