import { createHmac } from "node:crypto";
import { describe, expect, it } from "vitest";
import { isValidWebhookSignature } from "./webhooks";

describe("WooCommerce webhook signature", () => {
  it("accepts the base64 HMAC-SHA256 signature generated over the raw body", () => {
    const body = Buffer.from('{"id":42,"status":"processing"}');
    const hmacKey = "webhook-hmac-fixture";
    const signature = createHmac("sha256", hmacKey).update(body).digest("base64");
    expect(isValidWebhookSignature(body, hmacKey, signature)).toBe(true);
  });

  it("rejects a modified body and an absent signature", () => {
    const body = Buffer.from('{"id":42}');
    const hmacKey = "webhook-hmac-fixture";
    const signature = createHmac("sha256", hmacKey).update(body).digest("base64");
    expect(isValidWebhookSignature(Buffer.from('{"id":43}'), hmacKey, signature)).toBe(false);
    expect(isValidWebhookSignature(body, hmacKey, undefined)).toBe(false);
  });
});
