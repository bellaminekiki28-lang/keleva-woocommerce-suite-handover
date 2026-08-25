import { describe, expect, it } from "vitest";
import { orderStatusInput, productInput } from "./routers/operations";

describe("Keleva Manager operation inputs", () => {
  it("accepts a simple product draft with decimal comma", () => {
    const result = productInput.safeParse({
      storeId: 1,
      name: "Plat recette",
      regularPrice: "49,90",
      stockQuantity: 3,
      confirm: true,
    });
    expect(result.success).toBe(true);
    if (result.success) expect(result.data.publish).toBe(false);
  });

  it("rejects invalid product prices, empty names and missing confirmation", () => {
    expect(
      productInput.safeParse({
        storeId: 1,
        name: "A",
        regularPrice: "0",
        stockQuantity: 0,
        confirm: true,
      }).success
    ).toBe(false);
    expect(
      productInput.safeParse({
        storeId: 1,
        name: "Plat valide",
        regularPrice: "49.999",
        stockQuantity: 0,
        confirm: true,
      }).success
    ).toBe(false);
    expect(
      productInput.safeParse({
        storeId: 1,
        name: "Plat valide",
        regularPrice: "49",
        stockQuantity: 0,
      }).success
    ).toBe(false);
  });

  it("allows only controlled order statuses and explicit confirmation", () => {
    expect(
      orderStatusInput.safeParse({
        storeId: 1,
        orderId: 10,
        status: "on-hold",
        confirm: true,
      }).success
    ).toBe(true);
    expect(
      orderStatusInput.safeParse({
        storeId: 1,
        orderId: 10,
        status: "refunded",
        confirm: true,
      }).success
    ).toBe(false);
    expect(
      orderStatusInput.safeParse({
        storeId: 1,
        orderId: 10,
        status: "completed",
        confirm: false,
      }).success
    ).toBe(false);
  });
});
