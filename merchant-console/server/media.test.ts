import { describe, expect, it } from "vitest";
import sharp from "sharp";
import { isAcceptedMedia, safeMediaFilename } from "./media";

describe("media intake guard", () => {
  it("accepts bounded supported image formats and rejects invalid media", () => {
    expect(isAcceptedMedia("image/jpeg", 10)).toBe(true);
    expect(isAcceptedMedia("application/pdf", 10)).toBe(false);
    expect(isAcceptedMedia("image/png", 21 * 1024 * 1024)).toBe(false);
  });

  it("normalizes untrusted file names before using them in storage keys", () => {
    expect(safeMediaFilename("../catalogue été.png")).toBe("-catalogue-t-.png");
  });

  it("encodes a real bounded source image as JPEG, WebP and AVIF", async () => {
    const source = await sharp({
      create: { width: 2, height: 2, channels: 3, background: { r: 232, g: 101, b: 43 } },
    }).png().toBuffer();

    const variants = await Promise.all([
      sharp(source).jpeg({ quality: 80 }).toBuffer(),
      sharp(source).webp({ quality: 80 }).toBuffer(),
      sharp(source).avif({ quality: 50 }).toBuffer(),
    ]);

    expect(variants.every((variant) => variant.byteLength > 20)).toBe(true);
    await expect(Promise.all(variants.map((variant) => sharp(variant).metadata()))).resolves.toHaveLength(3);
  });
});
