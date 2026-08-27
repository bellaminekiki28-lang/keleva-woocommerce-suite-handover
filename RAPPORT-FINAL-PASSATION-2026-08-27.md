# Rapport final de passation — Keleva WooCommerce Suite

## État de livraison

Le staging Hostinger est documenté avec le thème Keleva 0.4.19 et Keleva Woo Addons 0.6.23. Les sources publiques, journaux de recette, procédure de migration, manifeste des fixtures de test, notes de réconciliation et checklist restauration/go-live sont disponibles dans le dépôt de handover.

Dépôt : https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover

Source de release autorisée : `8663ce3f59da5549b3c0b29b2a2245a82789d3b2`. Le commit de handover le plus récent sera celui créé après cette mise à jour.

## Documents à lire en premier

1. `PASSATION-KELEVA-GO-PRODUCTION.md`
2. `PROCEDURE-MIGRATION-STAGING-PRODUCTION.md`
3. `CHECKLIST-RESTAURATION-GO-LIVE.md`
4. `PUBLIC-TEST-FIXTURES.md`
5. `wordpress-package/docs/RELEASE_NOTES_0.4.19_RECONCILED.md`
6. `GO-LIVE-EVIDENCE-STATUS.md`
7. `RAPPORT-PREUVES-FINALES-2026-08-27.md`

## Artefacts de release

Les archives installables officielles ont été reconstruites depuis la source : `wordpress-package/installables/keleva-woo-0.4.19.zip` et `wordpress-package/installables/keleva-woo-addons-0.6.23.zip`. Leurs SHA-256 sont consignés dans `wordpress-package/RELEASE-SHA256-0.4.19-0.6.23.txt` et leur ordre d’installation/rollback dans `wordpress-package/RELEASE-MANIFEST-0.4.19-0.6.23.md`.

## Validation réalisée

Le parcours storefront FR/AR, le responsive desktop/mobile, le portail marchand natif Hostinger, la session HMAC, les produits, catégories, options, variantes, suppléments, stocks et commandes de recette ont été documentés dans les journaux précédents. Aucun paiement réel, aucune commande réelle, aucun client réel et aucune production n’ont été utilisés.

## Export TranslatePress — statut honnête

L’export privé TranslatePress a été généré depuis le staging avec un outil temporaire administrateur, puis l’outil a été retiré immédiatement. L’artefact s’appelle `keleva-translatepress-private-20260827-001010.json`, contient une table de dictionnaire et neuf options TranslatePress, et son SHA-256 est `0f6bdd5e961249c15306acec74b288e0ee280d989fa75476c217c5bd25d50427`. Il est remis hors dépôt public. Le relevé sans contenu traduit est publié dans `private-evidence-index/TRANSLATEPRESS-EXPORT-PROOF-2026-08-27.md`.

Cet export est prouvé comme généré mais **n’est pas encore restauré et validé sur une copie isolée**. Il ne doit donc pas être qualifié de restaurable validé, ni être importé sur la production. Le préfixe `wp_` relevé sur le staging ne doit pas être supposé identique à celui de la production.

## Règles de sécurité

Les mots de passe, cookies, exports SQL bruts, sauvegardes Hostinger, secrets, données clients et données de commande ne sont pas présents dans le dépôt public. Ne pas ajouter ces éléments au dépôt lors de la migration.

## Go-live

Le go-live est conditionné à une sauvegarde restaurée avec succès, à la validation métier des prix/stocks/traductions, à la configuration des paiements et de la livraison en production, à une recette FR/AR complète et à la signature des deux validations go/no-go. En cas d’échec, appliquer la section Rollback de la checklist et ne jamais restaurer une base de staging sur la production.
