# Keleva Woo 0.4.2 — correctifs de conformité

Cette version corrige les écarts P0 démontrés lors de l’audit WordPress/WooCommerce local et renforce le contrat de recette.

| Domaine | Correction |
|---|---|
| Catégories | Une catégorie contenant des produits ne peut plus être supprimée silencieusement. L’API retourne HTTP 409 avec les identifiants des produits à déplacer. |
| Options | Un groupe `buttons` avec une limite supérieure à un est normalisé côté serveur en `checkbox`, comme l’impose la console. |
| Sources de modèles | Le payload public expose `category_default`, `customized` ou `none`, tandis que les métadonnées internes historiques restent compatibles. |
| Design tokens | Cinq palettes exposent les surfaces, états, danger, avertissement, ombre et contraste de texte sombre. Les 21 clés sont validées par le test PHP. |
| Accessibilité | La recherche utilise un combobox valide ; la galerie utilise une vraie liste HTML, des boutons nommés et `aria-pressed` sans `role=listitem`. |
| Contraste | Les CTA, rails et en-têtes de la console utilisent les tokens `on-accent`/`on-ink`. Le probe Axe indépendant ne relève plus de violation sur les vues home, produit et login. |
| Checkout | Lorsque le bloc WooCommerce ne produit pas de formulaire exploitable, le thème rend un fallback `[woocommerce_checkout]` classique tout en conservant le support du bloc lorsqu’il est hydraté. |
| Provisioning | Ajout de `wordpress-dev/bin/provision.sh`, portable et idempotent, installant/activant WooCommerce, le plugin, le thème, les pages et les fixtures. Les chemins personnels de console ont été supprimés. |
| Recette | Les sélecteurs Playwright ciblent désormais l’identifiant produit de fixture et la pagination peut être activée sans ambiguïté. Un test REST sécurité/API versionné a été ajouté. |

## Validation exécutée

Les validations réussies sur le laboratoire réel sont les suivantes : lint PHP sur 51 fichiers, syntaxe JavaScript, parsing JSON de `.wp-env.json`, provisioning idempotent avec code zéro, contrat REST sécurité/API, contrat PHP des palettes, Playwright/Axe Chromium/Firefox/WebKit avec pagination, et probe Axe indépendant sur quatre vues et deux largeurs mobiles. Le dernier probe indépendant rapporte zéro violation Axe sur les vues `home-drawer`, `home-mobile-drawer`, `product` et `merchant-login`.

Les chemins de test du laboratoire sont exclus du dépôt : `/home/ubuntu/keleva-local-wordpress`. Les scripts de recette ne contiennent pas de secret de production.
