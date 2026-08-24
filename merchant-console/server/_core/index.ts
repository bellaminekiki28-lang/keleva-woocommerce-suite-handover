import "dotenv/config";
import express from "express";
import helmet from "helmet";
import { createServer } from "http";
import net from "net";
import { createExpressMiddleware } from "@trpc/server/adapters/express";
import { registerOAuthRoutes } from "./oauth";
import { registerStorageProxy } from "./storageProxy";
import { appRouter } from "../routers";
import { createContext } from "./context";
import { serveStatic, setupVite } from "./vite";
import { receiveWooWebhook } from "../webhooks";
import { processQueuedSyncRequest } from "../sync";
import { processQueuedMediaRequest, uploadOriginalMedia } from "../media";
import { uploadCatalogImport } from "../importHandlers";
import { processQueuedImportRequest } from "../importProcessor";

function isPortAvailable(port: number): Promise<boolean> {
  return new Promise(resolve => {
    const server = net.createServer();
    server.listen(port, () => {
      server.close(() => resolve(true));
    });
    server.on("error", () => resolve(false));
  });
}

async function findAvailablePort(startPort: number = 3000): Promise<number> {
  for (let port = startPort; port < startPort + 20; port++) {
    if (await isPortAvailable(port)) {
      return port;
    }
  }
  throw new Error(`No available port found starting from ${startPort}`);
}

async function startServer() {
  const app = express();
  const server = createServer(app);
  app.use(helmet({
    contentSecurityPolicy: process.env.NODE_ENV === "production" ? undefined : false,
    crossOriginEmbedderPolicy: false,
    referrerPolicy: { policy: "strict-origin-when-cross-origin" },
  }));
  app.disable("x-powered-by");
  // The WooCommerce signature covers the exact byte sequence. This route must stay before JSON parsing.
  app.post("/api/webhooks/woocommerce/:storeId", express.raw({ type: "application/json", limit: "2mb" }), receiveWooWebhook);
  app.post("/api/media/upload/:storeId", express.raw({ type: "*/*", limit: "20mb" }), uploadOriginalMedia);
  app.post("/api/imports/catalog/:storeId", express.raw({ type: ["text/csv", "application/csv"], limit: "5mb" }), uploadCatalogImport);
  // Configure body parser with larger size limit for file uploads
  app.use(express.json({ limit: "50mb" }));
  app.use(express.urlencoded({ limit: "50mb", extended: true }));
  app.post("/api/scheduled/process-syncs", processQueuedSyncRequest);
  app.post("/api/scheduled/process-media", processQueuedMediaRequest);
  app.post("/api/scheduled/process-imports", processQueuedImportRequest);
  registerStorageProxy(app);
  registerOAuthRoutes(app);
  // tRPC API
  app.use(
    "/api/trpc",
    createExpressMiddleware({
      router: appRouter,
      createContext,
    })
  );
  // development mode uses Vite, production mode uses static files
  if (process.env.NODE_ENV === "development") {
    await setupVite(app, server);
  } else {
    serveStatic(app);
  }

  const preferredPort = parseInt(process.env.PORT || "3000");
  const port = await findAvailablePort(preferredPort);

  if (port !== preferredPort) {
    console.log(`Port ${preferredPort} is busy, using port ${port} instead`);
  }

  server.listen(port, () => {
    console.log(`Server running on http://localhost:${port}/`);
  });
}

startServer().catch(console.error);
