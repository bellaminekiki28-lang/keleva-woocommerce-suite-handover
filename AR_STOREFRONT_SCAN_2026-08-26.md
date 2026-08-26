# Scan AR storefront — 2026-08-26

URL: fiche produit arabe de staging, avec compte WordPress administrateur actif dans le navigateur.

Chaînes d’interface visibles et déjà arabes : recherche, panier, retour au catalogue, état disponible, choix de sauce, quantité, ajout au panier, onglets description/informations/offres/politiques/questions, favoris, vue rapide, checkout labels dans le contenu extrait.

Éléments non arabes observés : `V velora.` / `Velora`, `Brunch & Bloom`, `Pancakes fleur d’oranger`, `Œufs shakshuka`, `French`, ainsi que le texte alt d’image `Afficher l’image produit en grand`. Les trois premiers sont marques ou noms de produits éditoriaux ; `French` est le sélecteur de langue ; le texte alt est une aide d’administration/extension. Aucun reliquat WCFM client supplémentaire n’a été identifié dans le DOM extrait au-delà des labels déjà traduits ou des noms de marque.

Capture : `/home/ubuntu/screenshots/aliceblue-bison-4339_2026-08-26_19-17-43_2932.webp`.

Conclusion : la passe AR côté client est fonctionnellement localisée ; les reliquats à traiter sont soit identitaires/éditoriaux, soit liés à l’administration WordPress et ne doivent pas être traduits automatiquement sans choix éditorial explicite. La documentation finale doit conserver cette limite.

Source publique : staging Hostinger, aucune transaction ni donnée client créée.

