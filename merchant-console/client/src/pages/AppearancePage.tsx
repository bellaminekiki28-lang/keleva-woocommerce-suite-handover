import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ArrowLeft, Check, Eye, Palette, ShieldCheck } from "lucide-react";
import { useState } from "react";
import { useLocation } from "wouter";

const palettes = [
  {
    id: "velora",
    label: "Velora Corail",
    description: "Chaleur éditoriale et conversion.",
    bg: "#F7F4EE",
    surface: "#FFFDF8",
    ink: "#1E1C19",
    accent: "#A83B2B",
  },
  {
    id: "onyx-gold",
    label: "Onyx Doré",
    description: "Luxe nocturne et campagne événementielle.",
    bg: "#0A0A0B",
    surface: "#131315",
    ink: "#F7F1E6",
    accent: "#D3A33E",
  },
  {
    id: "sienna",
    label: "Sienne Atelier",
    description: "Gastronomie artisanale et proximité.",
    bg: "#FAF3EA",
    surface: "#FFFDF9",
    ink: "#33231D",
    accent: "#98402B",
  },
  {
    id: "sage",
    label: "Sauge Minérale",
    description: "Nature, fraîcheur et calme.",
    bg: "#F0F3ED",
    surface: "#FCFDF9",
    ink: "#1E3028",
    accent: "#2B604D",
  },
  {
    id: "azure",
    label: "Azur Profond",
    description: "Confiance et sobriété froide.",
    bg: "#F2F6FB",
    surface: "#FEFEFF",
    ink: "#13283D",
    accent: "#1B5D88",
  },
  {
    id: "obsidienne-cuivree",
    label: "Obsidienne Cuivrée",
    description: "Luxe manufacturé et matière.",
    bg: "#0D0D0F",
    surface: "#16161A",
    ink: "#F2EDE4",
    accent: "#9C5518",
  },
  {
    id: "ivoire-encre",
    label: "Ivoire Encre",
    description: "Maison éditoriale et contraste.",
    bg: "#F5F1E8",
    surface: "#FFFDF8",
    ink: "#24211D",
    accent: "#7A3E2A",
  },
  {
    id: "argile-sombre",
    label: "Argile Sombre",
    description: "Matière nocturne et végétale.",
    bg: "#1C1A17",
    surface: "#26231F",
    ink: "#F1EBDD",
    accent: "#5D6A44",
  },
  {
    id: "perle-graphite",
    label: "Perle Graphite",
    description: "Raffinement discret et minéral.",
    bg: "#F4F3F1",
    surface: "#FFFFFF",
    ink: "#25232A",
    accent: "#4F596D",
  },
];

export default function AppearancePage() {
  const [, setLocation] = useLocation();
  const [selected, setSelected] = useState("velora");
  const active =
    palettes.find(palette => palette.id === selected) ?? palettes[0];

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
            Apparence guidée
          </p>
          <h1 className="mt-2 text-3xl font-semibold tracking-[-.05em] text-[#1d1d1b] sm:text-4xl">
            Modifier l’apparence
          </h1>
          <p className="mt-3 max-w-2xl leading-7 text-[#625f59]">
            Choisissez une direction visuelle et regardez immédiatement son
            caractère. La publication sur le magasin sera activée après
            connexion.
          </p>
        </div>
        <Badge className="w-fit rounded-full border-0 bg-[#f4ead4] text-[#76551c]">
          Aperçu local uniquement
        </Badge>
      </header>

      <div className="grid gap-5 xl:grid-cols-[1.1fr_.9fr]">
        <section
          aria-labelledby="palette-title"
          className="rounded-[1.75rem] border border-[#1d1d1b]/10 bg-white p-5 sm:p-7"
        >
          <div className="mb-5 flex items-center gap-3">
            <span className="grid h-10 w-10 place-items-center rounded-2xl bg-[#f6e1d6] text-[#a9441d]">
              <Palette className="h-5 w-5" aria-hidden="true" />
            </span>
            <div>
              <h2 id="palette-title" className="font-semibold text-[#1d1d1b]">
                Palettes Keleva
              </h2>
              <p className="text-sm text-[#6b675f]">
                Velora reste la palette par défaut.
              </p>
            </div>
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            {palettes.map(palette => {
              const isSelected = palette.id === selected;
              return (
                <button
                  key={palette.id}
                  type="button"
                  onClick={() => setSelected(palette.id)}
                  aria-pressed={isSelected}
                  className={`rounded-2xl border p-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#eb5f2a] focus-visible:ring-offset-2 ${isSelected ? "border-[#eb5f2a] bg-[#fff8f3] shadow-[0_8px_24px_rgba(235,95,42,.12)]" : "border-[#1d1d1b]/10 hover:border-[#1d1d1b]/25"}`}
                >
                  <span className="flex items-center justify-between gap-3">
                    <span className="flex gap-1.5" aria-hidden="true">
                      <i
                        className="h-5 w-5 rounded-full border border-black/10"
                        style={{ background: palette.bg }}
                      />
                      <i
                        className="h-5 w-5 rounded-full border border-black/10"
                        style={{ background: palette.ink }}
                      />
                      <i
                        className="h-5 w-5 rounded-full border border-black/10"
                        style={{ background: palette.accent }}
                      />
                    </span>
                    {isSelected ? (
                      <Check
                        className="h-4 w-4 text-[#eb5f2a]"
                        aria-label="Palette sélectionnée"
                      />
                    ) : null}
                  </span>
                  <span className="mt-3 block text-sm font-semibold text-[#1d1d1b]">
                    {palette.label}
                  </span>
                  <span className="mt-1 block text-xs leading-5 text-[#6b675f]">
                    {palette.description}
                  </span>
                </button>
              );
            })}
          </div>
        </section>

        <section
          aria-labelledby="preview-title"
          className="overflow-hidden rounded-[1.75rem] border border-[#1d1d1b]/10 p-5 sm:p-7"
          style={{ background: active.bg, color: active.ink }}
        >
          <div className="flex items-center justify-between gap-3">
            <div
              className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[.16em]"
              style={{ color: active.accent }}
            >
              <Eye className="h-4 w-4" aria-hidden="true" />
              Aperçu
            </div>
            <span
              className="rounded-full px-3 py-1 text-[11px] font-semibold"
              style={{ background: active.surface, color: active.ink }}
            >
              {active.label}
            </span>
          </div>
          <h2
            id="preview-title"
            className="mt-10 text-3xl font-semibold tracking-[-.05em]"
          >
            Choisir moins.
            <br />
            Choisir mieux.
          </h2>
          <p className="mt-4 max-w-sm text-sm leading-6 opacity-75">
            Une sélection préparée avec intention, présentée avec calme et
            livrée avec attention.
          </p>
          <div
            className="mt-8 rounded-2xl p-4"
            style={{ background: active.surface }}
          >
            <div className="flex items-center justify-between gap-3">
              <span className="text-sm font-semibold">Sélection du jour</span>
              <span
                className="text-sm font-semibold"
                style={{ color: active.accent }}
              >
                49 MAD
              </span>
            </div>
            <p className="mt-2 text-xs opacity-70">
              Photo, disponibilité et prix visibles en un regard.
            </p>
            <button
              type="button"
              className="mt-4 rounded-xl px-4 py-2 text-sm font-semibold"
              style={{ background: active.accent, color: active.bg }}
            >
              Voir le plat
            </button>
          </div>
          <div className="mt-6 flex items-start gap-2 text-xs leading-5 opacity-75">
            <ShieldCheck
              className="mt-0.5 h-4 w-4 shrink-0"
              aria-hidden="true"
            />
            La prévisualisation n’écrit rien sur le magasin tant que la
            connexion n’est pas validée.
          </div>
          <Button
            disabled
            className="mt-6 w-full rounded-xl bg-[#1d1d1b] text-white opacity-50"
          >
            Publier cette apparence
          </Button>
        </section>
      </div>
    </div>
  );
}
