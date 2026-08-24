import { TRPCError } from "@trpc/server";
import { desc, eq } from "drizzle-orm";
import { z } from "zod";
import { importJobs, importRows } from "../../drizzle/schema";
import { requireStoreRole } from "../access";
import { queueImportApply, queueImportRollback } from "../importProcessor";
import { enforceRateLimit } from "../rateLimit";
import { protectedProcedure, router } from "../_core/trpc";

const input = z.object({ storeId: z.number().int().positive(), importJobId: z.number().int().positive() });

export const importsRouter = router({
  list: protectedProcedure.input(z.object({ storeId: z.number().int().positive() })).query(async ({ ctx, input }) => {
    const { db } = await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog", "viewer"]);
    return db.select().from(importJobs).where(eq(importJobs.storeId, input.storeId)).orderBy(desc(importJobs.createdAt)).limit(50);
  }),
  rows: protectedProcedure.input(input).query(async ({ ctx, input }) => {
    const { db } = await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog", "viewer"]);
    return db.select().from(importRows).where(eq(importRows.importJobId, input.importJobId)).orderBy(importRows.rowNumber);
  }),
  queueApply: protectedProcedure.input(input.extend({ confirm: z.literal(true) })).mutation(async ({ ctx, input }) => {
    await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog"]);
    await enforceRateLimit(`user:${ctx.user.id}:store:${input.storeId}`, "import.apply", 3);
    try { return await queueImportApply(input.storeId, input.importJobId, ctx.user.id); } catch { throw new TRPCError({ code: "BAD_REQUEST", message: "Cet import ne peut pas être appliqué dans son état actuel." }); }
  }),
  rollback: protectedProcedure.input(input.extend({ confirm: z.literal(true) })).mutation(async ({ ctx, input }) => {
    await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator"]);
    await enforceRateLimit(`user:${ctx.user.id}:store:${input.storeId}`, "import.rollback", 3);
    try { return await queueImportRollback(input.storeId, input.importJobId, ctx.user.id); } catch { throw new TRPCError({ code: "BAD_REQUEST", message: "Le rollback de cet import n’est pas disponible." }); }
  }),
});
