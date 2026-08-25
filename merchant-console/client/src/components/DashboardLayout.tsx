import { useAuth } from "@/_core/hooks/useAuth";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarProvider,
  SidebarTrigger,
} from "@/components/ui/sidebar";
import { startLogin } from "@/const";
import { useIsMobile } from "@/hooks/useMobile";
import {
  Boxes,
  FileClock,
  GalleryVerticalEnd,
  LayoutDashboard,
  Link2,
  ListChecks,
  LogOut,
  PackageSearch,
  Palette,
  RefreshCw,
  ShieldCheck,
  ShoppingBag,
} from "lucide-react";
import { useLocation } from "wouter";
import { DashboardLayoutSkeleton } from "./DashboardLayoutSkeleton";

const menuItems = [
  { icon: LayoutDashboard, label: "Vue d’ensemble", path: "/" },
  { icon: Link2, label: "Connexion magasin", path: "/connexion" },
  { icon: ShoppingBag, label: "Commandes", path: "/commandes" },
  { icon: PackageSearch, label: "Catalogue", path: "/catalogue" },
  { icon: Boxes, label: "Stock & variations", path: "/stock" },
  { icon: GalleryVerticalEnd, label: "Médias", path: "/medias" },
  { icon: Palette, label: "Apparence", path: "/apparence" },
  { icon: RefreshCw, label: "Synchronisation", path: "/synchronisation" },
  { icon: FileClock, label: "Journal d’audit", path: "/audit" },
];

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const { loading, user } = useAuth();

  if (loading) return <DashboardLayoutSkeleton />;

  if (!user) {
    return (
      <main className="grid min-h-screen place-items-center bg-[#f4f0e8] p-5 text-[#1d1d1b]">
        <section className="w-full max-w-md rounded-[2rem] border border-[#1d1d1b]/10 bg-white p-8 shadow-[0_24px_70px_rgba(28,28,27,.12)]">
          <div className="mb-8 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#eb5f2a] text-white">
            <ShieldCheck className="h-6 w-6" />
          </div>
          <p className="mb-2 text-xs font-semibold uppercase tracking-[.18em] text-[#8a513e]">
            Keleva Merchant
          </p>
          <h1 className="text-3xl font-semibold tracking-[-.04em]">
            Accès marchand sécurisé
          </h1>
          <p className="mt-4 leading-6 text-[#5f5d58]">
            La console est séparée de wp-admin. Les opérations passent par un
            serveur applicatif et sont journalisées.
          </p>
          <Button
            onClick={() => startLogin()}
            className="mt-8 h-12 w-full rounded-xl bg-[#1d1d1b] text-white hover:bg-[#343330]"
          >
            Se connecter
          </Button>
        </section>
      </main>
    );
  }

  return (
    <SidebarProvider defaultOpen>
      <ConsoleSidebar />
      <SidebarInset className="bg-[#f4f0e8]">
        <MobileTopbar />
        <main className="min-h-screen p-4 sm:p-6 lg:p-8">{children}</main>
      </SidebarInset>
    </SidebarProvider>
  );
}

function ConsoleSidebar() {
  const [location, setLocation] = useLocation();
  const { user, logout } = useAuth();

  return (
    <Sidebar
      collapsible="icon"
      className="border-r border-[#1d1d1b]/10 bg-[#1d1d1b] text-[#f4f0e8]"
    >
      <SidebarHeader className="px-3 py-4">
        <button
          onClick={() => setLocation("/")}
          className="flex w-full items-center gap-3 rounded-xl p-2 text-left outline-none focus-visible:ring-2 focus-visible:ring-[#f8a254]"
        >
          <span className="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#eb5f2a] text-sm font-black text-white">
            K
          </span>
          <span className="min-w-0 group-data-[collapsible=icon]:hidden">
            <span className="block truncate text-sm font-semibold tracking-tight">
              Keleva Merchant
            </span>
            <span className="block truncate text-[11px] uppercase tracking-[.14em] text-[#b8b3aa]">
              Centre opérationnel
            </span>
          </span>
        </button>
      </SidebarHeader>
      <SidebarContent className="px-2">
        <SidebarMenu>
          {menuItems.map(item => (
            <SidebarMenuItem key={item.path}>
              <SidebarMenuButton
                isActive={location === item.path}
                tooltip={item.label}
                onClick={() => setLocation(item.path)}
                className="h-11 rounded-xl text-[#d9d4ca] hover:bg-white/10 hover:text-white data-[active=true]:bg-[#f4f0e8] data-[active=true]:text-[#1d1d1b]"
              >
                <item.icon className="h-4 w-4" />
                <span>{item.label}</span>
              </SidebarMenuButton>
            </SidebarMenuItem>
          ))}
        </SidebarMenu>
      </SidebarContent>
      <SidebarFooter className="border-t border-white/10 p-3">
        <div className="mb-3 flex items-center gap-2 rounded-xl bg-white/5 p-2 group-data-[collapsible=icon]:justify-center">
          <span
            className="h-2 w-2 rounded-full bg-[#9fc8a5]"
            aria-hidden="true"
          />
          <span className="text-xs text-[#d9d4ca] group-data-[collapsible=icon]:hidden">
            BFF prêt à configurer
          </span>
        </div>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <button className="flex w-full items-center gap-3 rounded-xl p-2 text-left transition-colors hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#f8a254]">
              <Avatar className="h-8 w-8 border border-white/20">
                <AvatarFallback className="bg-[#343330] text-xs text-white">
                  {user?.name?.slice(0, 1).toUpperCase()}
                </AvatarFallback>
              </Avatar>
              <span className="min-w-0 group-data-[collapsible=icon]:hidden">
                <span className="block truncate text-sm text-white">
                  {user?.name ?? "Marchand"}
                </span>
                <span className="block truncate text-xs text-[#b8b3aa]">
                  Session chiffrée
                </span>
              </span>
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem
              onClick={logout}
              className="cursor-pointer text-destructive"
            >
              <LogOut className="mr-2 h-4 w-4" />
              Se déconnecter
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarFooter>
    </Sidebar>
  );
}

function MobileTopbar() {
  const isMobile = useIsMobile();
  const [location] = useLocation();
  if (!isMobile) return null;
  const title =
    menuItems.find(item => item.path === location)?.label ?? "Keleva Merchant";
  return (
    <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-[#1d1d1b]/10 bg-[#f4f0e8]/95 px-4 backdrop-blur">
      <div className="flex items-center gap-3">
        <SidebarTrigger className="rounded-xl border border-[#1d1d1b]/10 bg-white" />
        <span className="text-sm font-semibold">{title}</span>
      </div>
      <Badge className="rounded-full border-0 bg-[#e4f0e5] text-[#37603e]">
        Sécurisé
      </Badge>
    </header>
  );
}
