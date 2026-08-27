# Addendum de mission — staging mobilier FR/AR Keleva

**Date :** 27 août 2026

**Auteur :** Manus AI

**Périmètre :** staging Hostinger et copies locales isolées ; aucune production commerciale distincte
**Décision :** **NO-GO production maintenu**

> Ce document décrit uniquement les résultats réellement obtenus depuis la passation précédente. Il exclut toute sauvegarde, export SQL/TranslatePress brut, cookie, identifiant, clé, URL de téléchargement privée, chemin local ou donnée client.

## Résumé

Le storefront de staging reste une boutique de mobilier premium Velora. La recette a confirmé les quatre meubles publics attendus — Fauteuil Ligne Noa, Table basse Arco, Canapé Modulaire Serein et Lampe Atelier Halo — et a identifié puis remis en brouillon, sans suppression, une fixture restaurant supplémentaire apparue dans le catalogue. Les boutiques FR et AR n’affichent donc à nouveau que ces quatre meubles. [1] [2]

Les deux derniers messages de l’état vide du panier arabe ont été traduits dans TranslatePress et vérifiés hors éditeur : le panier AR est vide, son compteur est à zéro et ses deux messages sont rendus en arabe. Le correcteur TranslatePress temporaire utilisé pendant la correction a ensuite été désactivé et supprimé ; les traductions demeurent visibles après son retrait. [3]

## Recette storefront et mobile

Les routes publiques FR et AR d’accueil, boutique, fauteuil, panier et checkout ont été contrôlées sans session administrateur à 390 × 844 px. Les dix routes ont répondu HTTP 200. Dans les premiers écrans inspectés, aucun débordement horizontal ni chevauchement n’a été constaté sur les accueils, les boutiques, les paniers vides, la fiche fauteuil ni les routes checkout à panier vide. Ces dernières renvoient logiquement l’état panier vide plutôt qu’un formulaire de paiement ; aucun checkout, paiement, commande ou WhatsApp n’a été déclenché. [1] [2] [3] [4]

Les fiches AR d’Arco, Serein et Halo ont été recontrôlées hors éditeur. Les titres, catégories, descriptions courtes et longues, prix, stocks, options principales et textes d’aide contrôlés sont rendus en arabe. Le reliquat structurel WCFM est traité séparément : il ne constitue pas une validation de l’attribution vendeur. [5] [6] [7]

| Parcours | Résultat | Limite explicite |
|---|---|---|
| Accueil et boutique FR/AR | PASS — staging, desktop déjà recetté et mobile ciblé | Pas une certification multi-navigateurs exhaustive |
| Fiches Noa, Arco, Serein et Halo AR | PASS — contenu et options principales contrôlés | Attribution WCFM non corrigée sur staging |
| Panier AR vide | PASS | Aucun test de checkout réel car panier volontairement vide |
| Checkout FR/AR à panier vide | PASS — HTTP/rendu d’état vide | Paiement et création de commande non testés |
| Traduction AR exhaustive de tous les états non visités | Non déclarée | Toute chaîne client restante découverte doit être récoltée et traduite atomiquement |

## Export TranslatePress

Un export **privé** du dictionnaire FR → AR a été généré depuis le catalogue mobilier courant du staging. Il date du 27 août 2026 à 12:12:18 UTC, mesure 246 503 octets et possède le SHA-256 `23cbb12b2da275ccb076ec8250bd6123dbff3f8b4d258c3f835d1d42f18eec20`. Son périmètre est limité à une table de dictionnaire (`wp_trp_dictionary_fr_fr_ar`, 934 lignes) et neuf options dont le nom commence par `trp_`. Les produits, commandes, utilisateurs, cookies, mots de passe, clés de paiement et options WordPress générales sont exclus.

La restauration technique des données TranslatePress sur copie isolée est attestée, mais la validation des routes locales `/ar/` demeure en échec sous les configurations testées. Cette limite est conservée au statut **FAIL** : il serait incorrect de l’interpréter comme une preuve de parité avec le routage de l’hébergement. [8]

## Rollback de release

Un rollback de code complet a été exécuté sur copie isolée entre la candidate thème `0.4.19`/plugin `0.6.23` et la révision thème `0.4.17`/plugin `0.6.23`. Après simulation d’un incident HTTP 503, le retour code a été mesuré à 877 ms, puis la réinstallation de la candidate à 853 ms. Cette preuve vaut uniquement pour le code en copie isolée ; le rollback de base n’a pas été exécuté et est donc **N/A**. [9]

## Migration locale WCFM et Sauce

L’inventaire de la copie isolée a montré que les quatre meubles conservent une attribution vers des vendeurs WCFM de démonstration et un attribut héritier « Sauce ». Une migration locale réversible a été préparée et exécutée uniquement en copie : snapshot avant essai, inspection de portée, retrait contrôlé des marqueurs, contrôle WooCommerce, script inverse et comparaison fonctionnelle après retour.

Le forward a conservé les noms, statuts, prix et stocks des quatre meubles, puis l’inverse a restitué les auteurs, valeurs de métadonnées et taxonomies inventoriés. La recette visuelle par permalien local a en revanche rencontré le même routage non fidèle que l’autre recette locale. **Aucun changement WCFM ou Sauce n’a été appliqué au staging.** [10]

| Objet | Forward local | Rollback local | Staging |
|---|---|---|---|
| Marqueurs WCFM et restaurant hérités | PASS — retrait limité | PASS — valeurs fonctionnelles restaurées | Non appliqué |
| Attribut Sauce | PASS — retrait limité | PASS — inventaire fonctionnel restauré | Non appliqué |
| Prix, stocks, titres, statuts | PASS — conservés | PASS — conservés | Non modifié |
| Fiches et panier par URLs locales | FAIL/N/A — routage local non fidèle | N/A | Non testé par migration |

## Capacités disponibles et bloqueurs

| Domaine | Situation |
|---|---|
| Staging WordPress, wp-admin, TranslatePress, recette publique, copies locales, GitHub | Disponibles et utilisés pour les preuves ci-dessus |
| Production commerciale séparée, sauvegarde/restauration production, inventaire production | Non disponibles ou non prouvés ; aucune action réalisée |
| Parité locale complète du routage hôte `/ar/` | Non obtenue ; validation locale AR conservée en échec |
| Stripe, n8n, WhatsApp et leurs credentials sandbox | Non configurés ; aucun flux tiers déclenché |
| Signatures métier et technique | Absentes |

## Décision et suite obligatoire

La décision reste **NO-GO production**. Pour envisager une future décision, il faut obtenir et faire approuver séparément : l’inventaire de production, sa sauvegarde complète, la restauration sur copie isolée, la validation de parité du routage bilingue, les essais sandbox des intégrations retenues ou leurs exclusions écrites, une recette métier FR/AR, une matrice des navigateurs cibles, et les signatures technique et métier.

Le correcteur temporaire a été supprimé du staging et aucun serveur de recette local ne reste actif. La migration WCFM/Sauce locale a démontré une procédure réversible, mais elle n’autorise pas à modifier le staging tant que les limites de rendu local n’ont pas reçu une décision documentée.

## Références

[1]: https://aliceblue-bison-433987.hostingersite.com/ "Accueil Keleva staging FR"
[2]: https://aliceblue-bison-433987.hostingersite.com/ar/boutique/ "Boutique Keleva staging AR"
[3]: https://aliceblue-bison-433987.hostingersite.com/ar/panier/ "Panier Keleva staging AR"
[4]: https://aliceblue-bison-433987.hostingersite.com/commander/ "Route checkout Keleva staging FR"
[5]: https://aliceblue-bison-433987.hostingersite.com/ar/product/table-basse-arco/ "Fiche Table basse Arco AR"
[6]: https://aliceblue-bison-433987.hostingersite.com/ar/product/canape-modulaire-serein/ "Fiche Canapé Modulaire Serein AR"
[7]: https://aliceblue-bison-433987.hostingersite.com/ar/product/lampe-atelier-halo/ "Fiche Lampe Atelier Halo AR"
[8]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/private-evidence-index/TRANSLATEPRESS-EXPORT-INVENTORY-2026-08-27.json "Index public assaini TranslatePress"
[9]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/GO-LIVE-EVIDENCE-STATUS.md "Registre GO/NO-GO Keleva"
[10]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/GO-LIVE-EVIDENCE-STATUS.md "Registre GO/NO-GO Keleva"
