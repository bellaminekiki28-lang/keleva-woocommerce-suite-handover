import { Button } from "@/components/ui/button";
import { ArrowRight, Link2, ShieldCheck } from "lucide-react";
import { useLocation } from "wouter";

export function ConnectionState({ compact = false }: { compact?: boolean }) {
  const [, setLocation] = useLocation();
  return (
    <section className={`overflow-hidden rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white ${compact ? "p-5" : "p-6 sm:p-8"}`}>
      <div className="flex items-start justify-between gap-5">
        <div>
          <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-[#f6e1d6] text-[#b4461d]"><Link2 className="h-5 w-5" /></div>
          <p className="text-xs font-semibold uppercase tracking-[.17em] text-[#8a513e]">Aucune donnée simulée</p>
          <h2 className="mt-2 text-xl font-semibold tracking-[-.03em] text-[#1d1d1b]">Connectez votre magasin pour commencer.</h2>
          <p className="mt-2 max-w-xl text-sm leading-6 text-[#605d57]">Les clés WooCommerce sont saisies une seule fois, chiffrées côté serveur, jamais rendues au navigateur et révocables à tout moment.</p>
        </div>
        <ShieldCheck className="h-6 w-6 shrink-0 text-[#3c7545]" aria-hidden="true" />
      </div>
      <Button onClick={() => setLocation("/connexion")} className="mt-6 rounded-xl bg-[#1d1d1b] text-white hover:bg-[#343330]">Configurer une connexion <ArrowRight className="ml-2 h-4 w-4" /></Button>
    </section>
  );
}
