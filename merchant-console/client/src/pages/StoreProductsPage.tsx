import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { trpc } from "@/lib/trpc";
import {
  AlertCircle,
  ArrowLeft,
  Check,
  PackageSearch,
  Plus,
  RefreshCw,
  ShieldCheck,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { useLocation } from "wouter";
import { toast } from "sonner";

type StoreProductsPageProps = { mode: "catalogue" | "stock" };

export default function StoreProductsPage({ mode }: StoreProductsPageProps) {
  const [, setLocation] = useLocation();
  const stores = trpc.stores.list.useQuery();
  const connectedStores = useMemo(
    () => (stores.data ?? []).filter(store => store.status === "connected"),
    [stores.data]
  );
  const [storeId, setStoreId] = useState<number | null>(null);
  const [quantities, setQuantities] = useState<Record<number, string>>({});
  const utils = trpc.useUtils();

  useEffect(() => {
    if (storeId === null && connectedStores[0])
      setStoreId(connectedStores[0].id);
  }, [connectedStores, storeId]);

  const products = trpc.operations.products.useQuery(
    { storeId: storeId ?? 0, limit: 50 },
    { enabled: storeId !== null }
  );
  const setStock = trpc.operations.setStock.useMutation({
    onSuccess: async (_product, variables) => {
      await utils.operations.products.invalidate({
        storeId: variables.storeId,
        limit: 50,
      });
      toast.success("Stock mis à jour et journalisé.");
    },
    onError: error => toast.error(error.message),
  });

  const title = mode === "catalogue" ? "Catalogue" : "Stock et disponibilité";
  const description =
    mode === "catalogue"
      ? "Consultez les produits synchronisés depuis WooCommerce, sans modifier de données par accident."
      : "Modifiez une quantité à la fois, avec confirmation et journal d’audit.";

  function quantityFor(productId: number, current: number | null) {
    return quantities[productId] ?? (current === null ? "0" : String(current));
  }

  function saveStock(productId: number, current: number | null) {
    if (storeId === null) return;
    const value = Number(quantityFor(productId, current));
    if (!Number.isInteger(value) || value < 0) {
      toast.error("La quantité doit être un nombre entier positif ou nul.");
      return;
    }
    setStock.mutate({
      storeId,
      productId,
      quantity: value,
      confirm: true,
      reason: "Modification depuis Keleva Manager",
    });
  }

  return (
    <div className="mx-auto max-w-6xl">
      <header className="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <button
            type="button"
            onClick={() => setLocation("/")}
            className="mb-4 inline-flex items-center text-sm font-semibold text-[#6d5a50] hover:text-[#1d1d1b] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#eb5f2a] focus-visible:ring-offset-2"
          >
            <ArrowLeft className="mr-2 h-4 w-4" aria-hidden="true" />
            Retour à l’accueil
          </button>
          <p className="text-xs font-semibold uppercase tracking-[.18em] text-[#8a513e]">
            {mode === "catalogue" ? "Gestion guidée" : "Disponibilité"}
          </p>
          <h1 className="mt-2 text-3xl font-semibold tracking-[-.05em] text-[#1d1d1b] sm:text-4xl">
            {title}
          </h1>
          <p className="mt-3 max-w-2xl leading-7 text-[#625f59]">
            {description}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Badge className="w-fit rounded-full border-0 bg-[#e4f0e5] text-[#37603e]">
            Source : WooCommerce
          </Badge>
          {mode === "catalogue" ? (
            <Button
              onClick={() => setLocation("/catalogue/nouveau")}
              className="rounded-xl bg-[#1d1d1b] text-white hover:bg-[#343330]"
            >
              <Plus className="mr-2 h-4 w-4" aria-hidden="true" />
              Ajouter un plat
            </Button>
          ) : null}
        </div>
      </header>

      {connectedStores.length > 1 ? (
        <div className="mb-5 flex flex-wrap items-center gap-3 rounded-2xl border border-[#1d1d1b]/10 bg-white p-4">
          <label
            htmlFor="store-select"
            className="text-sm font-semibold text-[#1d1d1b]"
          >
            Magasin
          </label>
          <select
            id="store-select"
            value={storeId ?? ""}
            onChange={event => setStoreId(Number(event.target.value))}
            className="rounded-xl border border-[#1d1d1b]/15 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#eb5f2a]"
          >
            <option value="" disabled>
              Choisir un magasin
            </option>
            {connectedStores.map(store => (
              <option key={store.id} value={store.id}>
                {store.name}
              </option>
            ))}
          </select>
        </div>
      ) : null}

      {stores.isLoading ? (
        <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
          <p className="text-sm text-[#625f59]">
            Vérification de la connexion au magasin…
          </p>
        </div>
      ) : null}
      {!stores.isLoading && connectedStores.length === 0 ? (
        <section className="grid gap-5 lg:grid-cols-[1.1fr_.9fr]">
          <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
            <div className="grid h-11 w-11 place-items-center rounded-2xl bg-[#f6e1d6] text-[#a9441d]">
              <PackageSearch className="h-5 w-5" aria-hidden="true" />
            </div>
            <h2 className="mt-6 text-xl font-semibold tracking-tight text-[#1d1d1b]">
              Connectez votre magasin pour voir les données
            </h2>
            <p className="mt-3 max-w-xl text-sm leading-6 text-[#625f59]">
              Keleva ne crée aucune donnée simulée. Après une connexion
              WooCommerce validée, les produits et stocks apparaîtront ici.
            </p>
            <Button
              onClick={() => setLocation("/connexion")}
              className="mt-6 rounded-xl bg-[#1d1d1b] text-white hover:bg-[#343330]"
            >
              Configurer une connexion
            </Button>
          </div>
          <aside className="rounded-[1.75rem] bg-[#1d1d1b] p-7 text-[#f8f4ec]">
            <ShieldCheck
              className="h-6 w-6 text-[#f8a254]"
              aria-hidden="true"
            />
            <h2 className="mt-6 text-lg font-semibold">
              Aucun accès dangereux
            </h2>
            <p className="mt-3 text-sm leading-6 text-[#d9d4ca]">
              Les clés WooCommerce restent chiffrées côté serveur. Les
              opérations d’écriture nécessitent une confirmation et un rôle
              autorisé.
            </p>
          </aside>
        </section>
      ) : null}

      {storeId !== null && products.isLoading ? (
        <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
          <div className="flex items-center gap-3 text-sm text-[#625f59]">
            <RefreshCw className="h-4 w-4 animate-spin" aria-hidden="true" />
            Lecture authentifiée de WooCommerce…
          </div>
        </div>
      ) : null}
      {storeId !== null && products.error ? (
        <div
          role="alert"
          className="rounded-[1.75rem] border border-[#d7aaa0] bg-[#fff5f2] p-6 text-[#8f2d1d]"
        >
          <div className="flex items-center gap-2 font-semibold">
            <AlertCircle className="h-5 w-5" aria-hidden="true" />
            Impossible de lire WooCommerce
          </div>
          <p className="mt-2 text-sm leading-6">{products.error.message}</p>
        </div>
      ) : null}
      {storeId !== null && products.data && products.data.length === 0 ? (
        <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
          <h2 className="text-xl font-semibold text-[#1d1d1b]">
            Aucun produit synchronisé
          </h2>
          <p className="mt-2 text-sm leading-6 text-[#625f59]">
            Ajoutez un produit depuis l’assistant Keleva Manager dans le
            prochain lot, ou depuis WooCommerce si vous préférez conserver ce
            système comme source de vérité.
          </p>
        </div>
      ) : null}
      {storeId !== null && products.data && products.data.length > 0 ? (
        <section className="overflow-hidden rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white">
          <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[#1d1d1b]/10 p-5 sm:p-7">
            <div>
              <h2 className="font-semibold text-[#1d1d1b]">
                {products.data.length} produits récents
              </h2>
              <p className="mt-1 text-sm text-[#625f59]">
                Les données affichées viennent directement de WooCommerce.
              </p>
            </div>
            <Badge className="rounded-full border-0 bg-[#f4f0e8] text-[#5d574e]">
              Lecture contrôlée
            </Badge>
          </div>
          <div
            tabIndex={0}
            role="region"
            aria-label="Liste des produits défilable horizontalement"
            className="overflow-x-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#eb5f2a]"
          >
            <table className="w-full min-w-[680px] text-left text-sm">
              <thead className="bg-[#faf8f3] text-xs uppercase tracking-[.12em] text-[#5d574e]">
                <tr>
                  <th scope="col" className="px-5 py-4 sm:px-7">
                    Produit
                  </th>
                  <th scope="col" className="px-5 py-4">
                    Prix
                  </th>
                  <th scope="col" className="px-5 py-4">
                    Statut
                  </th>
                  <th scope="col" className="px-5 py-4">
                    Stock
                  </th>
                  {mode === "stock" ? (
                    <th scope="col" className="px-5 py-4">
                      Action
                    </th>
                  ) : null}
                </tr>
              </thead>
              <tbody className="divide-y divide-[#1d1d1b]/8">
                {products.data.map(product => (
                  <tr key={product.id} className="align-middle">
                    <td className="px-5 py-4 sm:px-7">
                      <div className="flex items-center gap-3">
                        <div className="h-12 w-12 overflow-hidden rounded-xl bg-[#f4f0e8]">
                          {product.images?.[0]?.src ? (
                            <img
                              src={product.images[0].src}
                              alt=""
                              className="h-full w-full object-cover"
                            />
                          ) : null}
                        </div>
                        <div>
                          <p className="font-semibold text-[#1d1d1b]">
                            {product.name}
                          </p>
                          <p className="mt-1 text-xs text-[#77736b]">
                            {product.type}
                            {product.sku ? ` · ${product.sku}` : ""}
                          </p>
                        </div>
                      </div>
                    </td>
                    <td className="px-5 py-4 font-semibold text-[#1d1d1b]">
                      {product.price || product.regular_price || "—"}
                    </td>
                    <td className="px-5 py-4">
                      <Badge
                        className={
                          product.status === "publish"
                            ? "border-0 bg-[#e4f0e5] text-[#37603e]"
                            : "border-0 bg-[#f4f0e8] text-[#5d574e]"
                        }
                      >
                        {product.status}
                      </Badge>
                    </td>
                    <td className="px-5 py-4 text-[#625f59]">
                      {product.stock_quantity ??
                        (product.stock_status === "instock"
                          ? "En stock"
                          : "Rupture")}
                    </td>
                    {mode === "stock" ? (
                      <td className="px-5 py-4">
                        <div className="flex items-center gap-2">
                          <Input
                            aria-label={`Nouvelle quantité pour ${product.name}`}
                            value={quantityFor(
                              product.id,
                              product.stock_quantity
                            )}
                            onChange={event =>
                              setQuantities(current => ({
                                ...current,
                                [product.id]: event.target.value,
                              }))
                            }
                            inputMode="numeric"
                            className="h-9 w-24 rounded-lg"
                          />
                          <Button
                            size="sm"
                            onClick={() =>
                              saveStock(product.id, product.stock_quantity)
                            }
                            disabled={setStock.isPending}
                            className="rounded-lg bg-[#1d1d1b] text-white hover:bg-[#343330]"
                          >
                            <Check
                              className="mr-1.5 h-3.5 w-3.5"
                              aria-hidden="true"
                            />
                            Enregistrer
                          </Button>
                        </div>
                      </td>
                    ) : null}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      ) : null}
    </div>
  );
}
