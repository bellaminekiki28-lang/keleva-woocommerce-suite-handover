# Matrice de traçabilité — Keleva Woo ultra premium

> **Règle de décision.** « Conforme » signifie : code local réel, recette reproductible et preuve archivée. « Partiel » interdit toute proposition de mutation Hostinger avant achèvement local. Cette matrice couvre le cahier des charges v1.0 du 23 août 2026.[^cdc]

> **Addendum d’audit — 24 août 2026.** Ce document est une matrice historique de traçabilité, non un verdict « 100 % conforme ». Les contrôles staging et sécurité réalisés après cette photographie doivent être lus avec l’inventaire Hostinger : tout statut local demeure insuffisant lorsqu’une dépendance réelle ou un risque de stabilité n’est pas démontré.

| Domaine postérieur à la matrice | État factuel | Preuve exécutée | Limite ou écart restant |
| --- | --- | --- | --- |
| Thème staging | **Actif au dernier contrôle : Keleva Woo 0.4.10** | Administration WordPress après Addons 0.5.5 ; E2E Chromium et recette sans JavaScript vertes lors de l’état Keleva | Des bascules antérieures non sollicitées vers RestoCommerce ont été constatées ; cause et stabilité temporelle non prouvées. |
| Traçabilité de bascule | **Instrumentée et prouvée** | Addons 0.5.5, journal protégé : `theme_switch`, acteur `wp-user-1`, contexte `admin`, RestoCommerce → Keleva Woo ; aucune IP ni secret | L’instrumentation ne permet pas d’attribuer les événements antérieurs à son déploiement ; il faut rapprocher toute future bascule des journaux Hostinger. |
| Surface WordPress publique | **Vecteurs testés durcis** | 0.4.10 : REST utilisateurs et sondes auteur `404`, XML-RPC `403`, `X-Pingback` absent, fichiers sensibles testés `403` ; lint, PHPCS, PHPStan ciblé et test WordPress verts | Absence de pentest tiers, de WAF démontré et de rate limiting applicatif validé. |
| Widgets et SSR | **Parcours ciblés prouvés** | Staging Chromium : favori, Wishlist, Compare, Mega Menu, Product Tabs, Checkout Shell et nettoyage panier ; recette `javaScriptEnabled:false` verte | L’E2E staging est Chromium seulement ; appareils physiques et toutes les combinaisons tierces ne sont pas couverts. |
| Régression locale | **Verte sur périmètre exécuté** | Chromium, Firefox et WebKit ; 26 passes Axe, aucune violation ; Plugin Check Addons 0.5.5 sans erreur | PHPStan global demeure hors preuve faute de mémoire ; les contrôles ciblés ne l’équivalent pas. |
| Commerce externe | **Non validé** | Livraison Maroc/MAD testée ; aucun paiement fictif créé | Passerelle Visa/moyen local, clés sandbox, Merchant Center, credentials Woo/Webhooks réels, RUM p75 et CDN/Redis restent à fournir ou exécuter. |

| État | Nombre | Décision |
| --- | ---: | --- |
| Conforme et prouvé | 50 | À synchroniser dans le prochain package local. |
| Partiel | 2 | À terminer localement et re-tester. |
| Conditionnel / hors périmètre | 2 | À documenter, sans simulation commerciale. |

## Exigences fonctionnelles R1–R43

| ID | Exigence contractuelle synthétique | État | Sources locales | Recette / preuve | Écart restant |
| --- | --- | --- | --- | --- | --- |
| R1 | Feuille mobile plein écran, animation et mouvement réduit | Conforme | Console native, `keleva-mobile-sheet-v1` | Playwright 360/390/430 | — |
| R2 | En-tête deux lignes, cible 44 px et titre tronqué | Conforme | `enhance-mobile-sheet-header.mjs` | Assertions CSS mobile | — |
| R3 | Action principale collante dans les feuilles | Conforme | CSS console mobile | Captures/QA mobile | — |
| R4 | Confirmation tactile sans `confirm()` | Conforme | `enhance-confirmation-console.mjs` | Playwright confirmation 360 px | — |
| R5 | État de progression photo | Conforme | `enhance-photo-progress-console.mjs` | Rendu console local | — |
| R6 | QA portrait 360/390/430 | Conforme | `qa/e2e.mjs` | `playwright-axe-report.json` | — |
| R7 | Écran catégories avec couverture, compteur, visibilité | Conforme | `class-category-service.php`, console | API + Playwright création/import/suppression | Couverture exclusive nettoyée avec la catégorie de recette. |
| R8 | CRUD catégories et suppression protégée | Conforme | Endpoint, service catégories | `category-api-test.sh` | — |
| R9 | Réordonnancement tactile DnD ou ↑/↓ | Conforme | API `categories/order`, console | API + Playwright bouton ↑ | Contrôles accessibles ↑/↓ validés. |
| R10 | Sélecteur catégorie structuré dans produit | Conforme | Console, service catégories | QA catégories/produit | — |
| R11 | Modèles de groupes par catégorie | Conforme | `class-category-service.php`, `class-product-options.php` | Test catégories API | — |
| R12 | Proposition des modèles, liberté de personnaliser | Conforme | Console, options | Test API + QA options | — |
| R13 | Source personnalisée et compteur d’usage | Conforme | `class-product-options.php` | Test catégories API | — |
| R14 | Limite 1–4, libellé clair, aperçu client | Conforme | `enhance-option-console.mjs` | Playwright `optionExperience` | — |
| R15 | Cases imposées si max > 1 | Conforme | Console native | Assertion Playwright | — |
| R16 | Inclus/supplément explicite en console | Conforme | Console native | QA options | — |
| R17 | Inclus/supplément cohérent storefront | Conforme | Moteur options, CSS | Quick view multi-moteurs | — |
| R18 | Cinq vignettes composites de palette | Conforme | `palette.php`, console | QA : cinq aperçus | — |
| R19 | Prévisualisation storefront avant application | Conforme | `palette.php`, console apparence | Playwright : survol Onyx → iframe `keleva_palette_preview=onyx-gold`, Velora inchangé avant confirmation, reset ; Axe 0 violation | Aucune écriture avant confirmation ; contrat PHP : `storedPalette=velora` après reset. |
| R20 | `theme_mod` pilote les tokens globaux | Conforme | Endpoint apparence, `palette.php` | `appearance-api-test.sh` | — |
| R21 | Confirmation avant/après | Conforme | Console apparence | Playwright Onyx/reset | — |
| R22 | Contraste AA des cinq palettes | Conforme | `palette-contract-test.php` | Contrat + Axe | — |
| R23 | Retour Velora immédiat | Conforme | API/console apparence | Playwright reset | — |
| R24 | Logo Onyx contrasté | Conforme | `palette.php`, CSS | Assertion Playwright | — |
| R25 | Couleurs codées en dur éliminées | Conforme | CSS thème/extension, audit | `AUDIT_JETONS_COULEUR_20260823.md` | Exemptions : palettes, fallbacks, swatches éditoriaux. |
| R26 | Drawer desktop/mobile et fermeture swipe | Conforme | `header.php`, `storefront.js` | Playwright Chromium/Firefox/WebKit : balayage tactile horizontal ferme le tiroir et restitue le focus ; Axe 0 violation | Geste ignore les contrôles interactifs et les mouvements majoritairement verticaux. |
| R27 | Ouverture après ajout panier | Conforme | `storefront.js` | QA drawer | — |
| R28 | Quantité, sous-total, actions, cross-sell | Conforme | Endpoint `keleva/v1/cart/cross-sells`, tiroir storefront | Playwright Chromium/Firefox/WebKit : association WooCommerce réelle, recommandation rendue puis ajoutée au panier ; Axe 0 violation | Les recommandations n’apparaissent qu’à partir des associations cross-sell WooCommerce configurées. |
| R29 | Drawer conforme aux tokens | Conforme | `style.css` | Axe/QA | — |
| R30 | Recherche instantanée sans rechargement | Conforme | `header.php`, `storefront.js` | QA 3 moteurs | — |
| R31 | Quick view et Acheter maintenant | Conforme | `storefront.js` | Chromium variation/options → checkout | Pas de paiement simulé. |
| R32 | Fermeture conserve contexte catalogue | Conforme | Dialog natif | QA quick view | — |
| R33 | Préchargement hover et apparition mobile | Conforme | `storefront.js` | Playwright Chromium/Firefox/WebKit : `IntersectionObserver` mobile, ressource API observée et marqueur de préchargement ; Axe 0 violation | Hover/focus desktop conservés ; observation limitée aux contextes mobiles/coarse pointer. |
| R34 | Checkout invité | Conforme | `checkout.php` | Contrat checkout + QA | — |
| R35 | Autocomplétion adresse | Partiel | `checkout.php` | Contrat attributs | Aide navigateur ; fournisseur réel absent. |
| R36 | Paiement express si passerelle compatible | Conditionnel | Checkout WooCommerce | Aucun faux bouton | Requiert passerelle et identifiants. |
| R37 | Panier persistant | Conforme | Session checkout | QA `persistentCart` | — |
| R38 | Liste commandes et filtres métier | Conforme | API orders, console ventes | Playwright : filtres Toutes/attente/préparation/terminées | — |
| R39 | Détail commande, options, adresse, expédition | Conforme | API `orders/{id}`, console ventes | Playwright commande temporaire | Adresse, livraison, paiement et choix de ligne rendus. |
| R40 | Badge notifications commandes/ruptures | Conforme | API summary, console ventes et notifications | Playwright : commande temporaire en attente + produit en rupture → badge et libellé accessible | — |
| R41 | CA jour/semaine, attente, meilleures ventes | Conforme | API KPI, console ventes | Playwright KPI et commande temporaire | Jour, semaine, attente et top 3 calculés localement. |
| R42 | Coupons : type, montant, expiration | Conforme | API coupons, console ventes | API création avec `date_expires` + Playwright champ `type=date` | — |
| R43 | Multi-utilisateur à droits limités | Hors périmètre | — | — | Évolution future explicitement exclue. |

## Exigences transverses S1–S11

| ID | Exigence | État | Sources locales | Recette / preuve | Écart restant |
| --- | --- | --- | --- | --- | --- |
| S1 | Sessions HTTP-only, CSRF, Origin | Conforme | `class-dashboard-endpoint.php` | `session-http-test.sh` | — |
| S2 | Rotation de clé, HMAC, audit | Conforme | Endpoint, journal audit | Recette session/audit | — |
| S3 | Chargement/succès/erreur visuels | Conforme | Console native, patch `keleva-console-skeleton-v2` | Playwright : réponse catalogue retardée, status accessible et `aria-busy`, retrait après contenu réel ; Axe 0 violation | Réduit automatiquement l’animation selon `prefers-reduced-motion` et expose une erreur après 15 s. |
| S4 | Multi-photo, ordre, recadrage 1:1 | Partiel | Galerie thème, endpoint image | 3 médias, miniatures QA 3 moteurs | Upload multiple/réordonnancement/crop marchand absents. |
| S5 | Pagination/recherche catalogue à l’échelle | Conforme | Endpoint summary, console | 26 brouillons temporaires, QA `catalogPagination` | Fixtures nettoyées. |
| S6 | Devise configurable | Conforme | Console native | API currency + QA | Devise WooCommerce héritée. |
| S7 | Jetons complets | Conforme | CSS thème/extension | Audit + QA post-tokenisation | — |
| S8 | E-mails sur palette active | Conforme | `palette.php` | `email-palette-contract-test.php` | Aucun e-mail réel envoyé. |
| S9 | Thank-you, 404 et états vides | Conforme | Templates, `checkout.php` | Curl local et commande temporaire | — |
| S10 | Polices sans FOUT | Conforme | `inc/assets.php` | HTML local : preconnect/preload | — |
| S11 | Micro-interactions accessibles | Conforme | CSS/JS storefront | QA post-ajout | Mouvement réduit respecté. |

## Artefacts obligatoires disponibles

| Nature | Emplacement |
| --- | --- |
| Rapport Playwright/Axe | `/home/ubuntu/keleva-local-wordpress/proofs/qa/playwright-axe-report.json` |
| Captures Playwright | `/home/ubuntu/keleva-local-wordpress/proofs/qa/` |
| Lighthouse accueil et produit | `/home/ubuntu/keleva-local-wordpress/proofs/lighthouse/` |
| Sessions | `/home/ubuntu/keleva-local-wordpress/tests/session-http-test.sh` |
| Catégories | `/home/ubuntu/keleva-local-wordpress/tests/category-api-test.sh` |
| Apparence | `/home/ubuntu/keleva-local-wordpress/tests/appearance-api-test.sh` |
| Commandes/coupons | `/home/ubuntu/keleva-local-wordpress/tests/orders-coupons-api-test.sh` |
| Palette/e-mails | `/home/ubuntu/keleva-local-wordpress/tests/palette-contract-test.php`, `email-palette-contract-test.php` |

## Décision avant Hostinger

Les lignes **Partiel** demeurent le reliquat local réel. Aucune mise à jour Hostinger ne doit être proposée tant que ces exigences ne sont pas soit complétées et prouvées, soit explicitement acceptées comme hors version par le demandeur. Les fonctions conditionnelles ne seront jamais remplacées par une simulation.

[^cdc]: [Cahier des charges Keleva Woo v1.0 — source locale](../upload/pasted_content.txt)
