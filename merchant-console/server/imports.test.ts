import { describe, expect, it } from "vitest";
import { validateCatalogCsv } from "./imports";

describe("catalog CSV validation", () => {
  it("returns a per-row error without accepting invalid stock", () => {
    const rows = validateCatalogCsv("sku,name,stock_quantity,status\nA-01,Tasse,5,publish\nA-02,Vase,invalid,draft\n");
    expect(rows).toHaveLength(2);
    expect(rows[0]?.status).toBe("valid");
    expect(rows[1]).toMatchObject({ status: "invalid", errorCode: "INVALID_STOCK", rowNumber: 3 });
  });

  it("rejects unsupported columns before rows are persisted", () => {
    expect(() => validateCatalogCsv("sku,name,cost\nA-01,Tasse,3\n")).toThrow("Colonne non autorisée");
  });
});
