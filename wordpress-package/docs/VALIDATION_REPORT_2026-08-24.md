# Rapport de validation technique — Keleva Woo

**Date de campagne :** 24 août 2026
**Environnement :** WordPress de test Hostinger, `https://aliceblue-bison-433987.hostingersite.com/`
**Référence du dépôt initial :** `0644b65e72b6a0782795eaad0edfc54452574b2d`
**Thème actif :** Keleva Woo `0.4.10`
**Extension Addons active :** Keleva Woo Addons `0.5.9`
**Auteur :** Manus AI

## Conclusion exécutive

Le staging exécute désormais le parcours demandé **sans formulaire avant WhatsApp**. Depuis le panier, le visiteur peut cliquer sur « Commander sur WhatsApp — Paiement à la livraison ». Keleva crée d’abord une commande WooCommerce provisoire COD avec les lignes du panier, puis redirige vers une conversation WhatsApp préremplie avec le numéro fourni et le récapitulatif. Le client n’a pas à saisir son nom, sa localisation, son téléphone ni sa date de livraison avant cette redirection.

La commande de recette **#348** a été créée avec cinq lignes, un total de **540 MAD** et le statut initial `pending`. L’événement sortant signé a été reçu par le mock n8n HTTPS avec `signature_valid=true`. Le contrat entrant n8n→WordPress a ensuite été testé avec des données fictives : mauvais secret = 401, signature expirée = 401, champs manquants = 422, événement valide = 200 et passage en `on-hold`, puis même `event_id` rejoué = 200 avec `deduplicated=true`. Le dashboard WooCommerce affiche les données fictives, la localisation, la date de livraison et la note d’activité.

> **Limite importante :** `wa.me` ouvre une conversation et transporte un texte prérempli, mais ne lit pas les réponses du client et ne pilote pas à lui seul un dialogue. L’automatisation réelle qui pose les questions et renvoie les réponses vers WordPress nécessite WhatsApp Business Cloud/Meta, des credentials n8n et un endpoint HTTPS n8n public [8] [9] [10] [11]. Cette partie métier réelle n’est donc pas déclarée comme produite ; seul le contrat WordPress↔mock n8n est prouvé.

La conformité CDC globale reste **partielle**. Les points fonctionnels principaux du parcours panier→WhatsApp→WooCommerce sont démontrés sur le staging ; la matrice Chromium/Firefox/WebKit et les viewports mobiles émulés ont été exécutés, mais les appareils physiques, l’API WooCommerce réelle de la console, les tests de production Meta/n8n, le pentest, l’expédition et une campagne CWV complète restent ouverts. Aucune carte, aucun compte Stripe, aucune transaction et aucun message WhatsApp n’ont été utilisés.

## Tableau de décision

| Domaine | État final | Preuve ou réserve |
| --- | --- | --- |
| Thème Keleva Woo | **Prouvé actif** | Keleva Woo `0.4.10` actif ; accueil, boutique, fiche produit, panier, checkout et 404 répondent. Neuf palettes disponibles ; `velora` reste le défaut. |
| Accueil et catalogue | **Corrigé et vérifié** | Fallback vers les produits WooCommerce publiés ; 9 cartes visibles ; hero réel `demo-douceur-quartier.png`, média 1×1 exclu. |
| Keleva Woo Addons | **Prouvé actif** | Installation fraîche puis activation de `0.5.9`. |
| Rate limiting | **Historique prouvé, régression à investiguer** | Ancienne campagne : 5×401 puis 429. Après réinstallation `0.5.9`, une courte répétition a obtenu 6×401 avec `X-RateLimit-Limit: 5`, sans 429 ; l’identité CDN ou la fenêtre doit être analysée avant une nouvelle assertion. |
| Stripe | **Compatible en état non configuré** | Plugin officiel WooCommerce Stripe Gateway `10.9.0` actif, sans compte ni clé ; aucune transaction exécutée. |
| Panier sans formulaire | **Prouvé** | CTA présent sur `/panier/`, aucune donnée client demandée avant clic. |
| Commande COD provisoire | **Prouvé** | Commandes #347 et #348 créées depuis le panier, méthode COD et état WhatsApp enregistrés. |
| Redirection WhatsApp | **Prouvé sans envoi** | Page `api.whatsapp.com` affiche le numéro, le récapitulatif, le total et la demande de nom/localisation/date ; aucun bouton d’envoi n’a été pressé. |
| Webhook sortant vers mock n8n | **Prouvé sur endpoint temporaire** | Payload JSON reçu avec signature HMAC valide ; mock local exposé par HTTPS temporaire. |
| Webhook entrant vers WordPress | **Prouvé sur staging** | HMAC du corps brut, timestamp, event ID, validation 401/422/200 et déduplication testés. |
| WhatsApp Business Cloud réel | **Non réalisé** | Aucun compte Meta, token, numéro Cloud API ou workflow n8n de production fourni. |
| Console contre API WooCommerce réelle | **Partiellement prouvé** | Checks locaux OK ; aucune Consumer Key/Secret restreinte fournie. |
| Performance/CWV | **Partiellement prouvé** | Laboratoire Lighthouse incomplet, surtout panier lent ; pas de RUM ni d’INP terrain. |
| Accessibilité couleur des 4 nouvelles palettes | **Prouvé par Axe** | Après `accent-text` et correction ciblée des textes `success`, les quatre previews obtiennent zéro `color-contrast: serious` et zéro nœud contrasté en échec. Les autres dimensions d’accessibilité restent à compléter. |
| Responsive/cross-browser | **Prouvé par émulation, appareils physiques ouverts** | 72 cas Playwright : Chromium système, Firefox 153 et WebKit 26.5 ; desktop, iPhone 13, Pixel 7 et iPad émulés ; zéro overflow et zéro violation Axe sérieuse/critique. Cela ne prouve pas Safari/Chrome physiques, Edge ni les appareils réels. |
| Pentest | **Non conclu** | Lint, tests et scan de secrets réalisés ; WPScan/ZAP indépendant non réalisé. |

## 1. Déploiement et contrôles locaux

Le thème Keleva Woo `0.4.10` est actif sur le staging. L’extension Addons a été remplacée proprement : la `0.5.8` a été désactivée et supprimée, puis la `0.5.9` a été installée fraîchement et activée. La page Extensions affiche l’action « Désactiver » et la version `0.5.9`.

Les contrôles locaux finaux ont été exécutés dans `merchant-console`, qui est le sous-projet portant le `package.json`. `pnpm check` et `pnpm build` passent. Les **25 tests Vitest passent avec une clé de chiffrement éphémère** ; sans cette variable, deux tests de sécurité échouent volontairement pour signaler une configuration incomplète. Le lint PHP couvre 57 fichiers et ne remonte aucune erreur. `git diff --check` et le scan de secrets du paquet WordPress sont propres. L’audit global conserve toutefois l’alerte transitive `esbuild@0.18.20` via l’outillage de développement `drizzle-kit`; `pnpm audit --prod` reste propre.

Le dépôt contient encore des modifications locales non poussées correspondant aux corrections du thème, aux réglages et au module WhatsApp. Aucun secret de test, cookie, fichier `.env`, Consumer Key ou clé Stripe n’a été ajouté au dépôt.

## 2. Palettes de marque Keleva

Les cinq palettes existantes ont été conservées et quatre variantes ont été ajoutées dans `inc/palette.php` : `obsidienne-cuivree`, `ivoire-encre`, `argile-sombre` et `perle-graphite`. Le token `on_accent` existait déjà dans le système actuel et a été réutilisé ; les nouvelles palettes ont également reçu les clés internes complémentaires attendues par le CSS (`surface_card`, `surface_media`, `subtle`, `media`, `benefit`, `success_wash`, `warning_wash`, `danger_wash`, `shadow_tint` et `on_ink`) afin d’éviter des index manquants dans le générateur de variables.

Le Customizer affiche désormais neuf choix dans **Apparence → Personnaliser → Keleva — Apparence → Palette active**. Les quatre URLs de preview publiques injectent leur classe et leur accent respectifs. Sans paramètre de preview, le DOM final confirme `keleva-palette--velora`, `--bg:#F7F4EE` et `--accent:#A83B2B` ; aucune palette active par défaut n’a été changée.

La validation esthétique est positive : Obsidienne Cuivrée et Argile Sombre ont une direction sombre atelier/luxe, Ivoire Encre est éditoriale et Perle Graphite raffinée. Les six corrections de tokens ont été appliquées puis déployées. La correction structurelle finale ajoute `accent_text` uniquement à Obsidienne (`#C97A3A`) et Argile (`#8C9A6C`), émet `--accent-text`, et remplace les usages textuels d’accent fort par ce token avec fallback historique ; les fonds de boutons hover continuent d’utiliser `accent-strong`. Les deux libellés de réassurance `success` des palettes sombres utilisent également une teinte mixée ciblée afin de dépasser le seuil AA sans modifier le token global `success`. Après purge LiteSpeed et cache-busting, la recette Axe finale obtient `0` violation `color-contrast: serious` et `0` nœud concerné sur les quatre previews : Obsidienne Cuivrée, Ivoire Encre, Argile Sombre et Perle Graphite. Les quatre palettes passent donc la validation automatisée du contraste couleur ; cela ne constitue pas à lui seul une certification complète d’accessibilité ou une validation production globale.

## 3. Correction de l’accueil Keleva

La cause du catalogue vide était la sélection exclusive de huit slugs Velora qui n’existaient pas dans le catalogue de test. `inc/cache.php` conserve cette sélection quand elle est disponible, mais bascule vers les produits WooCommerce publiés les plus récents lorsqu’elle est vide. Le transient a été versionné afin d’éviter de servir l’ancien résultat vide.

`front-page.php` ignore les médias fixture de dimensions 1×1 et choisit le premier produit disposant de métadonnées image réelles. Le contrôle public du 24 août affiche **9 pièces dans la sélection**, un hero photographique réel et les cartes produits du catalogue de staging. La preuve visuelle récente est `/home/ubuntu/screenshots/aliceblue-bison-4339_2026-08-24_19-07-07_5049.webp`.

## 4. Stripe et checkout classique

Le plugin officiel **WooCommerce Stripe Gateway `10.9.0`** est installé et actif. Il n’a été associé à aucun compte Stripe, aucune clé n’a été saisie et aucune carte de test ou réelle n’a été utilisée. L’objectif demandé était la compatibilité du plugin avec le thème, non l’exécution d’un paiement. WooCommerce documente bien un mode test séparé et des cartes de test pour une campagne ultérieure [5] [6].

Le résultat doit donc être formulé précisément : **la présence et l’activation du plugin cohabitent avec Keleva Woo sans erreur visible sur les pages contrôlées, mais la compatibilité de paiement n’est pas validée par une transaction**. Le checkout classique reste accessible et les réglages de paiement affichent le plugin sans compte connecté. La santé WooCommerce signale toujours qu’une expédition est active sans méthode configurée ; ce point empêche une validation complète du checkout opérationnel.

## 5. Parcours panier → WhatsApp → commande WooCommerce

Le module `class-whatsapp-order.php` ajoute au panier un lien WordPress same-origin noncé. Cette décision corrige l’échec de la première version REST, qui répondait « Le panier est vide » parce que la session WooCommerce n’était pas disponible dans le contexte REST. Le lien serveur conserve la session panier avant de créer la commande et évite de demander des coordonnées à l’avance.

La première commande de recette **#347** a été créée avec le panier initial : cinq références, six unités, total de 452 MAD, statut `pending`, méthode de paiement COD et état `_keleva_whatsapp_state=awaiting_customer_details`. La seconde commande **#348**, créée après passage de Chicken katsu curry à quantité 3, contient cinq lignes, sept unités et un total de 540 MAD.

Le lien a redirigé vers `api.whatsapp.com`, qui affiche la conversation avec le numéro de recette configuré et le message prérempli. La version finale affiche le total en texte lisible, par exemple `540,00 MAD`, sans entité HTML de devise. Le message demande ensuite à l’automatisation de recueillir le nom, la localisation et la date de livraison. La capture finale est `/home/ubuntu/screenshots/api_whatsapp_2026-08-24_19-06-43_5232.webp`.

Aucun bouton « Open app » ou « Continue to WhatsApp Web » n’a été pressé. Le système n’a donc envoyé aucun message réel. Le panier n’est pas automatiquement vidé après création ; ce comportement est volontaire dans la recette pour préserver la sélection et devra être confirmé comme règle métier avant production, avec une stratégie de retry et de prévention des commandes abandonnées.

## 6. Contrat sortant vers n8n mock

Un mock local Flask a été préparé dans `/home/ubuntu/mock_n8n_whatsapp.py`, puis exposé temporairement par une URL HTTPS de sandbox. Le dashboard Keleva a enregistré le numéro WhatsApp, l’URL HTTPS du mock et un secret de test chiffré dans WordPress. Le mock vérifie `X-Keleva-Signature` avec HMAC-SHA256 et journalise les événements ; aucune valeur secrète n’est inscrite dans le dépôt ou dans le rapport.

L’événement `keleva.whatsapp.order.created` de la commande #348 a été reçu avec une signature valide. Le payload contient l’identifiant de commande, le statut, la devise `MAD`, les cinq lignes, les quantités, les totaux et l’URL `wa.me`. Le mock a donc prouvé le contrat applicatif et la signature sortante, mais pas la disponibilité durable d’un n8n de production. L’URL temporaire cessera d’être utilisable avec l’arrêt du sandbox et doit être remplacée par l’URL publique HTTPS du workflow n8n réel.

WooCommerce fournit par ailleurs ses propres webhooks signés et journalisés, avec des identifiants de livraison et des mécanismes de désactivation après échecs consécutifs [3] [4]. Le présent module Keleva utilise un contrat dédié n8n→WordPress ; il ne doit pas être présenté comme un remplacement des webhooks cœur WooCommerce pour les synchronisations produits, stock ou remboursement.

## 7. Contrat entrant n8n → WordPress

La v0.5.9 ne se limite plus à un header secret statique. L’endpoint `POST /wp-json/keleva/v1/whatsapp/order/{id}` exige désormais :

| Contrôle | Résultat observé |
| --- | --- |
| HMAC sur `timestamp.event_id.corps_brut` | Signature correcte acceptée ; signature incorrecte rejetée. |
| Timestamp | Événement vieux de plus de 600 secondes rejeté en 401. |
| Event ID | Format contrôlé puis mémorisé sur la commande. |
| Champs obligatoires | Nom, localisation et date absents rejetés en 422. |
| Mise à jour valide | 200, statut `on-hold`, données billing/shipping et métadonnées de livraison enregistrées. |
| Retry du même event ID | 200 idempotent avec `deduplicated=true`, sans seconde application métier. |

Le test reproductible est `/home/ubuntu/test_whatsapp_inbound_hmac.py`. Pour la commande #348, la fiche WooCommerce montre le téléphone de recette, le nom fictif, la localisation `Casablanca — recette HMAC`, la date de livraison et la note privée « Informations reçues par WhatsApp ». La preuve textuelle de la note a été obtenue depuis l’interface d’édition de commande ; la capture est `/home/ubuntu/screenshots/aliceblue-bison-4339_2026-08-24_18-57-17_2271.webp`.

Les valeurs utilisées sont exclusivement des valeurs de recette. Elles ne représentent aucun client réel et les commandes #347/#348 doivent être supprimées ou conservées comme fixtures selon la procédure de remise à zéro du staging.

## 8. Rate limiting et sécurité

La campagne initiale sur Addons `0.5.6` a prouvé cinq réponses HTTP 401 suivies d’une sixième réponse HTTP 429 avec `Retry-After`, `Cache-Control: no-store`, `X-RateLimit-Limit: 5` et `X-RateLimit-Remaining: 0`. Les preuves brutes sont conservées dans `/home/ubuntu/keleva-proof-rate-limit-2026-08-24/`.

Après l’installation fraîche de `0.5.9`, une répétition courte a obtenu six réponses HTTP 401. Les réponses portent toujours `X-RateLimit-Limit: 5` et `Cache-Control: ... no-store`, mais la sixième ne renvoie pas 429. Les temps d’arrivée et la couche `hcdn` sont visibles dans les en-têtes ; l’identité déterminée par `REMOTE_ADDR`, le cache/CDN ou la fenêtre de persistance doivent être vérifiés. La nouvelle preuve est conservée dans `/home/ubuntu/keleva-proof-rate-limit-v059-2026-08-24/`.

Conclusion honnête : le comportement 5→429 est **historiquement prouvé**, mais sa régression après réinstallation n’est pas prouvée dans la fenêtre finale. Il est interdit de déclarer ce point définitivement conforme sans reproduire le test avec une identité stable ou vérifier les transients côté serveur.

## 9. Console marchande et API WooCommerce

La console React/TypeScript reste saine localement : checks, tests avec clé éphémère, build, lint et audit de production passent. Aucune Consumer Key / Consumer Secret WooCommerce restreinte n’a été fournie ; les imports, synchronisations, workers, rollback et webhooks de la console contre l’instance WooCommerce réelle ne sont donc pas validés.

La vulnérabilité transitive `esbuild@0.18.20` reste attachée à la chaîne de développement/migration `drizzle-kit`. Le paquet n’est pas signalé dans l’audit de production, mais le risque de développement doit être suivi lors des mises à jour amont.

## 10. Performance et Core Web Vitals

La campagne Lighthouse a utilisé Chromium en laboratoire. Elle comprend 10 runs pour l’accueil, la boutique et la fiche produit, et 5 runs pour le panier ; le checkout n’a pas obtenu une campagne complète. Les valeurs p75 historiques sont :

| Page | Runs | Performance p75 | Accessibilité p75 | LCP p75 | CLS p75 | TBT p75 |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Accueil | 10 | 93,75 | 100 | 2 729 ms | 0,000 | 0 ms |
| Boutique | 10 | 85,00 | 94 | 3 526 ms | 0,000 | 0 ms |
| Fiche produit | 10 | 84,00 | 94 | 3 577 ms | 0,000 | 0 ms |
| Panier | 5 | 57,00 | 98 | 7 301 ms | 0,000 | 394 ms |
| Checkout | 0 | — | — | — | — | — |

Les seuils « bons » communément utilisés sont LCP ≤ 2,5 s, INP ≤ 200 ms et CLS ≤ 0,1, appréciés au 75e percentile et segmentés au minimum par type d’appareil [1] [2]. L’accueil dépasse légèrement le seuil LCP et le panier est nettement plus lent. Lighthouse ne remplace pas des mesures terrain/RUM et ne mesure pas l’INP réel sans interaction utilisateur [1] [2].

## 11. Accessibilité, responsive et micro-interactions

Les trois défauts ciblés ont été corrigés et déployés : le skip-link pointe vers `#keleva-main`, les templates principaux rendent la cible focusable, l’archive boutique ne crée plus de double landmark `<main>`, et le script versionné marque décoratives les images WCFM sans `alt` lorsqu’elles sont injectées. La 404 a reçu un correctif complémentaire `tabindex="-1"` après la matrice, puis son focus a été vérifié. Sur la fiche produit auditée, l’image WCFM observée possède désormais `alt=""` et `aria-hidden="true"`.

La matrice Playwright post-média couvre 36 cas sur accueil/panier/commande et la matrice complète couvre 72 cas sur accueil, boutique, fiche produit, panier, checkout et 404. Chromium système, Firefox 153 et WebKit 26.5 ont été exécutés sur desktop, iPhone 13, Pixel 7 et iPad émulés. Les 72 cas ne montrent aucun débordement horizontal, aucune erreur critique et aucune violation Axe `serious` ou `critical`. Les 24 cas panier/checkout post-média ont zéro image 1×1 ; il reste uniquement une alerte Axe mineure `image-redundant-alt` sur les cartes WooCommerce, car leur texte alternatif répète le nom produit adjacent.

Ces résultats sont des émulations de moteurs et de viewport Linux : ils ne constituent pas une validation de Safari iOS, Chrome Android, Edge ou d’un appareil physique. Une revue clavier/lecteur d’écran humain, le focus visible, le zoom, l’orientation réelle et les scénarios métier secondaires restent à compléter.

## 12. Bascules de thème, WCFM et contenus

Keleva Woo est resté actif pendant la campagne finale et aucun second basculement n’a été observé pendant les contrôles publics. Un événement `theme_switch` précédent a documenté le passage de RestoCommerce vers Keleva Woo. Les logs Hostinger/PHP/cron permettant d’attribuer les anciennes bascules répétées ne sont pas accessibles dans cette session.

L’alerte WCFM mentionnant `themes/restocommerce/storefront.php` reste visible dans l’administration. Elle peut correspondre à un override vendeur intentionnel ; ce point est distinct du thème storefront audité. L’extension tierce `RestoCommerce WhatsApp Checkout` `0.1.1` a été inspectée : aucun lien de réglages visible, aucun CTA concurrent dans le panier, puis elle a été désactivée réversiblement. Après désactivation, le panier conserve exactement le CTA Keleva (`a.keleva-whatsapp-order-button`) et le checkout classique. Le plugin reste installé pour une éventuelle comparaison, mais n’est plus actif sur le staging. Les textes visibles sont cohérents avec l’identité Velora et aucun Lorem Ipsum évident n’a été observé ; les e-mails transactionnels, les pages compte, les résultats de recherche vide et les appareils physiques restent ouverts.

## 13. Preuves et artefacts

| Artefact | Emplacement |
| --- | --- |
| Rapport courant | `/home/ubuntu/keleva-woocommerce-suite/wordpress-package/docs/VALIDATION_REPORT_2026-08-24.md` |
| Journal consolidé | `/home/ubuntu/keleva-audit-findings.md` |
| Mock n8n | `/home/ubuntu/mock_n8n_whatsapp.py` |
| Journal du mock | `/home/ubuntu/mock_n8n_whatsapp_events.jsonl` |
| Test entrant HMAC | `/home/ubuntu/test_whatsapp_inbound_hmac.py` |
| Rate limit historique | `/home/ubuntu/keleva-proof-rate-limit-2026-08-24/` |
| Rate limit régression v0.5.9 | `/home/ubuntu/keleva-proof-rate-limit-v059-2026-08-24/` |
| Rapports Lighthouse | `/home/ubuntu/keleva-proof-lighthouse-2026-08-24/` |
| Capture accueil finale | `/home/ubuntu/screenshots/aliceblue-bison-4339_2026-08-24_19-07-07_5049.webp` |
| Capture WhatsApp finale | `/home/ubuntu/screenshots/api_whatsapp_2026-08-24_19-06-43_5232.webp` |
| Capture fiche commande #348 | `/home/ubuntu/screenshots/aliceblue-bison-4339_2026-08-24_18-57-17_2271.webp` |
| Audit médias staging | `/home/ubuntu/keleva-media-audit-2026-08-24.md` — 44 → 40 médias ; quatre fixtures 1×1 non attachées supprimées ; fixtures attachées conservées |
| Matrice QA complète | `/home/ubuntu/keleva-qa/artifacts/matrix-2026-08-24/summary-chromium.json`, `summary-firefox.json`, `summary-webkit.json` — 72 cas, 3 moteurs, 4 viewports |
| Matrice post-média | `/home/ubuntu/keleva-qa/artifacts/matrix-2026-08-24/summary-post-media.json` — 36 cas, zéro image 1×1, zéro overflow, zéro Axe sérieux/critique |
| Matrice post-404 | `/home/ubuntu/keleva-qa/artifacts/matrix-2026-08-24/summary-post-404.json` — 12 cas 404, Axe zéro ; HTTP 404 attendu |
| Script matrice | `/home/ubuntu/keleva-qa/matrix.mjs` |
| Smoke-test final | `/home/ubuntu/keleva-qa/smoke.mjs` — sortie finale exécutée après le correctif 404 |
| Archive thème a11y finale | `/home/ubuntu/keleva-woo-0.4.10-a11y-premium-404.zip` — SHA-256 `8c684142730bcb6718c8dcce4392120ba46e7c00ad6d342b9d55f848341b542c` |
| Archive thème fallback | `/home/ubuntu/keleva-woo-0.4.10-home-fallback-v3.zip` — SHA-256 `c1c5c594f7155873bd6c90e60b257c7e2f3a0f481cdfb6303ca4c5ed3b975417` |
| Archive Addons durcie | `/home/ubuntu/keleva-woo-addons-0.5.9-whatsapp-hardening.zip` — SHA-256 `d2af001d0f1cab116dfe81731d3b92d9620c8a753523b2d4f180cc91d84dc08f` |

## Appendice — Keleva Manager wp-admin, lot staging 0.5.11 — 2026-08-25
Le Keleva Manager intégré directement dans wp-admin a été déployé et validé sur le staging uniquement. Le menu est visible sous WooCommerce ; la page rend les actions Ajouter un plat, Modifier l’apparence, Produits et stock, Commandes et les réglages avancés. Neuf palettes sont disponibles et Velora a été restaurée comme palette active finale. Le contrôle DOM a relevé aucun overflow horizontal global.

La fixture fictive WooCommerce #361 (`Fixture QA Keleva Manager 20260825`) a été créée en brouillon à 19,90 MAD avec stock 7, modifiée avec succès à 21,90 MAD et stock 5 après correction du défaut `request['id']`, puis restaurée à 19,90 MAD et stock 7. L’audit staging contient `product_created` et deux `product_updated` pour l’ID 361. La fixture a ensuite été déplacée à la corbeille et ne figure plus dans les produits actifs.

Le changement temporaire vers Onyx Doré puis le retour vers Velora ont répondu HTTP 200 avec succès. Une tentative palette avec nonce invalide a été rejetée HTTP 403, sans mutation. Les contrôles locaux de 0.5.11 sont verts : 33 fichiers PHP sans erreur, syntaxe JavaScript valide, `git diff --check` propre. Archive séparée : `/home/ubuntu/keleva-woo-addons-0.5.11-manager.zip`, SHA-256 `3da3f58ee654a1d46acc1b781e05c7b29c2a2c01246f41dad502aa08ad95ac86`.

Rapport détaillé et preuves : `/home/ubuntu/keleva-qa/artifacts/keleva-manager-wpadmin-2026-08-25/REPORT.md`. Ce lot est validé pour le staging, mais ne constitue ni une validation intégrale du CDC ni une autorisation de production. Aucune production, aucun paiement Stripe réel, aucun message WhatsApp réel et aucune donnée client réelle n’ont été utilisés.

## Actions indispensables avant production

Il faut d’abord remplacer le mock par un workflow n8n hébergé sur une URL HTTPS durable, connecter WhatsApp Business Cloud avec les credentials Meta adéquats, configurer le webhook de messages entrants, corréler chaque conversation à `order_id`, dédupliquer les identifiants de message et superviser les erreurs. Aucun secret de production ne doit être copié dans Git ou dans les logs.

Il faut ensuite comprendre la non-reproduction du rate limiting v0.5.9 avec une identité réseau stable, configurer au moins une méthode d’expédition de test, réaliser une campagne checkout complète sans carte réelle, fournir une clé WooCommerce API restreinte pour la console, relancer Lighthouse sur toutes les surfaces et mesurer le terrain mobile.

Enfin, il faut compléter la validation humaine sur appareils physiques et lecteurs d’écran, traiter l’alerte mineure `image-redundant-alt` si l’objectif est zéro violation Axe, vérifier les e-mails et pages secondaires, obtenir les logs Hostinger pour les bascules de thème et exécuter un pentest WPScan/ZAP ou équivalent. **Le projet ne doit pas être déclaré conforme à 100 % au CDC avant la clôture documentée de ces points.**

## Références

[1]: https://developers.google.com/search/docs/appearance/core-web-vitals "Google Search Central — Understanding Core Web Vitals and Google search results"

[2]: https://web.dev/articles/vitals "web.dev — Web Vitals"

[3]: https://woocommerce.com/document/webhooks/ "WooCommerce — Webhooks"

[4]: https://developer.woocommerce.com/docs/apis/rest-api/v2/webhooks/ "WooCommerce Developer Docs — Webhooks API"

[5]: https://woocommerce.com/document/stripe/customer-experience/testing/ "WooCommerce — Stripe customer experience and testing"

[6]: https://docs.stripe.com/testing-use-cases "Stripe — Testing use cases"

[7]: https://github.com/woocommerce/woocommerce-gateway-stripe "WooCommerce — Stripe Gateway official repository"

[8]: https://docs.n8n.io/integrations/builtin/app-nodes/n8n-nodes-base.whatsapp "n8n — WhatsApp Business Cloud node"

[9]: https://docs.n8n.io/integrations/builtin/credentials/whatsapp "n8n — WhatsApp Business Cloud credentials"

[10]: https://n8n.io/integrations/webhook/and/whatsapp-business-cloud/ "n8n — Webhook and WhatsApp Business Cloud integration"

[11]: https://developers.facebook.com/documentation/business-messaging/whatsapp/webhooks/reference/messages "Meta — WhatsApp messages webhooks reference"
