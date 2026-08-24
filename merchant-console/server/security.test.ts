import { describe, expect, it } from "vitest";
import { appRouter } from "./routers";
import { decryptConnectionSecret, encryptConnectionSecret } from "./security";
import type { TrpcContext } from "./_core/context";

describe("security.health", () => {
  it("validates the server encryption secret through the health procedure without exposing it", async () => {
    const caller = appRouter.createCaller({} as TrpcContext);
    await expect(caller.security.health()).resolves.toEqual({ encryptionReady: true, algorithm: "aes-256-gcm" });
  });

  it("encrypts and decrypts a WooCommerce credential only on the server", () => {
    const encrypted = encryptConnectionSecret("wc_secret_never_send_to_browser");
    expect(encrypted).not.toContain("wc_secret_never_send_to_browser");
    expect(decryptConnectionSecret(encrypted)).toBe("wc_secret_never_send_to_browser");
  });
});
