import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { trpc } from "@/lib/trpc";
import {
  ArrowLeft,
  CheckCircle2,
  PackagePlus,
  ShieldCheck,
} from "lucide-react";
import { FormEvent, useEffect, useMemo, useState } from "react";
import { useLocation } from "wouter";
import { toast } from "sonner";

export default function CreateProductPage() {
  const [, setLocation] = useLocation();
  const stores = trpc.stores.list.useQuery();
  const connectedStores = useMemo(
    () => (stores.data ?? []).filter(store => store.status === "connected"),
    [stores.data]
  );
  const [storeId, setStoreId] = useState<number | null>(null);
  const [name, setName] = useState("");
  const [regularPrice, setRegularPrice] = useState("");
  const [stockQuantity, setStockQuantity] = useState("0");
  const [publish, setPublish] = useState(false);
  useEffect(() => {
    if (storeId === null && connectedStores[0])
      setStoreId(connectedStores[0].id);
  }, [connectedStores, storeId]);
  const createProduct = trpc.operations.createProduct.useMutation({
    onSuccess: product => {
      toast.success(`${product.name} a été enregistré.`);
      setLocation("/catalogue");
    },
    onError: error => toast.error(error.message),
  });

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (storeId === null) return;
    createProduct.mutate({
      storeId,
      name,
      regularPrice,
      stockQuantity: Number(stockQuantity),
      publish,
      confirm: true,
      reason: "Création depuis l’assistant Keleva Manager",
    });
  }

  return (
    <div className="mx-auto max-w-5xl">
      <header className="mb-7">
        <button
          type="button"
          onClick={() => setLocation("/catalogue")}
          className="mb-4 inline-flex items-center text-sm font-semibold text-[#6d5a50] hover:text-[#1d1d1b] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#eb5f2a] focus-visible:ring-offset-2"
        >
          <ArrowLeft className="mr-2 h-4 w-4" aria-hidden="true" />
          Retour au catalogue
        </button>
        <div className="flex flex-wrap items-center gap-3">
          <p className="text-xs font-semibold uppercase tracking-[.18em] text-[#8a513e]">
            Assistant en 4 étapes · Étape 1
          </p>
          <Badge className="rounded-full border-0 bg-[#e5efe5] text-[#35613b]">
            Brouillon par défaut
          </Badge>
        </div>
        <h1 className="mt-2 text-3xl font-semibold tracking-[-.05em] text-[#1d1d1b] sm:text-4xl">
          Ajouter un plat
        </h1>
        <p className="mt-3 max-w-2xl leading-7 text-[#625f59]">
          Commencez avec les informations essentielles. Vous pourrez compléter
          la photo et les détails avancés dans l’étape suivante.
        </p>
      </header>
      {connectedStores.length === 0 && !stores.isLoading ? (
        <section className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
          <div className="grid h-11 w-11 place-items-center rounded-2xl bg-[#f6e1d6] text-[#a9441d]">
            <PackagePlus className="h-5 w-5" aria-hidden="true" />
          </div>
          <h2 className="mt-6 text-xl font-semibold">
            Connectez votre magasin avant de créer un plat
          </h2>
          <p className="mt-3 max-w-xl text-sm leading-6 text-[#625f59]">
            Pour éviter toute fausse donnée, l’assistant n’est disponible
            qu’après une connexion WooCommerce vérifiée côté serveur.
          </p>
          <Button
            onClick={() => setLocation("/connexion")}
            className="mt-6 rounded-xl bg-[#1d1d1b] text-white hover:bg-[#343330]"
          >
            Configurer une connexion
          </Button>
        </section>
      ) : null}
      {connectedStores.length > 0 ? (
        <div className="grid gap-5 lg:grid-cols-[1.1fr_.9fr]">
          <form
            onSubmit={submit}
            className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-5 sm:p-7"
          >
            <div className="mb-7 flex items-start gap-3">
              <span className="grid h-10 w-10 place-items-center rounded-2xl bg-[#f6e1d6] text-[#a9441d]">
                <PackagePlus className="h-5 w-5" aria-hidden="true" />
              </span>
              <div>
                <h2 className="font-semibold">Informations essentielles</h2>
                <p className="mt-1 text-sm leading-5 text-[#625f59]">
                  Les champs sont contrôlés avant d’être envoyés au serveur.
                </p>
              </div>
            </div>
            <div className="grid gap-5">
              {connectedStores.length > 1 ? (
                <div className="grid gap-2">
                  <Label htmlFor="create-store">Magasin</Label>
                  <select
                    id="create-store"
                    value={storeId ?? ""}
                    onChange={event => setStoreId(Number(event.target.value))}
                    className="h-10 rounded-xl border border-[#1d1d1b]/15 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#eb5f2a]"
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
              <div className="grid gap-2">
                <Label htmlFor="product-name">Nom du plat</Label>
                <Input
                  id="product-name"
                  value={name}
                  onChange={event => setName(event.target.value)}
                  maxLength={180}
                  required
                  placeholder="Ex. Brunch du dimanche"
                />
              </div>
              <div className="grid gap-2">
                <Label htmlFor="product-price">Prix</Label>
                <Input
                  id="product-price"
                  value={regularPrice}
                  onChange={event => setRegularPrice(event.target.value)}
                  inputMode="decimal"
                  required
                  placeholder="Ex. 49.00"
                  aria-describedby="price-help"
                />
                <p id="price-help" className="text-xs text-[#77736b]">
                  Utilisez le montant affiché dans la devise de votre magasin.
                </p>
              </div>
              <div className="grid gap-2">
                <Label htmlFor="product-stock">Disponibilité initiale</Label>
                <Input
                  id="product-stock"
                  type="number"
                  min="0"
                  step="1"
                  value={stockQuantity}
                  onChange={event => setStockQuantity(event.target.value)}
                  required
                />
              </div>
              <label className="flex items-start gap-3 rounded-xl border border-[#1d1d1b]/10 bg-[#faf8f3] p-4 text-sm">
                <input
                  type="checkbox"
                  checked={publish}
                  onChange={event => setPublish(event.target.checked)}
                  className="mt-0.5 h-4 w-4 accent-[#eb5f2a]"
                />
                <span>
                  <span className="block font-semibold">
                    Publier immédiatement
                  </span>
                  <span className="mt-1 block leading-5 text-[#625f59]">
                    Laissez décoché pour créer un brouillon et vérifier le
                    résultat avant publication.
                  </span>
                </span>
              </label>
            </div>
            <div className="mt-7 flex flex-wrap gap-3">
              <Button
                type="submit"
                disabled={createProduct.isPending}
                className="rounded-xl bg-[#1d1d1b] text-white hover:bg-[#343330]"
              >
                {createProduct.isPending
                  ? "Enregistrement sécurisé…"
                  : publish
                    ? "Créer et publier"
                    : "Créer le brouillon"}
                <CheckCircle2 className="ml-2 h-4 w-4" aria-hidden="true" />
              </Button>
              <Button
                type="button"
                variant="outline"
                onClick={() => setLocation("/catalogue")}
                className="rounded-xl"
              >
                Annuler
              </Button>
            </div>
          </form>
          <aside className="rounded-[1.75rem] bg-[#1d1d1b] p-7 text-[#f8f4ec]">
            <ShieldCheck
              className="h-6 w-6 text-[#f8a254]"
              aria-hidden="true"
            />
            <h2 className="mt-6 text-lg font-semibold">
              Vos données restent protégées
            </h2>
            <p className="mt-3 text-sm leading-6 text-[#d9d4ca]">
              Le navigateur ne reçoit pas les secrets WooCommerce. La création
              est limitée aux rôles autorisés, confirmée côté serveur et ajoutée
              au journal d’audit.
            </p>
            <div className="mt-7 rounded-2xl bg-white/10 p-4 text-sm leading-6 text-[#e8e1d7]">
              La photo et les options avancées seront ajoutées dans les étapes
              suivantes de l’assistant.
            </div>
          </aside>
        </div>
      ) : null}
    </div>
  );
}
