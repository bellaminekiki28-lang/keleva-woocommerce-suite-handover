import { beforeEach, describe, expect, it, vi } from "vitest";

const mocks = vi.hoisted(() => ({
  getDb: vi.fn(),
  findWooProductBySku: vi.fn(),
  createWooProduct: vi.fn(),
  updateWooProduct: vi.fn(),
  deleteWooProduct: vi.fn(),
  authenticateRequest: vi.fn(),
}));

vi.mock("./db", () => ({ getDb: mocks.getDb }));
vi.mock("./woocommerceClient", () => ({
  findWooProductBySku: mocks.findWooProductBySku,
  createWooProduct: mocks.createWooProduct,
  updateWooProduct: mocks.updateWooProduct,
  deleteWooProduct: mocks.deleteWooProduct,
}));
vi.mock("./_core/sdk", () => ({ sdk: { authenticateRequest: mocks.authenticateRequest } }));

import { catalogPayloadFromRow, processOneQueuedImport, processQueuedImportRequest, rollbackPayload } from "./importProcessor";

type QueryReply = unknown[];

function importJob(overrides: Record<string, unknown> = {}) {
  return {
    id: 81,
    storeId: 7,
    initiatedByUserId: 12,
    sourceStorageKey: "imports/81.csv",
    mode: "apply",
    status: "ready",
    errorCount: 0,
    appliedCount: 0,
    rollbackSnapshot: { version: 1, entries: [] },
    createdAt: new Date("2026-08-24T10:00:00.000Z"),
    updatedAt: new Date("2026-08-24T10:00:00.000Z"),
    ...overrides,
  };
}

function validRow(rowNumber: number, payload: Record<string, unknown>) {
  return { id: rowNumber + 100, importJobId: 81, rowNumber, status: "valid", errorCode: null, errorMessage: null, normalizedPayload: payload };
}

function fakeDb(selectReplies: QueryReply[], updateReplies: unknown[] = []) {
  const nextSelect = () => selectReplies.shift() ?? [];
  const nextUpdate = () => updateReplies.shift() ?? [{ affectedRows: 1 }];

  return {
    select: () => ({
      from: () => {
        const reply = nextSelect();
        const chain = {
          where: () => chain,
          orderBy: () => chain,
          limit: async () => reply,
          then: <T>(resolve: (value: unknown[]) => T, reject?: (reason: unknown) => T) => Promise.resolve(reply).then(resolve, reject),
        };
        return chain;
      },
    }),
    update: () => ({ set: () => ({ where: async () => nextUpdate() }) }),
    insert: () => ({ values: async () => ({}) }),
  };
}

beforeEach(() => {
  vi.clearAllMocks();
});

describe("import processor payload safety", () => {
  it("normalizes a validated line and preserves a complete update snapshot", () => {
    expect(catalogPayloadFromRow({ sku: " KLV-1 ", name: " Vase ", regular_price: "89", stock_quantity: "0", status: "draft" })).toEqual({ sku: "KLV-1", name: "Vase", regular_price: "89", stock_quantity: 0, manage_stock: true, stock_status: "outofstock", status: "draft" });
    expect(rollbackPayload({ id: 51, sku: "KLV-1", name: "Vase initial", regular_price: "72", status: "publish", stock_quantity: 2, manage_stock: true, stock_status: "instock" })).toMatchObject({ sku: "KLV-1", name: "Vase initial", regular_price: "72", stock_quantity: 2, stock_status: "instock" });
  });
});

describe("processOneQueuedImport", () => {
  it("creates a missing SKU then marks the job completed", async () => {
    mocks.getDb.mockResolvedValue(fakeDb([
      [importJob()],
      [importJob({ status: "applying" })],
      [validRow(2, { sku: "KLV-NEW", name: "Nouveau", regular_price: "34", stock_quantity: 4 })],
    ]) as never);
    mocks.findWooProductBySku.mockResolvedValue(null);
    mocks.createWooProduct.mockResolvedValue({ id: 501 });

    await expect(processOneQueuedImport()).resolves.toMatchObject({ importJobId: 81, status: "completed" });
    expect(mocks.createWooProduct).toHaveBeenCalledWith(7, { sku: "KLV-NEW", name: "Nouveau", regular_price: "34", stock_quantity: 4, manage_stock: true, stock_status: "instock" });
    expect(mocks.updateWooProduct).not.toHaveBeenCalled();
  });

  it("updates an existing SKU from its validated payload without creating a duplicate", async () => {
    mocks.getDb.mockResolvedValue(fakeDb([
      [importJob()],
      [importJob({ status: "applying" })],
      [validRow(2, { sku: "KLV-EXIST", name: "Nom corrigé", regular_price: "88" })],
    ]) as never);
    const existing = { id: 320, sku: "KLV-EXIST", name: "Ancien nom", regular_price: "70", status: "publish", stock_quantity: 1, manage_stock: true, stock_status: "instock" };
    mocks.findWooProductBySku.mockResolvedValue(existing);
    mocks.updateWooProduct.mockResolvedValue({ id: 320 });

    await expect(processOneQueuedImport()).resolves.toMatchObject({ status: "completed" });
    expect(mocks.updateWooProduct).toHaveBeenCalledWith(7, 320, { sku: "KLV-EXIST", name: "Nom corrigé", regular_price: "88" });
    expect(mocks.createWooProduct).not.toHaveBeenCalled();
  });

  it("rolls back successful earlier mutations when a later row fails", async () => {
    mocks.getDb.mockResolvedValue(fakeDb([
      [importJob()],
      [importJob({ status: "applying" })],
      [validRow(2, { sku: "KLV-FIRST", name: "Premier", regular_price: "34" }), validRow(3, { sku: "KLV-BROKEN", name: "Second", regular_price: "40" })],
      [{ status: "applied" }],
    ]) as never);
    mocks.findWooProductBySku.mockResolvedValue(null);
    mocks.createWooProduct.mockResolvedValueOnce({ id: 501 }).mockRejectedValueOnce(new Error("WooCommerce request failed with HTTP 502"));

    await expect(processOneQueuedImport()).resolves.toMatchObject({ importJobId: 81, status: "failed" });
    expect(mocks.deleteWooProduct).toHaveBeenCalledWith(7, 501);
  });

  it("replays the stored pre-mutation snapshot during a manual rollback", async () => {
    const previous = { sku: "KLV-RESTORE", name: "Avant import", regular_price: "55", status: "publish", stock_quantity: 3, manage_stock: true, stock_status: "instock" };
    const rollbackJob = importJob({ mode: "rollback", rollbackSnapshot: { version: 1, entries: [{ rowNumber: 2, action: "updated", productId: 88, previous }] } });
    mocks.getDb.mockResolvedValue(fakeDb([
      [],
      [rollbackJob],
      [importJob({ mode: "rollback", status: "applying", rollbackSnapshot: rollbackJob.rollbackSnapshot })],
      [{ status: "applied" }],
    ]) as never);
    mocks.updateWooProduct.mockResolvedValue({ id: 88 });

    await expect(processOneQueuedImport()).resolves.toMatchObject({ importJobId: 81, status: "rolled_back" });
    expect(mocks.updateWooProduct).toHaveBeenCalledWith(7, 88, previous);
  });

  it("does not process a job when another worker won the atomic reservation", async () => {
    mocks.getDb.mockResolvedValue(fakeDb([[importJob()]], [[{ affectedRows: 0 }]]) as never);

    await expect(processOneQueuedImport()).resolves.toEqual({ processed: false, skipped: "claimed_elsewhere" });
    expect(mocks.findWooProductBySku).not.toHaveBeenCalled();
  });
});

describe("processQueuedImportRequest", () => {
  function responseRecorder() {
    const record: { status?: number; body?: unknown } = {};
    const res = {
      status: (status: number) => {
        record.status = status;
        return res;
      },
      json: (body: unknown) => {
        record.body = body;
        return res;
      },
    };
    return { record, res };
  }

  it("rejects a caller that is not a scheduled identity", async () => {
    mocks.authenticateRequest.mockResolvedValue({ isCron: false });
    const { record, res } = responseRecorder();

    await processQueuedImportRequest({} as never, res as never);

    expect(record).toEqual({ status: 403, body: { error: "cron_only" } });
  });

  it("does not leak a backend or WooCommerce error from the scheduled callback", async () => {
    mocks.authenticateRequest.mockResolvedValue({ isCron: true, taskUid: "task-imports" });
    mocks.getDb.mockRejectedValue(new Error("Woo secret must never reach the caller"));
    const { record, res } = responseRecorder();
    const errorSpy = vi.spyOn(console, "error").mockImplementation(() => undefined);

    await processQueuedImportRequest({} as never, res as never);

    expect(record.status).toBe(500);
    expect(record.body).toMatchObject({ error: "import_worker_failed", message: "Le worker d’import a échoué ; consulter les journaux sécurisés." });
    expect(JSON.stringify(record.body)).not.toContain("secret");
    errorSpy.mockRestore();
  });
});
