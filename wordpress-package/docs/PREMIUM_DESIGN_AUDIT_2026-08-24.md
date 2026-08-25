# Revue senior — Keleva Woo : qualité premium, palette et feuille de route

**Date :** 24 août 2026
**Staging audité :** `https://aliceblue-bison-433987.hostingersite.com/`
**Thème :** Keleva Woo `0.4.10`
**Auteur :** Manus AI

## Réponse courte

Un développeur senior ne déclarerait pas le thème « fini » uniquement parce que la page d’accueil est esthétique. Il verrouillerait d’abord une **direction artistique unique**, puis vérifierait la cohérence de cette direction sur l’accueil, le catalogue, la fiche produit, le panier, le checkout, les e-mails et les états d’erreur. Il mesurerait ensuite vitesse, accessibilité, responsive, sécurité et stabilité avec des tests reproductibles.

Le thème est déjà bien avancé visuellement : la hiérarchie éditoriale est claire, le hero est fort, les composants partagent un langage cohérent et les tokens de couleur sont centralisés. Le smoke-test corrigé confirme que la fiche produit réelle `brunch-bloom-avocado-toast` fonctionne avec sa variation Pesto et que le checkout `/commander/` charge correctement avec une session WooCommerce ; l’ancienne URL de test `/produit/avocado-toast/` était simplement erronée. Pour atteindre un niveau réellement ultra premium, les priorités résiduelles sont désormais la **discipline de contenu**, la QA sur appareils physiques, les détails d’accessibilité humaine, le LCP du panier/checkout, la configuration livraison et la mise en production réelle de WhatsApp Cloud/n8n. Les images 1×1 visibles ont été traitées sur le staging et l’extension WhatsApp concurrente a été désactivée.

## Où se trouvent les palettes ?

### Dans le code du thème

Le fichier source principal est :

```text
wordpress-package/wordpress/theme/keleva-woo/inc/palette.php
```

La fonction `keleva_woo_palettes()` centralise les cinq palettes. La fonction `keleva_woo_active_palette_id()` choisit la palette persistée dans le réglage WordPress `keleva_palette`, avec `velora` comme valeur par défaut. La fonction `keleva_woo_palette_css()` transforme la palette active en variables CSS globales : `--bg`, `--surface`, `--ink`, `--muted`, `--line`, `--accent`, `--accent-strong`, `--success`, `--warning`, `--danger`, etc.

Les feuilles qui consomment ces tokens sont principalement :

```text
wordpress-package/wordpress/theme/keleva-woo/style.css
wordpress-package/wordpress/theme/keleva-woo/assets/css/velora-parity.css
wordpress-package/wordpress/theme/keleva-woo/assets/css/velora-states.css
```

Les couleurs ne doivent pas être modifiées directement dans les composants. La règle senior est de modifier `inc/palette.php`, puis de vérifier que les composants utilisent bien les variables sémantiques plutôt que des hexadécimaux isolés.

### Dans l’administration WordPress

Le chemin d’administration est :

> **Apparence → Personnaliser → Keleva — Apparence → Palette active**

Le Customizer affiche les cinq choix suivants : **Velora Corail**, **Onyx Doré**, **Sienne Atelier**, **Sauge Minérale** et **Azur Profond**. Le staging utilise actuellement **Velora Corail**. La preuve a été vérifiée dans l’interface du Customizer et dans le DOM du storefront avec la classe `keleva-palette--velora`.

Les quatre nouvelles palettes demandées ont été ajoutées en plus des cinq existantes, avec les identifiants `obsidienne-cuivree`, `ivoire-encre`, `argile-sombre` et `perle-graphite`. Les neuf choix sont maintenant visibles dans le Customizer et les quatre previews publiques injectent bien leurs tokens calculés. La valeur par défaut reste `velora`.

La palette peut aussi être prévisualisée sans modifier le réglage persistant en ajoutant le paramètre suivant à l’URL :

```text
https://aliceblue-bison-433987.hostingersite.com/?keleva_palette_preview=onyx-gold
```

## Cartographie des palettes

| Palette | Fond | Surface | Texte | Secondaire | Accent CTA | Hover CTA | Usage recommandé |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **Velora Corail** | `#F7F4EE` | `#FFFDF8` | `#1E1C19` | `#68645D` | `#A83B2B` | `#872E22` | Identité actuelle, commerce éditorial, chaleur et conversion. |
| **Onyx Doré** | `#0A0A0B` | `#131315` | `#F7F1E6` | `#C8C1B5` | `#D3A33E` | `#E6BB62` | Positionnement luxe, nocturne, premium et campagne événementielle. |
| **Sienne Atelier** | `#FAF3EA` | `#FFFDF9` | `#33231D` | `#6C5B51` | `#98402B` | `#762E21` | Gastronomie artisanale, chaleur, matière et proximité. |
| **Sauge Minérale** | `#F0F3ED` | `#FCFDF9` | `#1E3028` | `#5E6D65` | `#2B604D` | `#20483A` | Nature, bien-être, bio, fraîcheur et calme. |
| **Azur Profond** | `#F2F6FB` | `#FEFEFF` | `#13283D` | `#53677C` | `#1B5D88` | `#15496C` | Confiance, service, tech, livraison et sobriété froide. |

La planche visuelle complète se trouve dans [`keleva-palette-board.png`](keleva-palette-board.png).

## Recommandation de direction artistique

Les quatre nouvelles propositions sont **premium sur le plan de la direction artistique** : Obsidienne Cuivrée apporte un luxe manufacturé, Ivoire Encre une maison éditoriale, Argile Sombre une matière nocturne et Perle Graphite un raffinement discret. Le PNG fourni est une bonne référence de composition, mais il reste une maquette : la validation finale doit porter sur les pages réelles, les états WooCommerce et les contrastes.

Je conserverais **Velora Corail** comme palette principale pour le moment. Elle correspond au storefront actuel, fonctionne bien avec la photographie culinaire claire et garde le CTA immédiatement identifiable. Elle possède un bon contraste sur les couples contrôlés et ne demande pas de revoir toute la lisibilité du checkout.

Je réserverais **Onyx Doré** à une version luxe ou à une campagne spéciale. La preview est visuellement la plus spectaculaire : le fond noir, le doré et la photographie dessert créent une impression de marque haut de gamme. Toutefois, une palette sombre impose une revue spécifique des états WooCommerce, des champs de formulaire, des erreurs, des emails, du focus clavier et des images transparentes. Elle ne doit pas être activée définitivement sans cette matrice de QA.

**Sienne Atelier** est la meilleure alternative si Keleva doit devenir une marque plus gastronomique et artisanale. **Sauge Minérale** et **Azur Profond** sont cohérentes mais moins distinctives pour la direction Velora actuelle.

Après remplacement des six tokens demandés, ajout ciblé de `accent-text`, migration des usages textuels d’accent fort et correction des deux libellés `success` sur fonds sombres, puis purge LiteSpeed et cache-busting, Axe ne remonte plus aucune violation `color-contrast: serious` sur les quatre previews. Obsidienne Cuivrée utilise `#C97A3A` et Argile Sombre `#8C9A6C` comme `accent-text`; `accent-strong` reste réservé aux fonds hover/actifs. Les quatre palettes sont donc validées comme **directions visuelles premium conformes au contrôle automatisé du contraste couleur**. Cette validation ne remplace pas la QA complète d’accessibilité, responsive, cross-browser, performance et production.

## Ce qu’un développeur senior ferait ensuite

### P0 — Geler une base propre et éliminer les ambiguïtés

La première action serait de prendre une sauvegarde staging, de conserver une fixture de test séparée et de documenter précisément les versions actives. Il fallait ensuite décider si `RestoCommerce WhatsApp Checkout 0.1.1` reste actif ou si Keleva Addons devient l’unique implémentation. L’extension concurrente n’exposait aucun réglage visible et le panier ne rendait déjà qu’un CTA Keleva ; elle est maintenant **installée mais désactivée** sur le staging, sans suppression, afin d’éliminer la dette de maintenance et le risque futur de doublon.

Le senior vérifierait également la configuration WooCommerce de livraison. La santé du site signale une expédition active sans méthode configurée ; un checkout premium ne peut pas laisser cette incohérence en staging de référence. Les commandes #347/#348 et les réglages du mock n8n doivent être identifiés comme fixtures, puis supprimés ou remis à zéro avant toute démonstration externe.

### P1 — Faire du système visuel une vraie architecture de marque

Le fichier `palette.php` est une bonne base, mais un système premium doit aussi définir les règles d’usage : couleur de fond de page, surface de carte, couleur de texte, texte secondaire, bordure, CTA principal, CTA secondaire, succès, avertissement, erreur, focus, état désactivé et overlay. Chaque composant doit consommer ces rôles, y compris les composants WooCommerce et les emails.

La typographie doit être traitée comme un système : une échelle explicite pour eyebrow, body, prix, H1, H2 et microcopy ; une longueur maximale de ligne ; des règles de césure ; une hiérarchie stable sur desktop et mobile. Il faut éviter que le caractère très compact de certains titres soit uniquement « joli » mais difficile à lire avec des noms produits longs.

La photographie reste un levier premium important. Sur le staging, quatre fixtures 1×1 non attachées et non référencées ont été supprimées ; les fixtures encore attachées à des produits n’ont pas été supprimées. Les trois produits parents qui alimentaient les recommandations 1×1 ont reçu des médias demo dimensionnés, puis la matrice post-média a confirmé zéro image 1×1 sur accueil, panier et commande. Il faut encore imposer un ratio et une direction photo par catégorie, préparer des variantes WebP/AVIF réellement dimensionnées et contrôler les produits sans image en production.

### P2 — Tester le produit comme une expérience complète

La matrice exécutée couvre désormais l’accueil, la boutique, la fiche produit variable, le panier, le checkout et la 404 sur 3 moteurs et 4 viewports : 72 cas complets, puis 36 cas post-média et 12 cas 404 post-correctif. Elle vérifie le statut HTTP, le nombre de landmarks, skip-link/focus, overflow horizontal, images 1×1, erreurs console/réseau, CTA WhatsApp, formulaire checkout et erreurs critiques. Les scénarios coupon, livraison, paiement refusé, e-mails, confirmation et appareils physiques restent à ajouter.

Il faut ajouter une recette visuelle automatisée avec captures de référence, tests de débordement, vérification du focus, navigation clavier et détection d’erreurs console. Pour le parcours WhatsApp, le test réel doit utiliser WhatsApp Business Cloud/Meta et n8n sur une URL HTTPS durable ; `wa.me` seul ne peut pas lire les réponses du client.

### P3 — Industrialiser qualité, performance et sécurité

Le pipeline senior devrait exécuter automatiquement `pnpm check`, tests Vitest avec clé de test injectée par CI, build, lint PHP, PHPCS WordPress, PHPStan si compatible, scan de secrets et audit des dépendances. Il faudrait ajouter des tests Playwright ou équivalent pour les surfaces publiques, Axe pour l’accessibilité et Lighthouse CLI pour les budgets LCP/CLS/TBT. WPScan ou OWASP ZAP doit rester une étape de sécurité séparée, authentifiée et autorisée contre le staging.

Côté performance, la priorité est le LCP/TTFB du panier et du checkout, le chargement de la photo hero, le dimensionnement des images, le CSS render-blocking et les skeletons qui restent visibles trop longtemps. Les budgets doivent être suivis sur mobile et desktop au 75e percentile, puis complétés par des mesures terrain lorsque le site aura du trafic réel [1] [2].

## Outils à utiliser dans le sandbox

| Besoin | Outil recommandé | État actuel | Action senior |
| --- | --- | --- | --- |
| TypeScript/build | pnpm, TypeScript, Vitest | Déjà disponible et vert | Garder en CI avec clé éphémère. |
| PHP | PHP CLI, PHPCS WordPress, PHPStan | PHP disponible ; Composer/PHPCS absents | Ajouter via `composer`/CI, puis lint et analyse statique. |
| Tests navigateur | Playwright | Chromium système, Firefox 153 et WebKit 26.5 installés | 72 cas émulés exécutés sur desktop, iPhone 13, Pixel 7 et iPad ; ajouter Edge/appareils physiques si disponibles. |
| Accessibilité | axe-core/Playwright | Pipeline exécuté | Aucun Axe sérieux/critique ; une alerte mineure `image-redundant-alt` subsiste sur les cartes Woo du panier/checkout. |
| Performance | Lighthouse CLI | Campagne laboratoire historique partielle | Compléter checkout, budgets mobiles et mesures terrain/RUM. |
| Sécurité WordPress | WPScan, ZAP | Non installé/exécuté | Lancer contre staging avec autorisation et rapport séparé. |
| Dépendances | pnpm audit | Déjà exécuté | Suivre l’alerte transitive esbuild côté développement. |
| WordPress | WP-CLI | Absent | Ajouter seulement si accès SSH/Hostinger le permet ; sinon conserver les preuves navigateur/API. |
| Webhooks | mock Flask puis n8n réel | Mock prouvé, n8n réel absent | Remplacer par endpoint HTTPS durable et logs de livraison. |

L’installation d’un outil ne constitue jamais une preuve de conformité. La preuve vient de l’exécution, du résultat, du log conservé et de la reproduction du scénario.

## Résultats du smoke-test senior automatisé

Les défauts historiques ont été rejoués après déploiement et arbitrage :

| Constat | État post-correctif | Preuve ou réserve |
| --- | --- | --- |
| Image logo vendeur WCFM `.wcfmmp_sold_by_container_left > img` sans `alt` | Corrigé sur la fiche auditée | Le script versionné applique `alt=""` et `aria-hidden="true"` aux images WCFM décoratives injectées ; à confirmer sur tous les types de page vendeur. |
| Deux landmarks `<main>` imbriqués sur la boutique | Corrigé | La boutique post-déploiement expose `mainCount=1`. |
| Skip-link `#catalogue` invalide | Corrigé | Le skip-link pointe `#keleva-main`, la cible existe et est focusable ; la 404 a reçu `tabindex=-1` après la première matrice. |
| Recommandations du panier avec images fixture en `1×1` | Corrigé côté rendu | Trois produits parents ont reçu des médias demo ; la matrice post-média mesure zéro image 1×1 sur 36 cas accueil/panier/commande. |
| Alerte Axe `image-redundant-alt` sur cartes Woo | Mineure restante | Le texte alternatif de l’image répète le nom produit adjacent ; aucun impact `serious`/`critical`, mais une correction peut viser zéro violation. |
| Checkout redirigé vers le panier sans cookie | Comportement attendu du test neuf | La matrice documente la redirection et le panier reste accessible ; un scénario avec panier chargé et livraison configurée est encore requis. |
| Erreur réseau de la 404 | Attendue | Le statut HTTP 404 de la route fictive est attendu ; Axe ne remonte aucune violation. |

Le journal historique est `/home/ubuntu/keleva-qa/artifacts/smoke-results-detailed.jsonl`. La matrice actuelle et ses captures sont dans `/home/ubuntu/keleva-qa/artifacts/matrix-2026-08-24/` : `summary-chromium.json`, `summary-firefox.json`, `summary-webkit.json`, `summary-post-media.json` et `summary-post-404.json`. Elle prouve l’émulation des moteurs et des viewports, pas des appareils physiques ni de Safari/Edge réels.

## Verdict senior

Le thème possède déjà une **base premium crédible** : palette centralisée, composition éditoriale, hero réel, composants cohérents et plusieurs variantes de marque. La palette actuelle est facile à trouver et à modifier depuis WordPress ; elle n’est pas cachée dans des réglages dispersés.

Je qualifierais toutefois le niveau actuel de **premium en direction visuelle et validé par émulation, mais pas encore premium certifié en production**. Le staging dispose maintenant d’une seule implémentation WhatsApp active, d’un rendu sans image 1×1 sur les surfaces testées, de corrections a11y ciblées et d’une matrice multi-moteurs sans overflow ni violation Axe sérieuse/critique. Pour passer le cap production, il faut configurer livraison/checkout, traiter l’alerte Axe mineure si l’objectif est zéro violation, compléter la QA physique/lecteur d’écran, les CWV terrain, WhatsApp Cloud/n8n réel et le pentest. Le bon ordre est : **stabilité fonctionnelle → système visuel → expérience mobile → performance → sécurité → production**.

## Références

[1]: https://developers.google.com/search/docs/appearance/core-web-vitals "Google Search Central — Core Web Vitals"

[2]: https://web.dev/articles/vitals "web.dev — Web Vitals"
