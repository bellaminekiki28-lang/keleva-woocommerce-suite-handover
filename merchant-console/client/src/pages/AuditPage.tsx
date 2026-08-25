import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { trpc } from "@/lib/trpc";
import {
  AlertCircle,
  ArrowLeft,
  ClipboardCheck,
  ShieldCheck,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { useLocation } from "wouter";

const actionLabels: Record<string, string> = {
  "product.created": "Produit créé",
  "product.stock_updated": "Stock modifié",
  "product.status_updated": "Statut produit modifié",
  "order.status_updated": "Statut commande modifié",
  "media.retry_requested": "Reprise média demandée",
  merchant_session_started: "Session ouverte",
  merchant_session_ended: "Session fermée",
};

export default function AuditPage() {
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
  const audit = trpc.audit.list.useQuery(
    { storeId: storeId ?? 0 },
    { enabled: storeId !== null }
  );

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
            Traçabilité
          </p>
          <h1 className="mt-2 text-3xl font-semibold tracking-[-.05em] text-[#1d1d1b] sm:text-4xl">
            Journal d’audit
          </h1>
          <p className="mt-3 max-w-2xl leading-7 text-[#625f59]">
            Retrouvez qui a fait quoi, quand et sur quelle ressource, sans
            afficher de secret ni de donnée carte.
          </p>
        </div>
        <Badge className="w-fit rounded-full border-0 bg-[#e4f0e5] text-[#37603e]">
          Lecture protégée
        </Badge>
      </header>
      {connectedStores.length === 0 && !stores.isLoading ? (
        <section className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
          <div className="flex items-center gap-3">
            <ClipboardCheck
              className="h-6 w-6 text-[#35613b]"
              aria-hidden="true"
            />
            <h2 className="text-xl font-semibold">Aucun magasin connecté</h2>
          </div>
          <p className="mt-3 text-sm leading-6 text-[#625f59]">
            Le journal apparaîtra après une connexion WooCommerce validée et la
            première action métier.
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
        <section className="overflow-hidden rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white">
          <div className="flex items-center gap-3 border-b border-[#1d1d1b]/10 p-5 sm:p-7">
            <ShieldCheck
              className="h-5 w-5 text-[#35613b]"
              aria-hidden="true"
            />
            <div>
              <h2 className="font-semibold">Dernières actions</h2>
              <p className="mt-1 text-sm text-[#625f59]">
                Les événements sont conservés côté serveur.
              </p>
            </div>
          </div>
          {audit.isLoading ? (
            <p className="p-7 text-sm text-[#625f59]">Lecture du journal…</p>
          ) : null}
          {audit.error ? (
            <div
              role="alert"
              className="m-5 rounded-xl bg-[#fff5f2] p-4 text-sm text-[#8f2d1d]"
            >
              <div className="flex items-center gap-2 font-semibold">
                <AlertCircle className="h-4 w-4" aria-hidden="true" />
                Le journal n’est pas disponible
              </div>
              <p className="mt-1">{audit.error.message}</p>
            </div>
          ) : null}
          {audit.data?.length === 0 ? (
            <p className="p-7 text-sm leading-6 text-[#625f59]">
              Aucune action enregistrée.
            </p>
          ) : null}
          {audit.data && audit.data.length > 0 ? (
            <div
              tabIndex={0}
              role="region"
              aria-label="Journal d’audit défilable horizontalement"
              className="overflow-x-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#eb5f2a]"
            >
              <table className="w-full min-w-[720px] text-left text-sm">
                <thead className="bg-[#faf8f3] text-xs uppercase tracking-[.12em] text-[#5d574e]">
                  <tr>
                    <th scope="col" className="px-5 py-4 sm:px-7">
                      Action
                    </th>
                    <th scope="col" className="px-5 py-4">
                      Ressource
                    </th>
                    <th scope="col" className="px-5 py-4">
                      Résultat
                    </th>
                    <th scope="col" className="px-5 py-4">
                      Date
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#1d1d1b]/8">
                  {audit.data.map(event => (
                    <tr key={event.id}>
                      <td className="px-5 py-4 font-semibold sm:px-7">
                        {actionLabels[event.action] ?? event.action}
                        <p className="mt-1 text-xs font-normal text-[#77736b]">
                          Utilisateur #{event.actorUserId ?? "système"}
                        </p>
                      </td>
                      <td className="px-5 py-4 text-[#625f59]">
                        {event.targetType}{" "}
                        {event.targetId ? `#${event.targetId}` : ""}
                      </td>
                      <td className="px-5 py-4">
                        <Badge
                          className={
                            event.outcome === "success"
                              ? "border-0 bg-[#e4f0e5] text-[#37603e]"
                              : "border-0 bg-[#fff0ed] text-[#8f2d1d]"
                          }
                        >
                          {event.outcome === "success"
                            ? "Réussi"
                            : event.outcome}
                        </Badge>
                        {event.reason ? (
                          <p className="mt-1 max-w-xs text-xs text-[#77736b]">
                            {event.reason}
                          </p>
                        ) : null}
                      </td>
                      <td className="px-5 py-4 text-[#625f59]">
                        {new Date(event.createdAt).toLocaleString("fr-FR")}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : null}
        </section>
      ) : null}
    </div>
  );
}
