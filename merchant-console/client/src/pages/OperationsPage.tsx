import { ConnectionState } from "@/components/ConnectionState";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { CircleAlert, FileWarning, RefreshCw, ShieldCheck } from "lucide-react";

type OperationsPageProps = {
  eyebrow: string;
  title: string;
  description: string;
  action?: string;
};

export default function OperationsPage({ eyebrow, title, description, action }: OperationsPageProps) {
  return (
    <div className="mx-auto max-w-6xl">
      <header className="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p className="text-xs font-semibold uppercase tracking-[.18em] text-[#8a513e]">{eyebrow}</p><h1 className="mt-2 text-3xl font-semibold tracking-[-.05em] text-[#1d1d1b] sm:text-4xl">{title}</h1><p className="mt-3 max-w-2xl leading-7 text-[#625f59]">{description}</p></div>
        {action ? <Button disabled className="rounded-xl bg-[#1d1d1b] text-white opacity-60"><RefreshCw className="mr-2 h-4 w-4" />{action}</Button> : null}
      </header>
      <div className="grid gap-4 lg:grid-cols-[1.25fr_.75fr]">
        <ConnectionState />
        <aside className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-[#e4f0e5] p-6 text-[#214d2b]">
          <div className="flex items-center gap-2"><ShieldCheck className="h-5 w-5" /><span className="text-sm font-semibold">Garde-fous actifs</span></div>
          <ul className="mt-5 space-y-3 text-sm leading-5"><li className="flex gap-2"><CircleAlert className="mt-0.5 h-4 w-4 shrink-0" />Aucune opération métier n’est proposée avant autorisation côté serveur.</li><li className="flex gap-2"><FileWarning className="mt-0.5 h-4 w-4 shrink-0" />Les confirmations, journaux d’audit et erreurs seront reliés au BFF.</li></ul>
          <Badge className="mt-6 rounded-full border-0 bg-white text-[#214d2b]">En attente d’une connexion validée</Badge>
        </aside>
      </div>
    </div>
  );
}
