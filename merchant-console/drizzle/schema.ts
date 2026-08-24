import { boolean, index, int, json, mysqlEnum, mysqlTable, text, timestamp, uniqueIndex, varchar } from "drizzle-orm/mysql-core";

/**
 * Core user table backing auth flow.
 * Extend this file with additional tables as your product grows.
 * Columns use camelCase to match both database fields and generated types.
 */
export const users = mysqlTable("users", {
  /**
   * Surrogate primary key. Auto-incremented numeric value managed by the database.
   * Use this for relations between tables.
   */
  id: int("id").autoincrement().primaryKey(),
  /** Manus OAuth identifier (openId) returned from the OAuth callback. Unique per user. */
  openId: varchar("openId", { length: 64 }).notNull().unique(),
  name: text("name"),
  email: varchar("email", { length: 320 }),
  loginMethod: varchar("loginMethod", { length: 64 }),
  role: mysqlEnum("role", ["user", "admin"]).default("user").notNull(),
  createdAt: timestamp("createdAt").defaultNow().notNull(),
  updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  lastSignedIn: timestamp("lastSignedIn").defaultNow().notNull(),
});

export type User = typeof users.$inferSelect;
export type InsertUser = typeof users.$inferInsert;

export const stores = mysqlTable("stores", {
  id: int("id").autoincrement().primaryKey(),
  name: varchar("name", { length: 160 }).notNull(),
  baseUrl: varchar("base_url", { length: 2048 }).notNull().unique(),
  status: mysqlEnum("status", ["pending", "connected", "revoked", "error"]).default("pending").notNull(),
  lastSyncedAt: timestamp("last_synced_at"),
  lastSyncError: text("last_sync_error"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().onUpdateNow().notNull(),
});

export const storeConnections = mysqlTable("store_connections", {
  id: int("id").autoincrement().primaryKey(),
  storeId: int("store_id").notNull(),
  encryptedConsumerKey: text("encrypted_consumer_key").notNull(),
  encryptedConsumerSecret: text("encrypted_consumer_secret").notNull(),
  encryptedWebhookSecret: text("encrypted_webhook_secret").notNull(),
  secretFingerprint: varchar("secret_fingerprint", { length: 64 }).notNull(),
  isRevoked: boolean("is_revoked").default(false).notNull(),
  revokedAt: timestamp("revoked_at"),
  lastVerifiedAt: timestamp("last_verified_at"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().onUpdateNow().notNull(),
}, (table) => [index("store_connections_store_id_idx").on(table.storeId)]);

export const storeMemberships = mysqlTable("store_memberships", {
  id: int("id").autoincrement().primaryKey(),
  storeId: int("store_id").notNull(),
  userId: int("user_id").notNull(),
  role: mysqlEnum("role", ["owner", "operator", "catalog", "viewer"]).default("viewer").notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
}, (table) => [uniqueIndex("store_memberships_store_user_uq").on(table.storeId, table.userId), index("store_memberships_user_id_idx").on(table.userId)]);

export const auditLogs = mysqlTable("audit_logs", {
  id: int("id").autoincrement().primaryKey(),
  storeId: int("store_id").notNull(),
  actorUserId: int("actor_user_id"),
  action: varchar("action", { length: 100 }).notNull(),
  targetType: varchar("target_type", { length: 64 }).notNull(),
  targetId: varchar("target_id", { length: 128 }),
  outcome: mysqlEnum("outcome", ["success", "rejected", "failed"]).notNull(),
  reason: varchar("reason", { length: 500 }),
  metadata: json("metadata"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
}, (table) => [index("audit_logs_store_created_idx").on(table.storeId, table.createdAt)]);

export const syncRuns = mysqlTable("sync_runs", {
  id: int("id").autoincrement().primaryKey(),
  storeId: int("store_id").notNull(),
  kind: mysqlEnum("kind", ["full", "products", "orders", "stock", "media"]).notNull(),
  status: mysqlEnum("status", ["queued", "running", "completed", "failed", "cancelled"]).default("queued").notNull(),
  requestedByUserId: int("requested_by_user_id"),
  startedAt: timestamp("started_at"),
  completedAt: timestamp("completed_at"),
  errorMessage: text("error_message"),
  progress: int("progress").default(0).notNull(),
  idempotencyKey: varchar("idempotency_key", { length: 128 }).notNull().unique(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
}, (table) => [index("sync_runs_store_created_idx").on(table.storeId, table.createdAt)]);

export const webhookEvents = mysqlTable("webhook_events", {
  id: int("id").autoincrement().primaryKey(),
  storeId: int("store_id").notNull(),
  deliveryId: varchar("delivery_id", { length: 128 }).notNull(),
  topic: varchar("topic", { length: 160 }).notNull(),
  resourceId: varchar("resource_id", { length: 128 }),
  signatureVerified: boolean("signature_verified").default(false).notNull(),
  status: mysqlEnum("status", ["received", "processed", "ignored", "failed"]).default("received").notNull(),
  payloadDigest: varchar("payload_digest", { length: 64 }).notNull(),
  processingError: text("processing_error"),
  receivedAt: timestamp("received_at").defaultNow().notNull(),
  processedAt: timestamp("processed_at"),
}, (table) => [uniqueIndex("webhook_events_store_delivery_uq").on(table.storeId, table.deliveryId), index("webhook_events_store_received_idx").on(table.storeId, table.receivedAt)]);

export const mediaAssets = mysqlTable("media_assets", {
  id: int("id").autoincrement().primaryKey(),
  storeId: int("store_id").notNull(),
  originalStorageKey: varchar("original_storage_key", { length: 512 }).notNull(),
  originalUrl: varchar("original_url", { length: 2048 }).notNull(),
  mimeType: varchar("mime_type", { length: 120 }).notNull(),
  status: mysqlEnum("status", ["uploaded", "queued", "processing", "ready", "failed"]).default("uploaded").notNull(),
  variants: json("variants"),
  errorMessage: text("error_message"),
  retryCount: int("retry_count").default(0).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().onUpdateNow().notNull(),
}, (table) => [index("media_assets_store_status_idx").on(table.storeId, table.status)]);

export const importJobs = mysqlTable("import_jobs", {
  id: int("id").autoincrement().primaryKey(),
  storeId: int("store_id").notNull(),
  initiatedByUserId: int("initiated_by_user_id").notNull(),
  sourceStorageKey: varchar("source_storage_key", { length: 512 }).notNull(),
  mode: mysqlEnum("mode", ["validate", "apply", "rollback"]).notNull(),
  status: mysqlEnum("status", ["uploaded", "validating", "invalid", "ready", "applying", "completed", "rolled_back", "failed"]).default("uploaded").notNull(),
  errorCount: int("error_count").default(0).notNull(),
  appliedCount: int("applied_count").default(0).notNull(),
  rollbackSnapshot: json("rollback_snapshot"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().onUpdateNow().notNull(),
}, (table) => [index("import_jobs_store_created_idx").on(table.storeId, table.createdAt)]);

export const importRows = mysqlTable("import_rows", {
  id: int("id").autoincrement().primaryKey(),
  importJobId: int("import_job_id").notNull(),
  rowNumber: int("row_number").notNull(),
  status: mysqlEnum("status", ["valid", "invalid", "applied", "rolled_back"]).notNull(),
  errorCode: varchar("error_code", { length: 80 }),
  errorMessage: varchar("error_message", { length: 500 }),
  normalizedPayload: json("normalized_payload"),
}, (table) => [uniqueIndex("import_rows_job_row_uq").on(table.importJobId, table.rowNumber)]);

export const rateLimitBuckets = mysqlTable("rate_limit_buckets", {
  id: int("id").autoincrement().primaryKey(),
  subject: varchar("subject", { length: 160 }).notNull(),
  action: varchar("action", { length: 100 }).notNull(),
  windowStartedAt: timestamp("window_started_at").notNull(),
  requestCount: int("request_count").default(0).notNull(),
  updatedAt: timestamp("updated_at").defaultNow().onUpdateNow().notNull(),
}, (table) => [uniqueIndex("rate_limit_subject_action_window_uq").on(table.subject, table.action, table.windowStartedAt), index("rate_limit_updated_idx").on(table.updatedAt)]);
