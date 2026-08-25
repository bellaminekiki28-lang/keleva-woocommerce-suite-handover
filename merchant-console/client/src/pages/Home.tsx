import { ConnectionState } from "@/components/ConnectionState";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Activity,
  ArrowRight,
  ArrowUpRight,
  Boxes,
  Camera,
  Palette,
  ReceiptText,
  ShieldCheck,
  ShoppingBag,
  Sparkles,
  Store,
} from "lucide-react";
import { useLocation } from "wouter";

const actions = [
  {
    icon: ShoppingBag,
    label: "Ajouter un plat",
    description:
      "Créez un produit en quelques étapes, avec photo, prix et disponibilité.",
    path: "/catalogue/nouveau",
    tone: "bg-[#f6e1d6] text-[#a9441d]",
  },
  {
    icon: Boxes,
    label: "Modifier un prix ou un stock",
    description:
      "Retrouvez rapidement un produit et modifiez une seule information à la fois.",
    path: "/stock",
    tone: "bg-[#e5efe5] text-[#35613b]",
  },
  {
    icon: Camera,
    label: "Changer une photo",
    description:
      "Choisissez une image propre, contrôlée et adaptée au téléphone.",
    path: "/medias",
    tone: "bg-[#eee8f5] text-[#665080]",
  },
  {
    icon: ReceiptText,
    label: "Voir les commandes",
    description:
      "Suivez les commandes à confirmer, préparer, livrer ou clôturer.",
    path: "/commandes",
    tone: "bg-[#e7edf4] text-[#385a78]",
  },
  {
    icon: Palette,
    label: "Modifier l’apparence",
    description: "Prévisualisez la palette, le hero et les éléments de marque.",
    path: "/apparence",
    tone: "bg-[#f4ead4] text-[#856022]",
  },
];

export default function Home() {
  const [, setLocation] = useLocation();

  return (
    <div className="mx-auto max-w-6xl">
      <header className="mb-7 overflow-hidden rounded-[2rem] bg-[#1d1d1b] p-6 text-[#f8f4ec] shadow-[0_20px_50px_rgba(28,28,27,.17)] sm:p-9">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <Badge className="rounded-full border-0 bg-[#eb5f2a] px-3 py-1 text-[#1d1d1b]">
            Keleva Manager
          </Badge>
          <span className="text-xs uppercase tracking-[.16em] text-[#b8b3aa]">
            WooCommerce reste la source de vérité
          </span>
        </div>
        <div className="mt-9 flex items-start gap-4">
          <div
            className="hidden rounded-2xl bg-white/10 p-3 sm:block"
            aria-hidden="true"
          >
            <Sparkles className="h-6 w-6 text-[#f8a254]" />
          </div>
          <div>
            <h1 className="max-w-3xl text-3xl font-semibold tracking-[-.055em] sm:text-5xl">
              Que voulez-vous faire aujourd’hui ?
            </h1>
            <p className="mt-5 max-w-2xl text-base leading-7 text-[#d9d4ca]">
              Des actions simples pour piloter votre boutique. Les réglages
              techniques restent protégés dans la zone avancée.
            </p>
          </div>
        </div>
        <Button
          onClick={() => setLocation("/connexion")}
          className="mt-7 rounded-xl bg-[#f8f4ec] text-[#1d1d1b] hover:bg-white"
        >
          <Store className="mr-2 h-4 w-4" />
          Connecter un magasin <ArrowUpRight className="ml-2 h-4 w-4" />
        </Button>
      </header>

      <section aria-labelledby="quick-actions-title">
        <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[.18em] text-[#8a513e]">
              Raccourcis de gestion
            </p>
            <h2
              id="quick-actions-title"
              className="mt-2 text-2xl font-semibold tracking-[-.04em] text-[#1d1d1b]"
            >
              Les cinq actions essentielles
            </h2>
          </div>
          <p className="max-w-sm text-right text-sm leading-6 text-[#625f59]">
            Chaque action vous guide étape par étape et demande une confirmation
            avant une modification sensible.
          </p>
        </div>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {actions.map(action => (
            <button
              key={action.label}
              type="button"
              onClick={() => setLocation(action.path)}
              className="group rounded-[1.5rem] border border-[#1d1d1b]/10 bg-white p-5 text-left shadow-[0_8px_30px_rgba(28,28,27,.04)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_40px_rgba(28,28,27,.1)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#eb5f2a] focus-visible:ring-offset-2"
            >
              <span
                className={`grid h-11 w-11 place-items-center rounded-2xl ${action.tone}`}
              >
                <action.icon className="h-5 w-5" aria-hidden="true" />
              </span>
              <span className="mt-6 block text-base font-semibold tracking-tight text-[#1d1d1b]">
                {action.label}
              </span>
              <span className="mt-2 block text-sm leading-6 text-[#625f59]">
                {action.description}
              </span>
              <span className="mt-5 inline-flex items-center text-sm font-semibold text-[#1d1d1b]">
                Ouvrir{" "}
                <ArrowRight
                  className="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1"
                  aria-hidden="true"
                />
              </span>
            </button>
          ))}
        </div>
      </section>

      <section className="mt-7 grid gap-4 lg:grid-cols-[1.1fr_.9fr]">
        <ConnectionState compact />
        <article className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-[#e4f0e5] p-6 text-[#214d2b]">
          <div className="flex items-center gap-2">
            <ShieldCheck className="h-5 w-5" aria-hidden="true" />
            <span className="text-sm font-semibold">
              Réglages avancés protégés
            </span>
          </div>
          <p className="mt-5 text-sm leading-6">
            Stripe, WhatsApp Cloud, n8n, clés secrètes, cache et webhooks ne
            sont pas nécessaires pour les tâches quotidiennes. Ils sont réservés
            à l’administrateur technique.
          </p>
          <Button
            variant="outline"
            onClick={() => setLocation("/synchronisation")}
            className="mt-5 rounded-xl border-[#214d2b]/20 bg-white text-[#214d2b] hover:bg-[#f5faf5]"
          >
            Voir l’état technique <ArrowRight className="ml-2 h-4 w-4" />
          </Button>
        </article>
      </section>

      <div className="mt-5 flex items-center gap-2 text-xs text-[#5d574e]">
        <Activity className="h-4 w-4" aria-hidden="true" />
        Aucune métrique n’est inventée avant la connexion et la première
        synchronisation du magasin.
      </div>
    </div>
  );
}
