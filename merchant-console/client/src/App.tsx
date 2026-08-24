import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import DashboardLayout from "@/components/DashboardLayout";
import ErrorBoundary from "@/components/ErrorBoundary";
import { ThemeProvider } from "@/contexts/ThemeContext";
import Home from "@/pages/Home";
import ConnectionPage from "@/pages/ConnectionPage";
import NotFound from "@/pages/NotFound";
import OperationsPage from "@/pages/OperationsPage";
import { Route, Switch } from "wouter";

function ConsoleRoutes() {
  return (
    <DashboardLayout>
      <Switch>
        <Route path="/" component={Home} />
        <Route path="/connexion" component={ConnectionPage} />
        <Route path="/commandes">{() => <OperationsPage eyebrow="Opérations" title="Commandes à traiter" description="La liste et les transitions de statut apparaîtront après une synchronisation authentifiée du magasin. Les remboursements et annulations resteront soumis à confirmation et audit." action="Synchroniser les commandes" />}</Route>
        <Route path="/catalogue">{() => <OperationsPage eyebrow="Catalogue" title="Produits et variations" description="La console affichera uniquement les données WooCommerce synchronisées ; les brouillons, désactivations et imports seront gouvernés côté serveur." action="Synchroniser le catalogue" />}</Route>
        <Route path="/stock">{() => <OperationsPage eyebrow="Disponibilité" title="Stock et variations" description="Les modifications de quantité, seuil et disponibilité seront validées côté serveur, confirmées dans l’interface et archivées dans le journal d’audit." />}</Route>
        <Route path="/medias">{() => <OperationsPage eyebrow="Médias" title="Originaux et variantes" description="La chaîne média suivra la réception du fichier source, les variantes responsive JPEG/WebP/AVIF, le fallback et les reprises en erreur." />}</Route>
        <Route path="/synchronisation">{() => <OperationsPage eyebrow="Connectivité" title="Synchronisation observable" description="Les webhooks signés, les tâches de resynchronisation et leurs erreurs seront visibles ici avec des actions contrôlées de reprise." />}</Route>
        <Route path="/audit">{() => <OperationsPage eyebrow="Traçabilité" title="Journal d’audit" description="Les actions sensibles et les changements appliqués seront attribués, horodatés et consultables sans jamais exposer de secret ou de données carte." />}</Route>
        <Route component={NotFound} />
      </Switch>
    </DashboardLayout>
  );
}

export default function App() {
  return <ErrorBoundary><ThemeProvider defaultTheme="light"><TooltipProvider><ConsoleRoutes /><Toaster /></TooltipProvider></ThemeProvider></ErrorBoundary>;
}
