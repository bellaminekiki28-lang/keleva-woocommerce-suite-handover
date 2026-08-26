# Recette portail arabe authentifié — 2026-08-26

Route : `/ar/espace-marchand/` sur le domaine Hostinger natif, session `keleva.recette` déjà active après contrôle de persistance.

Vérifications navigateur desktop : dashboard Keleva white-label rendu sans wp-admin ni domaine externe ; navigation Ajouter un produit, Produits & stock, Commandes, Apparence, Catégories & options visible ; six produits avec stock et prix visibles ; commandes de staging visibles avec statuts ; palettes disponibles ; bloc Catégories disponibles et création de catégorie présent ; bloc « Choix, variantes et suppléments » présent avec cartes « Gérer les choix » et produits arabes.

Vérification RTL : contenu arabe aligné à droite dans les cartes, catégories et produits ; aucun débordement visible dans la vue desktop. La matrice mobile 390 × 844 px et les contrôles hors-canevas ont été validés dans les releases RTL antérieures et sont conservés dans le journal de recette. Aucun bouton de mutation, paiement ou création de donnée n’a été déclenché pendant cette passe finale.

Limite observée : certains libellés d’aide du portail restent en français sur la route arabe (portail white-label fonctionnel, mais traduction UI du portail non complète). Cette limite ne concerne pas le storefront public et doit rester signalée avant de déclarer une localisation arabe exhaustive du portail.

