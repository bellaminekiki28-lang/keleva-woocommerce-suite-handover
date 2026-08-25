import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import DashboardLayout from "@/components/DashboardLayout";
import ErrorBoundary from "@/components/ErrorBoundary";
import { ThemeProvider } from "@/contexts/ThemeContext";
import NotFound from "@/pages/NotFound";
import OperationsPage from "@/pages/OperationsPage";
import { lazy, Suspense } from "react";
import { Route, Switch } from "wouter";

const Home = lazy(() => import("@/pages/Home"));
const ConnectionPage = lazy(() => import("@/pages/ConnectionPage"));
const AppearancePage = lazy(() => import("@/pages/AppearancePage"));
const StoreProductsPage = lazy(() => import("@/pages/StoreProductsPage"));
const OrdersPage = lazy(() => import("@/pages/OrdersPage"));
const CreateProductPage = lazy(() => import("@/pages/CreateProductPage"));
const MediaPage = lazy(() => import("@/pages/MediaPage"));
const SyncPage = lazy(() => import("@/pages/SyncPage"));
const AuditPage = lazy(() => import("@/pages/AuditPage"));

function LoadingPage() {
  return (
    <div className="mx-auto grid min-h-[40vh] max-w-6xl place-items-center">
      <div className="rounded-2xl border border-[#1d1d1b]/10 bg-white px-5 py-4 text-sm text-[#625f59]">
        Ouverture de votre espace…
      </div>
    </div>
  );
}

function ConsoleRoutes() {
  return (
    <DashboardLayout>
      <Suspense fallback={<LoadingPage />}>
        <Switch>
          <Route path="/" component={Home} />
          <Route path="/connexion" component={ConnectionPage} />
          <Route path="/commandes" component={OrdersPage} />
          <Route path="/catalogue">
            {() => <StoreProductsPage mode="catalogue" />}
          </Route>
          <Route path="/catalogue/nouveau" component={CreateProductPage} />
          <Route path="/stock">
            {() => <StoreProductsPage mode="stock" />}
          </Route>
          <Route path="/medias" component={MediaPage} />
          <Route path="/apparence" component={AppearancePage} />
          <Route path="/synchronisation" component={SyncPage} />
          <Route path="/audit" component={AuditPage} />
          <Route component={NotFound} />
        </Switch>
      </Suspense>
    </DashboardLayout>
  );
}

export default function App() {
  return (
    <ErrorBoundary>
      <ThemeProvider defaultTheme="light">
        <TooltipProvider>
          <ConsoleRoutes />
          <Toaster />
        </TooltipProvider>
      </ThemeProvider>
    </ErrorBoundary>
  );
}
