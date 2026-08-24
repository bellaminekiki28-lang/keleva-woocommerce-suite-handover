import { createCipheriv, createDecipheriv, randomBytes } from "node:crypto";

const ALGORITHM = "aes-256-gcm";

function encryptionKey(): Buffer {
  const encoded = process.env.KELEVA_CONNECTION_ENCRYPTION_KEY;
  if (!encoded) throw new Error("KELEVA_CONNECTION_ENCRYPTION_KEY is required");
  const key = Buffer.from(encoded, "base64");
  if (key.length !== 32) throw new Error("KELEVA_CONNECTION_ENCRYPTION_KEY must decode to 32 bytes");
  return key;
}

export function connectionEncryptionHealth() {
  encryptionKey();
  return { ready: true, algorithm: ALGORITHM } as const;
}

export function encryptConnectionSecret(plaintext: string): string {
  const iv = randomBytes(12);
  const cipher = createCipheriv(ALGORITHM, encryptionKey(), iv);
  const ciphertext = Buffer.concat([cipher.update(plaintext, "utf8"), cipher.final()]);
  const authTag = cipher.getAuthTag();
  return [iv, authTag, ciphertext].map((part) => part.toString("base64url")).join(".");
}

export function decryptConnectionSecret(payload: string): string {
  const [encodedIv, encodedTag, encodedCiphertext] = payload.split(".");
  if (!encodedIv || !encodedTag || !encodedCiphertext) throw new Error("Malformed encrypted connection secret");
  const decipher = createDecipheriv(ALGORITHM, encryptionKey(), Buffer.from(encodedIv, "base64url"));
  decipher.setAuthTag(Buffer.from(encodedTag, "base64url"));
  return Buffer.concat([decipher.update(Buffer.from(encodedCiphertext, "base64url")), decipher.final()]).toString("utf8");
}

export function generateWebhookSecret(): string {
  return randomBytes(32).toString("base64url");
}
