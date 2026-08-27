# État des preuves GO/NO-GO — Keleva

**Périmètre contrôlé :** staging WordPress Hostinger et copies locales isolées uniquement. **Release de référence :** thème Keleva Woo `0.4.19` et plugin Keleva Woo Addons `0.6.23`, commit source `8663ce3`. **Décision officielle :** **NO-GO production**.

> Une preuve `PASS` ne s’étend jamais au-delà de son environnement et de son objet de contrôle. Les données utilisateurs, sauvegardes, exports bruts, SQL, cookies, identifiants, clés, URL privées et chemins locaux ne sont volontairement pas publiés.

| Contrôle | Statut | Portée et preuve publique assainie |
|---|---|---|
| Commit officiel et versions documentés | **PASS** | Release `0.4.19/0.6.23` et manifeste de livraison [1]. |
| Archives installables thème et plugin | **PASS** | Archives et SHA-256 versionnés dans le package de release [1]. |
| Correction storefront mobilier | **PASS — staging** | Accueil et catalogues FR/AR : quatre meubles publiés ; les fixtures restaurant sont en brouillon réversible. Contrôle public : [FR][2], [AR][3]. |
| Fixture restaurant résiduelle | **PASS — staging, réversible** | Le produit de fixture identifié pendant la recette a été passé en brouillon, sans suppression. Les deux catalogues publics n’exposent ensuite que les quatre meubles. [2] [3] |
| Configurateur fauteuil premium | **PASS — staging** | Choix radio obligatoire, services par cases à cocher, limites et total de recette de 5 920 MAD précédemment vérifiés, sans checkout ni paiement. [4] |
| Traduction AR des quatre meubles et catégories | **PASS — périmètre recetté** | Noms, descriptions, catégories et options principales de Noa, Arco, Serein et Halo sont rendus en arabe RTL sur les routes contrôlées. [3] [4] [5] [6] |
| Panier AR vide | **PASS — staging** | Les deux messages d’état vide sont rendus en arabe, le compteur public est à `00` et aucun article n’est présent. [7] |
| Recette responsive FR/AR | **PASS — mobile ciblé** | Les dix routes clés FR/AR retournent HTTP 200 à 390 × 844 px ; les premiers écrans de l’accueil, boutique, fauteuil, panier et checkout ne montrent ni débordement ni superposition observés. Ce contrôle n’est pas une matrice cross-browser complète. [8] |
| Portail marchand white-label | **PASS — staging/code** | Portail natif sous les routes dédiées, authentification distincte et absence d’accès marchand à `wp-admin` documentées séparément. [9] |
| Tests du checkpoint portail | **PASS — copie isolée** | Dépendances verrouillées, cinq fichiers Vitest / seize tests, TypeScript, build, login, persistance, logout/révocation et refus d’identifiant invalide. [10] |
| Sauvegarde Duplicator staging | **PASS — staging, privée** | Archive privée créée, téléchargée, checksumée et vérifiée ; l’index public ne décrit que les métadonnées autorisées. [11] |
| Restauration WordPress staging sur copie isolée | **PASS — partiel** | Fichiers et base restaurés ; accueil FR et fiche configurable vérifiés. Ce résultat ne couvre ni la production ni le rendu AR complet. [11] |
| Export TranslatePress mobilier actuel | **PASS — staging, privé** | Export daté du 27 août 2026 à 12:12:18 UTC ; SHA-256 `23cbb12b2da275ccb076ec8250bd6123dbff3f8b4d258c3f835d1d42f18eec20`, 246 503 octets, table `wp_trp_dictionary_fr_fr_ar` (934 lignes), neuf options `trp_%`. Le JSON brut reste privé. [12] |
| Restauration isolée de l’export TranslatePress et routes AR locales | **FAIL** | Les données ont été importées, mais les routes `/ar/` locales restent en 404 sous les configurations déjà testées. Aucune preuve de parité hôte n’est simulée. [13] |
| Rollback complet de release code | **PASS — copie isolée** | Retour code vers la release précédente contrôlé en 877 ms après incident simulé, puis réinstallation candidate en 853 ms. Rollback base : **N/A**, non exécuté. [14] |
| Migration Sauce des quatre meubles | **PASS — copie isolée** | Snapshot, inventaire, forward limité, intégrité WooCommerce, inverse et comparaison fonctionnelle après rollback sont prouvés. Le routage local des fiches/panier reste non fidèle ; aucune application staging. [15] |
| Reliquat WCFM restaurant | **OUVERT / NON APPLIQUÉ** | La cause est identifiée (auteurs et `_wcfm_vendor` hérités). Une neutralisation locale a été testée puis annulée ; son application staging est bloquée par la limite de recette locale et requiert une décision séparée. [15] |
| Correcteur TranslatePress temporaire | **PASS — nettoyage staging** | Désactivé puis supprimé après recontrôle public des traductions persistantes. L’archive de récupération est privée. [8] |
| Sauvegarde et restauration production | **FAIL** | Production commerciale distincte non identifiée et non modifiée. |
| Inventaire production | **FAIL** | Domaine, hPanel, versions, extensions, paiements, cache et données métier ne sont pas prouvés hors staging. |
| Paiements, n8n et WhatsApp | **N/A — non approuvé** | Aucune intégration réelle ni credential tiers n’a été configuré, et aucun flux n’a été déclenché. |
| Recette métier, SEO, sécurité, monitoring et signatures | **FAIL** | Décision métier, inventaire production et signatures techniques/métier manquent. |

## Décision

**NO-GO production maintenu.** Les outils et accès ont permis de corriger et recetter le staging, de traduire les parcours AR ciblés, d’exporter TranslatePress dans un artefact privé, d’exercer des restaurations et rollback sur copies isolées, et de publier une documentation assainie. Ils ne prouvent pas une sauvegarde ou restauration de production, la parité locale du routage AR avec l’hébergement, les intégrations Stripe/n8n/WhatsApp, ni les signatures métier et techniques. Aucun déploiement, restauration, paiement, commande, automatisation ou écrasement de la production ne doit être exécuté sur la base de ce registre.

## Références

[1]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/tree/main/wordpress-package "Package de release Keleva"
[2]: https://aliceblue-bison-433987.hostingersite.com/boutique/ "Boutique Keleva staging FR"
[3]: https://aliceblue-bison-433987.hostingersite.com/ar/boutique/ "Boutique Keleva staging AR"
[4]: https://aliceblue-bison-433987.hostingersite.com/ar/product/fauteuil-ligne-noa/ "Fiche Fauteuil Ligne Noa AR"
[5]: https://aliceblue-bison-433987.hostingersite.com/ar/product/table-basse-arco/ "Fiche Table basse Arco AR"
[6]: https://aliceblue-bison-433987.hostingersite.com/ar/product/canape-modulaire-serein/ "Fiche Canapé Modulaire Serein AR"
[7]: https://aliceblue-bison-433987.hostingersite.com/ar/panier/ "Panier Keleva staging AR"
[8]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/RAPPORT-MISSION-STAGING-2026-08-27.md "Addendum de recette staging"
[9]: https://aliceblue-bison-433987.hostingersite.com/espace-marchand/ "Portail marchand Keleva"
[10]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/private-evidence-index/PORTAL-CHECKPOINT-PROOF-2026-08-27.md "Preuve publique assainie du checkpoint portail"
[11]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/private-evidence-index/HOSTINGER-STAGING-DUPLICATOR-BACKUP-PROOF-2026-08-27.md "Preuve publique assainie de sauvegarde staging"
[12]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/RAPPORT-MISSION-STAGING-2026-08-27.md#export-translatepress "Métadonnées publiques de l’export TranslatePress courant"
[13]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/private-evidence-index/TRANSLATEPRESS-EXPORT-INVENTORY-2026-08-27.json "Index public assaini TranslatePress"
[14]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/RAPPORT-MISSION-STAGING-2026-08-27.md#rollback-de-release "Résultat public assaini du rollback de release"
[15]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/private-evidence-index/WCFM-SAUCE-LOCAL-MIGRATION-PROOF-2026-08-27.md "Résultat public assaini de la migration locale"
