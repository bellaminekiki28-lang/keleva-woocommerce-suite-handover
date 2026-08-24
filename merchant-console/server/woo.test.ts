import { describe, expect, it } from "vitest";
import { credentialFingerprint, normalizeStoreUrl, redactConnectionError } from "./woo";

describe("WooCommerce BFF connection helpers", () => {
  it("normalizes an HTTPS store URL and strips fragments", () => {
    expect(normalizeStoreUrl("https://shop.example.test/catalogue/#private")).toBe("https://shop.example.test/catalogue");
  });

  it("rejects non-HTTPS store URLs", () => {
    expect(() => normalizeStoreUrl("http://shop.example.test")).toThrow("HTTPS");
  });

  it("uses a one-way credential fingerprint and redacts unexpected errors", () => {
    expect(credentialFingerprint("ck_demo_secret")).not.toContain("ck_demo_secret");
    expect(redactConnectionError(new Error("token=super-secret"))).not.toContain("super-secret");
  });
});
