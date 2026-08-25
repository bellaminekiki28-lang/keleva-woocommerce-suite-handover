import { TRPCError } from "@trpc/server";
import { z } from "zod";
import { auditLogs } from "../../drizzle/schema";
import { requireStoreRole } from "../access";
import { enforceRateLimit } from "../rateLimit";
import {
  createWooProduct,
  listWooOrders,
  listWooProducts,
  updateWooOrder,
  updateWooProduct,
} from "../woocommerceClient";
import { protectedProcedure, router } from "../_core/trpc";

const storeInput = z.object({
  storeId: z.number().int().positive(),
  limit: z.number().int().min(1).max(50).default(20),
});
const confirmation = z.object({
  storeId: z.number().int().positive(),
  productId: z.number().int().positive(),
  confirm: z.literal(true),
  reason: z.string().trim().min(3).max(500).optional(),
});
export const productInput = z.object({
  storeId: z.number().int().positive(),
  name: z.string().trim().min(2).max(180),
  regularPrice: z
    .string()
    .trim()
    .regex(/^\d+(?:[.,]\d{1,2})?$/),
  stockQuantity: z.number().int().min(0).max(1_000_000).default(0),
  publish: z.boolean().default(false),
  confirm: z.literal(true),
  reason: z.string().trim().min(3).max(500).optional(),
});
export const orderStatusInput = z.object({
  storeId: z.number().int().positive(),
  orderId: z.number().int().positive(),
  status: z.enum([
    "pending",
    "on-hold",
    "processing",
    "completed",
    "cancelled",
  ]),
  confirm: z.literal(true),
  reason: z.string().trim().min(3).max(500).optional(),
});

function safeWooFailure(error: unknown): never {
  throw new TRPCError({
    code: "BAD_GATEWAY",
    message:
      error instanceof Error &&
      error.message.startsWith("WooCommerce request failed")
        ? "WooCommerce a rejeté l’opération. Vérifiez l’état du magasin dans la console."
        : "L’opération WooCommerce a échoué sans exposer les données de connexion.",
  });
}

export const operationsRouter = router({
  createProduct: protectedProcedure
    .input(productInput)
    .mutation(async ({ ctx, input }) => {
      const { db } = await requireStoreRole(input.storeId, ctx.user.id, [
        "owner",
        "catalog",
      ]);
      await enforceRateLimit(
        `user:${ctx.user.id}:store:${input.storeId}`,
        "product.create",
        10
      );
      const regularPrice = input.regularPrice.replace(",", ".");
      try {
        const product = await createWooProduct(input.storeId, {
          name: input.name,
          type: "simple",
          status: input.publish ? "publish" : "draft",
          regular_price: regularPrice,
          manage_stock: true,
          stock_quantity: input.stockQuantity,
        });
        await db.insert(auditLogs).values({
          storeId: input.storeId,
          actorUserId: ctx.user.id,
          action: "product.created",
          targetType: "product",
          targetId: String(product.id),
          outcome: "success",
          reason: input.reason,
          metadata: {
            status: product.status,
            stockQuantity: input.stockQuantity,
          },
        });
        return product;
      } catch (error) {
        await db.insert(auditLogs).values({
          storeId: input.storeId,
          actorUserId: ctx.user.id,
          action: "product.created",
          targetType: "product",
          targetId: "pending",
          outcome: "failed",
          reason: input.reason,
        });
        return safeWooFailure(error);
      }
    }),
  orders: protectedProcedure.input(storeInput).query(async ({ ctx, input }) => {
    await requireStoreRole(input.storeId, ctx.user.id, [
      "owner",
      "operator",
      "catalog",
      "viewer",
    ]);
    try {
      return await listWooOrders(input.storeId, input.limit);
    } catch (error) {
      return safeWooFailure(error);
    }
  }),
  setOrderStatus: protectedProcedure
    .input(orderStatusInput)
    .mutation(async ({ ctx, input }) => {
      const { db } = await requireStoreRole(input.storeId, ctx.user.id, [
        "owner",
        "operator",
      ]);
      await enforceRateLimit(
        `user:${ctx.user.id}:store:${input.storeId}`,
        "order.status",
        20
      );
      try {
        const order = await updateWooOrder(
          input.storeId,
          input.orderId,
          input.status
        );
        await db.insert(auditLogs).values({
          storeId: input.storeId,
          actorUserId: ctx.user.id,
          action: "order.status_updated",
          targetType: "order",
          targetId: String(input.orderId),
          outcome: "success",
          reason: input.reason,
          metadata: { status: input.status },
        });
        return order;
      } catch (error) {
        await db.insert(auditLogs).values({
          storeId: input.storeId,
          actorUserId: ctx.user.id,
          action: "order.status_updated",
          targetType: "order",
          targetId: String(input.orderId),
          outcome: "failed",
          reason: input.reason,
          metadata: { status: input.status },
        });
        return safeWooFailure(error);
      }
    }),
  products: protectedProcedure
    .input(storeInput)
    .query(async ({ ctx, input }) => {
      await requireStoreRole(input.storeId, ctx.user.id, [
        "owner",
        "operator",
        "catalog",
        "viewer",
      ]);
      try {
        return await listWooProducts(input.storeId, input.limit);
      } catch (error) {
        return safeWooFailure(error);
      }
    }),
  setStock: protectedProcedure
    .input(
      confirmation.extend({ quantity: z.number().int().min(0).max(1_000_000) })
    )
    .mutation(async ({ ctx, input }) => {
      const { db } = await requireStoreRole(input.storeId, ctx.user.id, [
        "owner",
        "operator",
        "catalog",
      ]);
      await enforceRateLimit(
        `user:${ctx.user.id}:store:${input.storeId}`,
        "stock.update",
        20
      );
      try {
        const product = await updateWooProduct(input.storeId, input.productId, {
          manage_stock: true,
          stock_quantity: input.quantity,
        });
        await db.insert(auditLogs).values({
          storeId: input.storeId,
          actorUserId: ctx.user.id,
          action: "product.stock_updated",
          targetType: "product",
          targetId: String(input.productId),
          outcome: "success",
          reason: input.reason,
          metadata: { quantity: input.quantity },
        });
        return product;
      } catch (error) {
        await db.insert(auditLogs).values({
          storeId: input.storeId,
          actorUserId: ctx.user.id,
          action: "product.stock_updated",
          targetType: "product",
          targetId: String(input.productId),
          outcome: "failed",
          reason: input.reason,
        });
        return safeWooFailure(error);
      }
    }),
  setProductStatus: protectedProcedure
    .input(
      confirmation.extend({ status: z.enum(["publish", "draft", "private"]) })
    )
    .mutation(async ({ ctx, input }) => {
      const { db } = await requireStoreRole(input.storeId, ctx.user.id, [
        "owner",
        "catalog",
      ]);
      await enforceRateLimit(
        `user:${ctx.user.id}:store:${input.storeId}`,
        "product.status",
        10
      );
      try {
        const product = await updateWooProduct(input.storeId, input.productId, {
          status: input.status,
        });
        await db.insert(auditLogs).values({
          storeId: input.storeId,
          actorUserId: ctx.user.id,
          action: "product.status_updated",
          targetType: "product",
          targetId: String(input.productId),
          outcome: "success",
          reason: input.reason,
          metadata: { status: input.status },
        });
        return product;
      } catch (error) {
        await db.insert(auditLogs).values({
          storeId: input.storeId,
          actorUserId: ctx.user.id,
          action: "product.status_updated",
          targetType: "product",
          targetId: String(input.productId),
          outcome: "failed",
          reason: input.reason,
        });
        return safeWooFailure(error);
      }
    }),
});
