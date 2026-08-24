# Modifier les contenus Keleva Woo sans alourdir le site

La boutique Keleva est pensée pour garder les parcours sensibles — catalogue, quick view, panier, checkout et options restaurant — dans le thème WooCommerce léger. Les modifications de contenu courantes ne demandent donc pas d’ajouter un widget Elementor sur ces pages.

## Produits et restaurant

Dans WordPress, ouvrez **Produits**, puis le produit à modifier. Vous pouvez changer le nom, le prix, la description, l’image mise en avant, les catégories, le stock et le statut de publication. Pour un produit restaurant, le panneau **Options restaurant Keleva** permet de modifier les sauces ; la limite de deux sélections est toujours appliquée par l’interface et le serveur.

## Textes de l’accueil Velora

Ouvrez **Apparence → Personnaliser**. Trois groupes apparaissent :

| Groupe | Contenus modifiables | Effet technique |
|---|---|---|
| **Accueil : hero & catalogue** | Surtitre, titre, texte d’introduction et bouton principal | Aucun script front-end ajouté |
| **Accueil : bénéfices** | Titre de section et les trois arguments | Aucun widget Elementor ajouté |
| **Accueil : FAQ** | Titre, questions et réponses | Accordéons HTML natifs, sans JavaScript |

Les champs sont enregistrés comme réglages de thème WordPress. Un champ vide revient automatiquement au texte Velora par défaut : la page ne peut donc pas afficher un titre ou un bloc important vide par erreur.

## Elementor : quand l’utiliser

Elementor reste approprié pour les blocs éditoriaux exceptionnels, les pages de campagne ou les layouts Woo Builder disponibles dans **Apparence → Keleva Layouts**. Pour conserver les performances, évitez d’ajouter un grand nombre de widgets, carrousels, animations, pop-ups et addons sur l’accueil, le catalogue, les fiches produit, le panier et le checkout. Après une modification Elementor significative, contrôlez le rendu mobile et lancez un audit Lighthouse.

## Points à ne pas modifier sans contrôle

Les attributs `data-keleva-*`, les routes `keleva-media`, les scripts du thème, les classes de quick view et les templates WooCommerce gèrent le panier, les images AVIF/WebP et le cache. Ils ne doivent pas être supprimés dans Elementor ou l’éditeur de fichiers. Pour une modification structurelle de ces parcours, utilisez une mise à jour de thème et testez-la d’abord dans la sandbox.
