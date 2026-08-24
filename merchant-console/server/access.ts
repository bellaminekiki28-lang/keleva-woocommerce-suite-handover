import { TRPCError } from "@trpc/server";
import { and, eq } from "drizzle-orm";
import { storeMemberships } from "../drizzle/schema";
import { getDb } from "./db";

export type StoreRole = "owner" | "operator" | "catalog" | "viewer";

export function roleAllows(role: StoreRole, permitted: readonly StoreRole[]): boolean {
  return permitted.includes(role);
}

export async function requireStoreRole(storeId: number, userId: number, permitted: readonly StoreRole[]) {
  const db = await getDb();
  if (!db) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR", message: "La base de données de la console est indisponible." });
  const [membership] = await db.select().from(storeMemberships).where(and(eq(storeMemberships.storeId, storeId), eq(storeMemberships.userId, userId))).limit(1);
  if (!membership || !roleAllows(membership.role, permitted)) throw new TRPCError({ code: "FORBIDDEN", message: "Votre rôle ne permet pas cette opération." });
  return { db, membership };
}
