import { desc, eq } from "drizzle-orm";
import { z } from "zod";
import { auditLogs } from "../../drizzle/schema";
import { requireStoreRole } from "../access";
import { protectedProcedure, router } from "../_core/trpc";

export const auditRouter = router({
  list: protectedProcedure.input(z.object({ storeId: z.number().int().positive() })).query(async ({ ctx, input }) => {
    const { db } = await requireStoreRole(input.storeId, ctx.user.id, ["owner", "operator", "catalog", "viewer"]);
    return db.select().from(auditLogs).where(eq(auditLogs.storeId, input.storeId)).orderBy(desc(auditLogs.createdAt)).limit(200);
  }),
});
