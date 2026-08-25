import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { trpc } from "@/lib/trpc";
import {
  AlertCircle,
  ArrowLeft,
  CheckCircle2,
  RefreshCw,
  ShieldCheck,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { useLocation } from "wouter";
import { toast } from "sonner";

const kinds = ["full", "products", "orders", "stock", "media"] as const;
const kindLabels: Record<string, string> = {
  full: "Tout le magasin",
  products: "Catalogue",
  orders: "Commandes",
  stock: "Stock",
  media: "Médias",
};

export default function SyncPage() {
  const [, setLocation] = useLocation();
  const stores = trpc.stores.list.useQuery();
  const connectedStores = useMemo(
    () => (stores.data ?? []).filter(store => store.status === "connected"),
    [stores.data]
  );
  const [storeId, setStoreId] = useState<number | null>(null);
  const [kind, setKind] = useState<(typeof kinds)[number]>("full");
  useEffect(() => {
    if (storeId === null && connectedStores[0])
      setStoreId(connectedStores[0].id);
  }, [connectedStores, storeId]);
  const syncs = trpc.sync.list.useQuery(
    { storeId: storeId ?? 0 },
    { enabled: storeId !== null }
  );
  const webhooks = trpc.sync.webhooks.useQuery(
    { storeId: storeId ?? 0 },
    { enabled: storeId !== null }
  );
  const utils = trpc.useUtils();
  const enqueue = trpc.sync.enqueue.useMutation({
    onSuccess: async () => {
      if (storeId !== null) {
        await utils.sync.list.invalidate({ storeId });
        await utils.sync.webhooks.invalidate({ storeId });
      }
      toast.success("Synchronisation placée dans la file.");
    },
    onError: error => toast.error(error.message),
  });

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
            Connectivité
          </p>
          <h1 className="mt-2 text-3xl font-semibold tracking-[-.05em] text-[#1d1d1b] sm:text-4xl">
            Synchronisation observable
          </h1>
          <p className="mt-3 max-w-2xl leading-7 text-[#625f59]">
            Lancez un traitement seulement quand le magasin est connecté et
            voyez son statut, sa progression et ses erreurs.
          </p>
        </div>
        <Badge className="w-fit rounded-full border-0 bg-[#e5efe5] text-[#35613b]">
          Traitements contrôlés
        </Badge>
      </header>
      {connectedStores.length === 0 && !stores.isLoading ? (
        <section className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
          <h2 className="text-xl font-semibold">
            Connectez votre magasin avant de synchroniser
          </h2>
          <p className="mt-3 text-sm leading-6 text-[#625f59]">
            Aucune synchronisation n’est simulée. Le bouton apparaîtra après
            validation de la connexion.
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
        <>
          <section className="grid gap-5 lg:grid-cols-[1.1fr_.9fr]">
            <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-5 sm:p-7">
              <div className="flex items-start gap-3">
                <span className="grid h-10 w-10 place-items-center rounded-2xl bg-[#e5efe5] text-[#35613b]">
                  <RefreshCw className="h-5 w-5" aria-hidden="true" />
                </span>
                <div>
                  <h2 className="font-semibold">Nouvelle synchronisation</h2>
                  <p className="mt-1 text-sm leading-5 text-[#625f59]">
                    Choisissez une zone. Le serveur gère la file et
                    l’idempotence.
                  </p>
                </div>
              </div>
              <div className="mt-6 grid gap-2">
                <label htmlFor="sync-kind" className="text-sm font-semibold">
                  Données à synchroniser
                </label>
                <select
                  id="sync-kind"
                  value={kind}
                  onChange={event =>
                    setKind(event.target.value as (typeof kinds)[number])
                  }
                  className="h-10 rounded-xl border border-[#1d1d1b]/15 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#eb5f2a]"
                >
                  {kinds.map(value => (
                    <option key={value} value={value}>
                      {kindLabels[value]}
                    </option>
                  ))}
                </select>
              </div>
              <Button
                onClick={() =>
                  storeId !== null && enqueue.mutate({ storeId, kind })
                }
                disabled={enqueue.isPending}
                className="mt-6 rounded-xl bg-[#1d1d1b] text-white hover:bg-[#343330]"
              >
                <RefreshCw
                  className={`mr-2 h-4 w-4 ${enqueue.isPending ? "animate-spin" : ""}`}
                  aria-hidden="true"
                />
                {enqueue.isPending
                  ? "Mise en file…"
                  : "Lancer la synchronisation"}
              </Button>
            </div>
            <aside className="rounded-[1.75rem] bg-[#1d1d1b] p-7 text-[#f8f4ec]">
              <ShieldCheck
                className="h-6 w-6 text-[#f8a254]"
                aria-hidden="true"
              />
              <h2 className="mt-6 text-lg font-semibold">Garde-fous</h2>
              <p className="mt-3 text-sm leading-6 text-[#d9d4ca]">
                Les reprises sont limitées par rôle et par débit. Les erreurs
                restent visibles sans révéler les credentials.
              </p>
            </aside>
          </section>
          <section className="mt-5 grid gap-5 lg:grid-cols-2">
            <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-5 sm:p-7">
              <h2 className="font-semibold">Dernières synchronisations</h2>
              <div className="mt-5 space-y-3">
                {syncs.isLoading ? (
                  <p className="text-sm text-[#625f59]">
                    Lecture des traitements…
                  </p>
                ) : null}
                {syncs.data?.length === 0 ? (
                  <p className="text-sm leading-6 text-[#625f59]">
                    Aucun traitement enregistré.
                  </p>
                ) : null}
                {syncs.data?.slice(0, 8).map(run => (
                  <div
                    key={run.id}
                    className="rounded-xl border border-[#1d1d1b]/10 p-4"
                  >
                    <div className="flex items-center justify-between gap-3">
                      <span className="text-sm font-semibold">
                        {kindLabels[run.kind] ?? run.kind}
                      </span>
                      <Badge
                        className={
                          run.status === "completed"
                            ? "border-0 bg-[#e4f0e5] text-[#37603e]"
                            : run.status === "failed"
                              ? "border-0 bg-[#fff0ed] text-[#8f2d1d]"
                              : "border-0 bg-[#f4f0e8] text-[#5d574e]"
                        }
                      >
                        {run.status}
                      </Badge>
                    </div>
                    <div className="mt-2 flex items-center justify-between text-xs text-[#77736b]">
                      <span>{run.progress}%</span>
                      <span>
                        {new Date(run.createdAt).toLocaleString("fr-FR")}
                      </span>
                    </div>
                    {run.errorMessage ? (
                      <p className="mt-2 flex gap-2 text-xs leading-5 text-[#8f2d1d]">
                        <AlertCircle
                          className="mt-0.5 h-3.5 w-3.5 shrink-0"
                          aria-hidden="true"
                        />
                        {run.errorMessage}
                      </p>
                    ) : null}
                  </div>
                ))}
              </div>
            </div>
            <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-5 sm:p-7">
              <h2 className="font-semibold">Webhooks reçus</h2>
              <div className="mt-5 space-y-3">
                {webhooks.isLoading ? (
                  <p className="text-sm text-[#625f59]">
                    Lecture des événements…
                  </p>
                ) : null}
                {webhooks.data?.length === 0 ? (
                  <p className="text-sm leading-6 text-[#625f59]">
                    Aucun webhook reçu.
                  </p>
                ) : null}
                {webhooks.data?.slice(0, 8).map(event => (
                  <div
                    key={event.deliveryId}
                    className="rounded-xl border border-[#1d1d1b]/10 p-4"
                  >
                    <div className="flex items-center justify-between gap-3">
                      <span className="truncate text-sm font-semibold">
                        {event.topic}
                      </span>
                      <Badge
                        className={
                          event.signatureVerified
                            ? "border-0 bg-[#e4f0e5] text-[#37603e]"
                            : "border-0 bg-[#fff0ed] text-[#8f2d1d]"
                        }
                      >
                        {event.signatureVerified ? "Signé" : "Refusé"}
                      </Badge>
                    </div>
                    <p className="mt-2 text-xs text-[#77736b]">
                      {event.status} ·{" "}
                      {event.resourceId ?? "Ressource inconnue"}
                    </p>
                    {event.processingError ? (
                      <p className="mt-2 text-xs leading-5 text-[#8f2d1d]">
                        {event.processingError}
                      </p>
                    ) : null}
                  </div>
                ))}
              </div>
            </div>
          </section>
        </>
      ) : null}
    </div>
  );
}
