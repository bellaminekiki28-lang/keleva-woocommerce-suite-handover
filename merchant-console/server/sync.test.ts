import { describe, expect, it } from "vitest";
import { canTransitionSync } from "./sync";

describe("sync lifecycle", () => {
  it("allows only the controlled queued-running-completed lifecycle", () => {
    expect(canTransitionSync("queued", "running")).toBe(true);
    expect(canTransitionSync("running", "completed")).toBe(true);
    expect(canTransitionSync("completed", "queued")).toBe(false);
    expect(canTransitionSync("queued", "completed")).toBe(false);
  });
});
