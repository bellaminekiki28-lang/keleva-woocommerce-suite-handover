import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { trpc } from "@/lib/trpc";
import {
  AlertCircle,
  ArrowLeft,
  CheckCircle2,
  Image,
  RefreshCw,
  ShieldCheck,
  XCircle,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { useLocation } from "wouter";
import { toast } from "sonner";

const statusLabels: Record<string, string> = {
  uploaded: "Reçu",
  queued: "En attente",
  processing: "Traitement",
  ready: "Prêt",
  failed: "Échec",
};

export default function MediaPage() {
  const [, setLocation] = useLocation();
  const stores = trpc.stores.list.useQuery();
  const connectedStores = useMemo(
    () => (stores.data ?? []).filter(store => store.status === "connected"),
    [stores.data]
  );
  const [storeId, setStoreId] = useState<number | null>(null);
  useEffect(() => {
    if (storeId === null && connectedStores[0])
      setStoreId(connectedStores[0].id);
  }, [connectedStores, storeId]);
  const assets = trpc.media.list.useQuery(
    { storeId: storeId ?? 0 },
    { enabled: storeId !== null }
  );
  const utils = trpc.useUtils();
  const retry = trpc.media.retry.useMutation({
    onSuccess: async () => {
      if (storeId !== null) await utils.media.list.invalidate({ storeId });
      toast.success("Le traitement média a été remis en file.");
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
            Qualité média
          </p>
          <h1 className="mt-2 text-3xl font-semibold tracking-[-.05em] text-[#1d1d1b] sm:text-4xl">
            Photos et variantes
          </h1>
          <p className="mt-3 max-w-2xl leading-7 text-[#625f59]">
            Suivez les fichiers reçus, les variantes responsive et les erreurs
            de traitement sans supprimer un média utilisé par accident.
          </p>
        </div>
        <Badge className="w-fit rounded-full border-0 bg-[#eee8f5] text-[#665080]">
          Contrôle des médias
        </Badge>
      </header>
      {connectedStores.length > 1 ? (
        <div className="mb-5 flex flex-wrap items-center gap-3 rounded-2xl border border-[#1d1d1b]/10 bg-white p-4">
          <label htmlFor="media-store-select" className="text-sm font-semibold">
            Magasin
          </label>
          <select
            id="media-store-select"
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
        <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7 text-sm text-[#625f59]">
          Vérification de la connexion au magasin…
        </div>
      ) : null}
      {!stores.isLoading && connectedStores.length === 0 ? (
        <section className="grid gap-5 lg:grid-cols-[1.1fr_.9fr]">
          <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
            <div className="grid h-11 w-11 place-items-center rounded-2xl bg-[#eee8f5] text-[#665080]">
              <Image className="h-5 w-5" aria-hidden="true" />
            </div>
            <h2 className="mt-6 text-xl font-semibold tracking-tight">
              Connectez votre magasin pour voir les médias
            </h2>
            <p className="mt-3 max-w-xl text-sm leading-6 text-[#625f59]">
              Aucun fichier n’est simulé. Les assets apparaîtront après une
              synchronisation authentifiée.
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
            <h2 className="mt-6 text-lg font-semibold">Suppression prudente</h2>
            <p className="mt-3 text-sm leading-6 text-[#d9d4ca]">
              Le retrait d’une image et la suppression définitive d’un fichier
              sont deux actions différentes. Les références doivent être
              vérifiées avant toute suppression.
            </p>
          </aside>
        </section>
      ) : null}
      {storeId !== null && assets.isLoading ? (
        <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
          <div className="flex items-center gap-3 text-sm text-[#625f59]">
            <RefreshCw className="h-4 w-4 animate-spin" aria-hidden="true" />
            Lecture des assets…
          </div>
        </div>
      ) : null}
      {storeId !== null && assets.error ? (
        <div
          role="alert"
          className="rounded-[1.75rem] border border-[#d7aaa0] bg-[#fff5f2] p-6 text-[#8f2d1d]"
        >
          <div className="flex items-center gap-2 font-semibold">
            <AlertCircle className="h-5 w-5" aria-hidden="true" />
            Impossible de lire les médias
          </div>
          <p className="mt-2 text-sm leading-6">{assets.error.message}</p>
        </div>
      ) : null}
      {storeId !== null && assets.data && assets.data.length === 0 ? (
        <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
          <h2 className="text-xl font-semibold">Aucun asset synchronisé</h2>
          <p className="mt-2 text-sm leading-6 text-[#625f59]">
            Les fichiers importés et leurs variantes seront visibles après le
            prochain traitement.
          </p>
        </div>
      ) : null}
      {storeId !== null && assets.data && assets.data.length > 0 ? (
        <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {assets.data.map(asset => (
            <article
              key={asset.id}
              className="overflow-hidden rounded-[1.5rem] border border-[#1d1d1b]/10 bg-white"
            >
              <div className="aspect-[4/3] bg-[#f4f0e8]">
                {asset.originalUrl ? (
                  <img
                    src={asset.originalUrl}
                    alt=""
                    className="h-full w-full object-cover"
                  />
                ) : null}
              </div>
              <div className="p-5">
                <div className="flex items-center justify-between gap-3">
                  <Badge
                    className={
                      asset.status === "ready"
                        ? "border-0 bg-[#e4f0e5] text-[#37603e]"
                        : asset.status === "failed"
                          ? "border-0 bg-[#fff0ed] text-[#8f2d1d]"
                          : "border-0 bg-[#f4f0e8] text-[#5d574e]"
                    }
                  >
                    {statusLabels[asset.status] ?? asset.status}
                  </Badge>
                  <span className="text-xs text-[#77736b]">#{asset.id}</span>
                </div>
                <p className="mt-3 truncate text-sm font-semibold text-[#1d1d1b]">
                  {asset.originalStorageKey}
                </p>
                {asset.errorMessage ? (
                  <p className="mt-2 flex gap-2 text-xs leading-5 text-[#8f2d1d]">
                    <XCircle
                      className="mt-0.5 h-3.5 w-3.5 shrink-0"
                      aria-hidden="true"
                    />
                    {asset.errorMessage}
                  </p>
                ) : null}
                {asset.status === "failed" ? (
                  <Button
                    size="sm"
                    onClick={() =>
                      storeId !== null &&
                      retry.mutate({
                        storeId,
                        mediaId: asset.id,
                        confirm: true,
                      })
                    }
                    disabled={retry.isPending}
                    className="mt-4 rounded-lg bg-[#1d1d1b] text-white hover:bg-[#343330]"
                  >
                    <RefreshCw
                      className="mr-2 h-3.5 w-3.5"
                      aria-hidden="true"
                    />
                    Réessayer
                  </Button>
                ) : (
                  <p className="mt-4 flex items-center gap-2 text-xs text-[#625f59]">
                    <CheckCircle2
                      className="h-3.5 w-3.5 text-[#37603e]"
                      aria-hidden="true"
                    />
                    Aucune action requise
                  </p>
                )}
              </div>
            </article>
          ))}
        </section>
      ) : null}
    </div>
  );
}
