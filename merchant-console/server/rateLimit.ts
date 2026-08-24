import { TRPCError } from "@trpc/server";
import { and, eq } from "drizzle-orm";
import { rateLimitBuckets } from "../drizzle/schema";
import { getDb } from "./db";

function windowStart(windowMs: number): Date {
  return new Date(Math.floor(Date.now() / windowMs) * windowMs);
}

export async function enforceRateLimit(subject: string, action: string, limit: number, windowMs = 60_000): Promise<void> {
  const db = await getDb();
  if (!db) throw new TRPCError({ code: "INTERNAL_SERVER_ERROR", message: "Le contrôle de débit est indisponible." });
  const startedAt = windowStart(windowMs);
  const [existing] = await db.select().from(rateLimitBuckets).where(and(eq(rateLimitBuckets.subject, subject), eq(rateLimitBuckets.action, action), eq(rateLimitBuckets.windowStartedAt, startedAt))).limit(1);
  if (existing) {
    const nextCount = existing.requestCount + 1;
    await db.update(rateLimitBuckets).set({ requestCount: nextCount }).where(eq(rateLimitBuckets.id, existing.id));
    if (nextCount > limit) throw new TRPCError({ code: "TOO_MANY_REQUESTS", message: "Trop de demandes sensibles. Réessayez dans une minute." });
    return;
  }
  await db.insert(rateLimitBuckets).values({ subject, action, windowStartedAt: startedAt, requestCount: 1 });
}
