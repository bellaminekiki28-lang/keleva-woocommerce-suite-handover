import { createHash } from "node:crypto";

const timeoutMs = 10_000;

export function normalizeStoreUrl(rawUrl: string): string {
  const url = new URL(rawUrl.trim());
  if (url.protocol !== "https:") throw new Error("L’URL du magasin doit utiliser HTTPS.");
  if (url.username || url.password) throw new Error("L’URL du magasin ne peut pas contenir d’identifiants.");
  url.pathname = url.pathname.replace(/\/$/, "");
  url.search = "";
  url.hash = "";
  return url.toString().replace(/\/$/, "");
}

export function credentialFingerprint(consumerKey: string): string {
  return createHash("sha256").update(consumerKey).digest("hex");
}

export async function verifyWooConnection(baseUrl: string, consumerKey: string, consumerSecret: string): Promise<{ siteName: string | null }> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const authorization = Buffer.from(`${consumerKey}:${consumerSecret}`).toString("base64");
    const response = await fetch(`${baseUrl}/wp-json/wc/v3/system_status`, {
      headers: { Authorization: `Basic ${authorization}`, Accept: "application/json" },
      signal: controller.signal,
    });
    if (!response.ok) throw new Error("WooCommerce a refusé la connexion. Vérifiez les permissions API et l’URL.");
    const payload = await response.json() as { environment?: { home_url?: string } };
    return { siteName: payload.environment?.home_url ?? null };
  } finally {
    clearTimeout(timer);
  }
}

export function redactConnectionError(error: unknown): string {
  if (error instanceof Error && error.message.includes("WooCommerce")) return error.message;
  return "La vérification de connexion a échoué sans exposer les identifiants fournis.";
}
