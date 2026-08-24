import { COOKIE_NAME } from "@shared/const";
import { getSessionCookieOptions } from "./_core/cookies";
import { systemRouter } from "./_core/systemRouter";
import { publicProcedure, router } from "./_core/trpc";
import { connectionEncryptionHealth } from "./security";
import { storesRouter } from "./routers/stores";
import { operationsRouter } from "./routers/operations";
import { syncRouter } from "./routers/sync";
import { mediaRouter } from "./routers/media";
import { auditRouter } from "./routers/audit";
import { importsRouter } from "./routers/imports";

export const appRouter = router({
    // if you need to use socket.io, read and register route in server/_core/index.ts, all api should start with '/api/' so that the gateway can route correctly
  system: systemRouter,
  auth: router({
    me: publicProcedure.query(opts => opts.ctx.user),
    logout: publicProcedure.mutation(({ ctx }) => {
      const cookieOptions = getSessionCookieOptions(ctx.req);
      ctx.res.clearCookie(COOKIE_NAME, { ...cookieOptions, maxAge: -1 });
      return {
        success: true,
      } as const;
    }),
  }),
  security: router({
    health: publicProcedure.query(() => {
      const status = connectionEncryptionHealth();
      return { encryptionReady: status.ready, algorithm: status.algorithm };
    }),
  }),
  stores: storesRouter,
  operations: operationsRouter,
  sync: syncRouter,
  media: mediaRouter,
  audit: auditRouter,
  imports: importsRouter,

  // TODO: add feature routers here, e.g.
  // todo: router({
  //   list: protectedProcedure.query(({ ctx }) =>
  //     db.getUserTodos(ctx.user.id)
  //   ),
  // }),
});

export type AppRouter = typeof appRouter;
