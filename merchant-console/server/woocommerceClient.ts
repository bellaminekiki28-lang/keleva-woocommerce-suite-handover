import { eq } from "drizzle-orm";
import { storeConnections, stores } from "../drizzle/schema";
import { getDb } from "./db";
import { decryptConnectionSecret } from "./security";

type WooResponse<T> = { data: T; headers: Headers };

export type WooOrder = { id: number; number: string; status: string; total: string; currency: string; date_created_gmt: string | null; billing?: { first_name?: string; last_name?: string; email?: string } };
export type WooProduct = { id: number; name: string; sku: string; status: string; stock_status: string; stock_quantity: number | null; manage_stock?: boolean; regular_price: string; type: string; price: string };

async function activeConnection(storeId: number) {
  const db = await getDb();
  if (!db) throw new Error("Database unavailable");
  const [store] = await db.select().from(stores).where(eq(stores.id, storeId)).limit(1);
  const [connection] = await db.select().from(storeConnections).where(eq(storeConnections.storeId, storeId)).limit(1);
  if (!store || !connection || store.status !== "connected" || connection.isRevoked) throw new Error("Store connection is not active");
  return { baseUrl: store.baseUrl, consumerKey: decryptConnectionSecret(connection.encryptedConsumerKey), consumerSecret: decryptConnectionSecret(connection.encryptedConsumerSecret) };
}

export async function wooRequest<T>(storeId: number, path: string, init?: RequestInit): Promise<WooResponse<T>> {
  const connection = await activeConnection(storeId);
  const authorization = Buffer.from(`${connection.consumerKey}:${connection.consumerSecret}`).toString("base64");
  const response = await fetch(`${connection.baseUrl}/wp-json/wc/v3/${path.replace(/^\//, "")}`, {
    ...init,
    headers: { Accept: "application/json", Authorization: `Basic ${authorization}`, ...(init?.headers ?? {}) },
  });
  if (!response.ok) throw new Error(`WooCommerce request failed with HTTP ${response.status}`);
  return { data: await response.json() as T, headers: response.headers };
}

export async function listWooOrders(storeId: number, limit: number): Promise<WooOrder[]> {
  const { data } = await wooRequest<WooOrder[]>(storeId, `orders?per_page=${limit}&orderby=date&order=desc`);
  return data;
}

export async function listWooProducts(storeId: number, limit: number): Promise<WooProduct[]> {
  const { data } = await wooRequest<WooProduct[]>(storeId, `products?per_page=${limit}&orderby=modified&order=desc`);
  return data;
}

export async function updateWooProduct(storeId: number, productId: number, payload: Record<string, unknown>): Promise<WooProduct> {
  const { data } = await wooRequest<WooProduct>(storeId, `products/${productId}`, { method: "PUT", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) });
  return data;
}

export async function findWooProductBySku(storeId: number, sku: string): Promise<WooProduct | null> {
  const { data } = await wooRequest<WooProduct[]>(storeId, `products?sku=${encodeURIComponent(sku)}&per_page=2`);
  if (data.length > 1) throw new Error("WooCommerce returned duplicate SKU results");
  return data[0] ?? null;
}

export async function createWooProduct(storeId: number, payload: Record<string, unknown>): Promise<WooProduct> {
  const { data } = await wooRequest<WooProduct>(storeId, "products", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) });
  return data;
}

export async function deleteWooProduct(storeId: number, productId: number): Promise<void> {
  await wooRequest<WooProduct>(storeId, `products/${productId}?force=true`, { method: "DELETE" });
}
