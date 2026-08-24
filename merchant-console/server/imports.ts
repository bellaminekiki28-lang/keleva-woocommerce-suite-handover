import { randomUUID } from "node:crypto";
import { parse } from "csv-parse/sync";

export type ImportValidation = { rowNumber: number; status: "valid" | "invalid"; errorCode?: string; errorMessage?: string; normalizedPayload?: Record<string, string> };

const allowedHeaders = new Set(["sku", "name", "regular_price", "stock_quantity", "status"]);

export function validateCatalogCsv(source: string): ImportValidation[] {
  const records = parse(source, { columns: true, skip_empty_lines: true, trim: true, bom: true, relax_column_count: false }) as Array<Record<string, string>>;
  if (records.length > 2_000) throw new Error("Le CSV dépasse 2 000 lignes ; segmentez l’import.");
  const headers = records.length > 0 ? Object.keys(records[0] ?? {}) : [];
  const unknownHeader = headers.find((header) => !allowedHeaders.has(header));
  if (unknownHeader) throw new Error(`Colonne non autorisée : ${unknownHeader}`);
  return records.map((record, index) => {
    const rowNumber = index + 2;
    if (!record.sku?.trim()) return { rowNumber, status: "invalid", errorCode: "MISSING_SKU", errorMessage: "La colonne sku est obligatoire." };
    if (!record.name?.trim()) return { rowNumber, status: "invalid", errorCode: "MISSING_NAME", errorMessage: "La colonne name est obligatoire." };
    if (record.stock_quantity && (!/^\d+$/.test(record.stock_quantity) || Number(record.stock_quantity) > 1_000_000)) return { rowNumber, status: "invalid", errorCode: "INVALID_STOCK", errorMessage: "stock_quantity doit être un entier entre 0 et 1 000 000." };
    if (record.status && !["publish", "draft", "private"].includes(record.status)) return { rowNumber, status: "invalid", errorCode: "INVALID_STATUS", errorMessage: "status doit être publish, draft ou private." };
    return { rowNumber, status: "valid", normalizedPayload: record };
  });
}

export function importObjectKey(storeId: number): string {
  return `stores/${storeId}/imports/${randomUUID()}.csv`;
}
