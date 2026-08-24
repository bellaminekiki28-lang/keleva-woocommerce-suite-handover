import { TRPCError } from "@trpc/server";
import { and, desc, eq } from "drizzle-orm";
import { z } from "zod";
import { auditLogs, storeConnections, storeMemberships, stores } from "../../drizzle/schema";
import { getDb } from "../db";
import { decryptConnectionSecret, encryptConnectionSecret, generateWebhookSecret } from "../security";
import { credentialFingerprint, normalizeStoreUrl, redactConnectionError, verifyWooConnection } from "../woo";
import { protectedProcedure, router } from "../_core/trpc";

const connectionInput = z.object({
  name: z.string().trim().min(2).max(160),
  baseUrl: z.string().trim().url().max(2048),
  consumerKey: z.string().trim().min(8).max(512),
  consumerSecret: z.string().trim().min(8).max(512),
});

const storeIdInput = z.object({ storeId: z.number().int().positive() });

async function databaseOrThrow() {
  const db = await getDb();
  if (!db) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR", message: "La base de données de la console est indisponible." });
  return db;
}

async function membershipOrThrow(storeId: number, userId: number) {
  const db = await databaseOrThrow();
  const [membership] = await db.select().from(storeMemberships).where(and(eq(storeMemberships.storeId, storeId), eq(storeMemberships.userId, userId))).limit(1);
  if (!membership) throw new TRPCError({ code: "FORBIDDEN", message: "Vous n’avez pas accès à ce magasin." });
  return { db, membership };
}

export const storesRouter = router({
  list: protectedProcedure.query(async ({ ctx }) => {
    const db = await databaseOrThrow();
    return db
      .select({ id: stores.id, name: stores.name, baseUrl: stores.baseUrl, status: stores.status, lastSyncedAt: stores.lastSyncedAt, lastSyncError: stores.lastSyncError, role: storeMemberships.role })
      .from(stores)
      .innerJoin(storeMemberships, eq(stores.id, storeMemberships.storeId))
      .where(eq(storeMemberships.userId, ctx.user.id))
      .orderBy(desc(stores.updatedAt));
  }),

  connect: protectedProcedure.input(connectionInput).mutation(async ({ ctx, input }) => {
    const baseUrl = normalizeStoreUrl(input.baseUrl);
    try {
      await verifyWooConnection(baseUrl, input.consumerKey, input.consumerSecret);
    } catch (error) {
      throw new TRPCError({ code: "BAD_REQUEST", message: redactConnectionError(error) });
    }

    const db = await databaseOrThrow();
    const [duplicate] = await db.select({ id: stores.id }).from(stores).where(eq(stores.baseUrl, baseUrl)).limit(1);
    if (duplicate) throw new TRPCError({ code: "CONFLICT", message: "Ce magasin est déjà relié à la console." });

    const created = await db.transaction(async (tx) => {
      const [store] = await tx.insert(stores).values({ name: input.name, baseUrl, status: "connected" }).$returningId();
      if (!store) throw new Error("Store insert failed");
      await tx.insert(storeConnections).values({
        storeId: store.id,
        encryptedConsumerKey: encryptConnectionSecret(input.consumerKey),
        encryptedConsumerSecret: encryptConnectionSecret(input.consumerSecret),
        encryptedWebhookSecret: encryptConnectionSecret(generateWebhookSecret()),
        secretFingerprint: credentialFingerprint(input.consumerKey),
        lastVerifiedAt: new Date(),
      });
      await tx.insert(storeMemberships).values({ storeId: store.id, userId: ctx.user.id, role: "owner" });
      await tx.insert(auditLogs).values({ storeId: store.id, actorUserId: ctx.user.id, action: "store.connected", targetType: "store", targetId: String(store.id), outcome: "success", metadata: { source: "console" } });
      return store;
    });
    return { storeId: created.id, status: "connected" as const };
  }),

  revoke: protectedProcedure.input(storeIdInput).mutation(async ({ ctx, input }) => {
    const { db, membership } = await membershipOrThrow(input.storeId, ctx.user.id);
    if (membership.role !== "owner") throw new TRPCError({ code: "FORBIDDEN", message: "Seul le propriétaire peut révoquer une connexion magasin." });
    await db.transaction(async (tx) => {
      await tx.update(storeConnections).set({ isRevoked: true, revokedAt: new Date() }).where(eq(storeConnections.storeId, input.storeId));
      await tx.update(stores).set({ status: "revoked" }).where(eq(stores.id, input.storeId));
      await tx.insert(auditLogs).values({ storeId: input.storeId, actorUserId: ctx.user.id, action: "store.connection_revoked", targetType: "store", targetId: String(input.storeId), outcome: "success" });
    });
    return { revoked: true } as const;
  }),

  connectionHealth: protectedProcedure.input(storeIdInput).query(async ({ ctx, input }) => {
    const { db } = await membershipOrThrow(input.storeId, ctx.user.id);
    const [connection] = await db.select().from(storeConnections).where(eq(storeConnections.storeId, input.storeId)).limit(1);
    if (!connection || connection.isRevoked) return { state: "revoked" as const };
    // Decryption stays in the BFF. This read is intentionally never returned to the caller.
    decryptConnectionSecret(connection.encryptedConsumerKey);
    decryptConnectionSecret(connection.encryptedConsumerSecret);
    return { state: "ready" as const, lastVerifiedAt: connection.lastVerifiedAt };
  }),
});
