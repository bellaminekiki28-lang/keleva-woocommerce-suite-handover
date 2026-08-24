import { describe, expect, it } from "vitest";
import { roleAllows } from "./access";

describe("store role matrix", () => {
  it("permits a catalog manager to update stock but not revoke a store", () => {
    expect(roleAllows("catalog", ["owner", "operator", "catalog"])).toBe(true);
    expect(roleAllows("catalog", ["owner"])).toBe(false);
  });

  it("keeps viewer access read-only", () => {
    expect(roleAllows("viewer", ["owner", "operator", "catalog", "viewer"])).toBe(true);
    expect(roleAllows("viewer", ["owner", "operator"])).toBe(false);
  });
});
