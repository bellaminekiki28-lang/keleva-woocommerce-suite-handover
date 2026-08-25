import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { trpc } from "@/lib/trpc";
import {
  AlertCircle,
  ArrowLeft,
  Check,
  ClipboardList,
  RefreshCw,
  ShieldCheck,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { useLocation } from "wouter";
import { toast } from "sonner";

const statusOptions = [
  "pending",
  "on-hold",
  "processing",
  "completed",
  "cancelled",
] as const;
type OrderStatus = (typeof statusOptions)[number];

function statusLabel(status: string) {
  const labels: Record<string, string> = {
    pending: "À confirmer",
    processing: "En préparation",
    completed: "Terminée",
    cancelled: "Annulée",
    failed: "Échec",
    "on-hold": "En attente",
    refunded: "Remboursée",
  };
  return labels[status] ?? status;
}

export default function OrdersPage() {
  const [, setLocation] = useLocation();
  const stores = trpc.stores.list.useQuery();
  const connectedStores = useMemo(
    () => (stores.data ?? []).filter(store => store.status === "connected"),
    [stores.data]
  );
  const [storeId, setStoreId] = useState<number | null>(null);
  const [pendingStatuses, setPendingStatuses] = useState<
    Record<number, OrderStatus>
  >({});
  useEffect(() => {
    if (storeId === null && connectedStores[0])
      setStoreId(connectedStores[0].id);
  }, [connectedStores, storeId]);
  const orders = trpc.operations.orders.useQuery(
    { storeId: storeId ?? 0, limit: 50 },
    { enabled: storeId !== null }
  );
  const utils = trpc.useUtils();
  const updateStatus = trpc.operations.setOrderStatus.useMutation({
    onSuccess: async () => {
      if (storeId !== null)
        await utils.operations.orders.invalidate({ storeId, limit: 50 });
      toast.success("Statut de commande mis à jour et journalisé.");
    },
    onError: error => toast.error(error.message),
  });

  function selectedStatus(orderId: number, current: string): OrderStatus {
    const pending = pendingStatuses[orderId];
    return (
      pending ??
      (statusOptions.includes(current as OrderStatus)
        ? (current as OrderStatus)
        : "pending")
    );
  }

  function saveStatus(orderId: number, current: string) {
    if (storeId === null) return;
    const status = selectedStatus(orderId, current);
    if (status === current) {
      toast.info("Aucun changement à enregistrer.");
      return;
    }
    updateStatus.mutate({
      storeId,
      orderId,
      status,
      confirm: true,
      reason: "Transition confirmée depuis Keleva Manager",
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
            Opérations
          </p>
          <h1 className="mt-2 text-3xl font-semibold tracking-[-.05em] text-[#1d1d1b] sm:text-4xl">
            Commandes à traiter
          </h1>
          <p className="mt-3 max-w-2xl leading-7 text-[#625f59]">
            Consultez l’état des commandes et appliquez une transition
            uniquement après confirmation.
          </p>
        </div>
        <Badge className="w-fit rounded-full border-0 bg-[#e7edf4] text-[#385a78]">
          Source : WooCommerce
        </Badge>
      </header>
      {connectedStores.length > 1 ? (
        <div className="mb-5 flex flex-wrap items-center gap-3 rounded-2xl border border-[#1d1d1b]/10 bg-white p-4">
          <label htmlFor="order-store-select" className="text-sm font-semibold">
            Magasin
          </label>
          <select
            id="order-store-select"
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
            <div className="grid h-11 w-11 place-items-center rounded-2xl bg-[#e7edf4] text-[#385a78]">
              <ClipboardList className="h-5 w-5" aria-hidden="true" />
            </div>
            <h2 className="mt-6 text-xl font-semibold tracking-tight">
              Connectez votre magasin pour voir les commandes
            </h2>
            <p className="mt-3 max-w-xl text-sm leading-6 text-[#625f59]">
              Aucune commande n’est inventée. La liste apparaîtra après une
              connexion WooCommerce validée.
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
              Opérations contrôlées
            </h2>
            <p className="mt-3 text-sm leading-6 text-[#d9d4ca]">
              Les transitions sont limitées aux rôles autorisés, confirmées côté
              serveur et ajoutées au journal.
            </p>
          </aside>
        </section>
      ) : null}
      {storeId !== null && orders.isLoading ? (
        <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
          <div className="flex items-center gap-3 text-sm text-[#625f59]">
            <RefreshCw className="h-4 w-4 animate-spin" aria-hidden="true" />
            Lecture authentifiée des commandes…
          </div>
        </div>
      ) : null}
      {storeId !== null && orders.error ? (
        <div
          role="alert"
          className="rounded-[1.75rem] border border-[#d7aaa0] bg-[#fff5f2] p-6 text-[#8f2d1d]"
        >
          <div className="flex items-center gap-2 font-semibold">
            <AlertCircle className="h-5 w-5" aria-hidden="true" />
            Impossible de lire les commandes
          </div>
          <p className="mt-2 text-sm leading-6">{orders.error.message}</p>
        </div>
      ) : null}
      {storeId !== null && orders.data && orders.data.length === 0 ? (
        <div className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-7">
          <h2 className="text-xl font-semibold">Aucune commande récente</h2>
          <p className="mt-2 text-sm leading-6 text-[#625f59]">
            Les nouvelles commandes apparaîtront ici après synchronisation.
          </p>
        </div>
      ) : null}
      {storeId !== null && orders.data && orders.data.length > 0 ? (
        <section className="overflow-hidden rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white">
          <div className="border-b border-[#1d1d1b]/10 p-5 sm:p-7">
            <h2 className="font-semibold">
              {orders.data.length} commandes récentes
            </h2>
            <p className="mt-1 text-sm text-[#625f59]">
              Les transitions nécessitent un clic de confirmation et sont
              journalisées.
            </p>
          </div>
          <div
            tabIndex={0}
            role="region"
            aria-label="Liste des commandes défilable horizontalement"
            className="overflow-x-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#eb5f2a]"
          >
            <table className="w-full min-w-[920px] text-left text-sm">
              <thead className="bg-[#faf8f3] text-xs uppercase tracking-[.12em] text-[#5d574e]">
                <tr>
                  <th scope="col" className="px-5 py-4 sm:px-7">
                    Commande
                  </th>
                  <th scope="col" className="px-5 py-4">
                    Client
                  </th>
                  <th scope="col" className="px-5 py-4">
                    Date
                  </th>
                  <th scope="col" className="px-5 py-4">
                    Statut actuel
                  </th>
                  <th scope="col" className="px-5 py-4">
                    Nouveau statut
                  </th>
                  <th scope="col" className="px-5 py-4">
                    Total
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#1d1d1b]/8">
                {orders.data.map(order => (
                  <tr key={order.id} className="align-middle">
                    <td className="px-5 py-4 font-semibold sm:px-7">
                      #{order.number || order.id}
                      <p className="mt-1 text-xs font-normal text-[#77736b]">
                        {order.id} · {order.billing?.email ?? "Client invité"}
                      </p>
                    </td>
                    <td className="px-5 py-4 text-[#625f59]">
                      {[order.billing?.first_name, order.billing?.last_name]
                        .filter(Boolean)
                        .join(" ") || "Client invité"}
                    </td>
                    <td className="px-5 py-4 text-[#625f59]">
                      {order.date_created_gmt
                        ? new Date(
                            `${order.date_created_gmt}Z`
                          ).toLocaleDateString("fr-FR")
                        : "—"}
                    </td>
                    <td className="px-5 py-4">
                      <Badge className="border-0 bg-[#f4f0e8] text-[#5d574e]">
                        {statusLabel(order.status)}
                      </Badge>
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-2">
                        <select
                          aria-label={`Nouveau statut pour la commande ${order.number || order.id}`}
                          value={selectedStatus(order.id, order.status)}
                          onChange={event =>
                            setPendingStatuses(current => ({
                              ...current,
                              [order.id]: event.target.value as OrderStatus,
                            }))
                          }
                          className="h-9 rounded-lg border border-[#1d1d1b]/15 bg-white px-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#eb5f2a]"
                        >
                          {statusOptions.map(status => (
                            <option key={status} value={status}>
                              {statusLabel(status)}
                            </option>
                          ))}
                        </select>
                        <Button
                          size="sm"
                          onClick={() => saveStatus(order.id, order.status)}
                          disabled={updateStatus.isPending}
                          className="rounded-lg bg-[#1d1d1b] text-white hover:bg-[#343330]"
                        >
                          <Check
                            className="mr-1.5 h-3.5 w-3.5"
                            aria-hidden="true"
                          />
                          Valider
                        </Button>
                      </div>
                    </td>
                    <td className="px-5 py-4 font-semibold">
                      {order.total} {order.currency}
                    </td>
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
