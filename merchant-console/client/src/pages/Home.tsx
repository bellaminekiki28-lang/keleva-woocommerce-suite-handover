import { ConnectionState } from "@/components/ConnectionState";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Activity, ArrowUpRight, Boxes, GalleryVerticalEnd, ShoppingBag } from "lucide-react";
import { useLocation } from "wouter";

const areas = [
  { icon: ShoppingBag, label: "Commandes", description: "Statuts, traitement, remboursement et traçabilité." },
  { icon: Boxes, label: "Catalogue & stock", description: "Produits, variations, disponibilité et actions contrôlées." },
  { icon: GalleryVerticalEnd, label: "Médias", description: "Originaux, variantes, erreurs et reprises de traitement." },
];

export default function Home() {
  const [, setLocation] = useLocation();
  return (
    <div className="mx-auto max-w-6xl">
      <header className="mb-7 overflow-hidden rounded-[2rem] bg-[#1d1d1b] p-6 text-[#f8f4ec] shadow-[0_20px_50px_rgba(28,28,27,.17)] sm:p-9">
        <div className="flex items-center justify-between gap-4"><Badge className="rounded-full border-0 bg-[#eb5f2a] px-3 py-1 text-white">Console externe</Badge><span className="text-xs uppercase tracking-[.16em] text-[#b8b3aa]">WooCommerce reste la source de vérité</span></div>
        <h1 className="mt-9 max-w-3xl text-3xl font-semibold tracking-[-.055em] sm:text-5xl">Pilotez votre activité sans exposer vos secrets.</h1>
        <p className="mt-5 max-w-2xl text-base leading-7 text-[#d9d4ca]">Keleva Merchant centralise l’opérationnel dans une interface mobile-first. Chaque action sensible est conçue pour être confirmée, contrôlée et auditée.</p>
        <Button onClick={() => setLocation("/connexion")} className="mt-7 rounded-xl bg-[#f8f4ec] text-[#1d1d1b] hover:bg-white">Connecter un magasin <ArrowUpRight className="ml-2 h-4 w-4" /></Button>
      </header>

      <div className="grid gap-4 md:grid-cols-3">
        {areas.map((area) => <article key={area.label} className="rounded-[1.5rem] border border-[#1d1d1b]/10 bg-white p-5"><area.icon className="h-5 w-5 text-[#eb5f2a]" /><h2 className="mt-6 font-semibold tracking-tight text-[#1d1d1b]">{area.label}</h2><p className="mt-2 text-sm leading-6 text-[#625f59]">{area.description}</p></article>)}
      </div>

      <section className="mt-7 grid gap-4 lg:grid-cols-[1.1fr_.9fr]">
        <ConnectionState compact />
        <article className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-[#f6e1d6] p-6"><div className="flex items-center gap-2 text-[#9e3a16]"><Activity className="h-5 w-5" /><span className="text-sm font-semibold">Observabilité par défaut</span></div><p className="mt-5 text-sm leading-6 text-[#6e3d2e]">Les prochains écrans relieront chaque synchronisation, webhook, import et traitement média à son statut, à son erreur et à son journal d’audit. Aucune métrique opérationnelle n’est inventée avant la première synchronisation.</p></article>
      </section>
    </div>
  );
}
