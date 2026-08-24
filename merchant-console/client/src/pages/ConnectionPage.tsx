import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { trpc } from "@/lib/trpc";
import { CheckCircle2, KeyRound, Link2, ShieldCheck, Unplug } from "lucide-react";
import { FormEvent, useState } from "react";
import { toast } from "sonner";

type ConnectionForm = { name: string; baseUrl: string; consumerKey: string; consumerSecret: string };

const initialForm: ConnectionForm = { name: "", baseUrl: "", consumerKey: "", consumerSecret: "" };

export default function ConnectionPage() {
  const utils = trpc.useUtils();
  const stores = trpc.stores.list.useQuery();
  const [form, setForm] = useState<ConnectionForm>(initialForm);
  const connect = trpc.stores.connect.useMutation({
    onSuccess: () => {
      setForm(initialForm);
      void utils.stores.list.invalidate();
      toast.success("Connexion validée et chiffrée côté serveur.");
    },
    onError: (error) => toast.error(error.message),
  });
  const revoke = trpc.stores.revoke.useMutation({
    onSuccess: () => {
      void utils.stores.list.invalidate();
      toast.success("Connexion révoquée et journalisée.");
    },
    onError: (error) => toast.error(error.message),
  });

  function update<K extends keyof ConnectionForm>(key: K, value: ConnectionForm[K]) {
    setForm((current) => ({ ...current, [key]: value }));
  }

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    connect.mutate(form);
  }

  return (
    <div className="mx-auto max-w-5xl">
      <header className="mb-7"><p className="text-xs font-semibold uppercase tracking-[.18em] text-[#8a513e]">Connexion sécurisée</p><h1 className="mt-2 text-3xl font-semibold tracking-[-.05em] text-[#1d1d1b] sm:text-4xl">Un pont serveur, pas des secrets dans le navigateur.</h1><p className="mt-3 max-w-3xl leading-7 text-[#625f59]">La console vérifie l’accès WooCommerce avant d’enregistrer une connexion. Les identifiants sont chiffrés côté serveur avec AES-256-GCM, ne sont jamais relus dans l’interface et peuvent être révoqués par le propriétaire.</p></header>

      <div className="grid gap-5 lg:grid-cols-[1.1fr_.9fr]">
        <form onSubmit={submit} className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-5 sm:p-7">
          <div className="mb-7 flex items-start gap-3"><span className="grid h-10 w-10 place-items-center rounded-2xl bg-[#f6e1d6] text-[#b4461d]"><KeyRound className="h-5 w-5" /></span><div><h2 className="font-semibold tracking-tight text-[#1d1d1b]">Ajouter un magasin</h2><p className="mt-1 text-sm leading-5 text-[#625f59]">Utilisez une clé WooCommerce limitée au strict nécessaire. La connexion doit pointer vers un HTTPS public à certificat valide.</p></div></div>
          <div className="grid gap-5">
            <div className="grid gap-2"><Label htmlFor="store-name">Nom du magasin</Label><Input id="store-name" value={form.name} onChange={(event) => update("name", event.target.value)} maxLength={160} required placeholder="Ex. Keleva Staging" /></div>
            <div className="grid gap-2"><Label htmlFor="store-url">URL HTTPS WooCommerce</Label><Input id="store-url" type="url" value={form.baseUrl} onChange={(event) => update("baseUrl", event.target.value)} maxLength={2048} required placeholder="https://staging.votre-domaine.tld" autoCapitalize="none" /></div>
            <div className="grid gap-2"><Label htmlFor="consumer-key">Consumer Key</Label><Input id="consumer-key" value={form.consumerKey} onChange={(event) => update("consumerKey", event.target.value)} minLength={8} required autoComplete="off" spellCheck={false} /></div>
            <div className="grid gap-2"><Label htmlFor="consumer-secret">Consumer Secret</Label><Input id="consumer-secret" type="password" value={form.consumerSecret} onChange={(event) => update("consumerSecret", event.target.value)} minLength={8} required autoComplete="new-password" spellCheck={false} /></div>
          </div>
          <Button type="submit" disabled={connect.isPending} className="mt-7 h-11 rounded-xl bg-[#1d1d1b] text-white hover:bg-[#343330]">{connect.isPending ? "Vérification sécurisée…" : "Vérifier et chiffrer la connexion"}<Link2 className="ml-2 h-4 w-4" /></Button>
        </form>

        <aside className="space-y-5">
          <section className="rounded-[1.75rem] bg-[#1d1d1b] p-6 text-[#f8f4ec]"><ShieldCheck className="h-6 w-6 text-[#f8a254]" /><h2 className="mt-6 text-lg font-semibold">Garanties du BFF</h2><p className="mt-2 text-sm leading-6 text-[#d9d4ca]">Les appels de catalogue, commandes, stock et webhooks seront réalisés uniquement par le serveur. Le navigateur ne reçoit ni Consumer Secret, ni secret de webhook.</p><ul className="mt-6 space-y-3 text-sm text-[#d9d4ca]"><li className="flex gap-2"><CheckCircle2 className="mt-0.5 h-4 w-4 text-[#9fc8a5]" />Chiffrement AES-256-GCM validé par test.</li><li className="flex gap-2"><CheckCircle2 className="mt-0.5 h-4 w-4 text-[#9fc8a5]" />Vérification API avant enregistrement.</li><li className="flex gap-2"><CheckCircle2 className="mt-0.5 h-4 w-4 text-[#9fc8a5]" />Révocation et audit prévus côté serveur.</li></ul></section>
          <section className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-5"><div className="flex items-center justify-between"><h2 className="font-semibold tracking-tight">Connexions enregistrées</h2><Badge className="rounded-full border-0 bg-[#f4f0e8] text-[#5d574e]">{stores.data?.length ?? 0}</Badge></div>{stores.isLoading ? <p className="mt-5 text-sm text-[#625f59]">Lecture des connexions autorisées…</p> : null}{stores.data?.length === 0 && !stores.isLoading ? <p className="mt-5 text-sm leading-6 text-[#625f59]">Aucun magasin n’est encore connecté. Les données opérationnelles n’apparaîtront qu’après une synchronisation authentifiée.</p> : null}<div className="mt-4 space-y-3">{stores.data?.map((store) => <div key={store.id} className="rounded-xl border border-[#1d1d1b]/10 p-4"><div className="flex items-start justify-between gap-3"><div className="min-w-0"><p className="truncate text-sm font-semibold text-[#1d1d1b]">{store.name}</p><p className="mt-1 truncate text-xs text-[#625f59]">{store.baseUrl}</p></div><Badge className={store.status === "connected" ? "border-0 bg-[#e4f0e5] text-[#37603e]" : "border-0 bg-[#f6e1d6] text-[#9e3a16]"}>{store.status}</Badge></div>{store.role === "owner" && store.status !== "revoked" ? <Button variant="ghost" size="sm" onClick={() => revoke.mutate({ storeId: store.id })} disabled={revoke.isPending} className="mt-3 h-8 px-0 text-destructive hover:bg-transparent hover:text-destructive"><Unplug className="mr-2 h-3.5 w-3.5" />Révoquer la connexion</Button> : null}</div>)}</div></section>
        </aside>
      </div>
    </div>
  );
}
