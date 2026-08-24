import type { Express, RequestHandler } from "express";
import { afterEach, describe, expect, it, vi } from "vitest";
import { ENV } from "./_core/env";
import { registerStorageProxy } from "./_core/storageProxy";

describe("storage proxy Express 5", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
  });

  it("registers the named wildcard route and forwards an array path as one storage key", async () => {
    const get = vi.fn();
    registerStorageProxy({ get } as unknown as Express);

    expect(get).toHaveBeenCalledWith("/manus-storage/*key", expect.any(Function));
    const handler = get.mock.calls[0][1] as RequestHandler;
    const originalUrl = ENV.forgeApiUrl;
    const originalKey = ENV.forgeApiKey;
    ENV.forgeApiUrl = "https://forge.example.test";
    ENV.forgeApiKey = "fixture-value";
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({ url: "https://storage.example.test/signed" }),
    });
    vi.stubGlobal("fetch", fetchMock);
    const res = {
      set: vi.fn(),
      redirect: vi.fn(),
      status: vi.fn().mockReturnThis(),
      send: vi.fn(),
    };

    await handler({ params: { key: ["products", "image.webp"] } } as never, res as never, vi.fn());

    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(String(fetchMock.mock.calls[0][0])).toContain("path=products%2Fimage.webp");
    expect(res.set).toHaveBeenCalledWith("Cache-Control", "no-store");
    expect(res.redirect).toHaveBeenCalledWith(307, "https://storage.example.test/signed");
    ENV.forgeApiUrl = originalUrl;
    ENV.forgeApiKey = originalKey;
  });
});
