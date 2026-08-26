# Rapport final de passation — Keleva WooCommerce Suite

## État de livraison

Le staging Hostinger est documenté avec le thème Keleva 0.4.19 et Keleva Woo Addons 0.6.23. Les sources publiques, journaux de recette, procédure de migration, manifeste des fixtures de test, notes de réconciliation et checklist restauration/go-live sont disponibles dans le dépôt de handover.

Dépôt : https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover

Dernier commit publié : `6c69e41` — `docs: finalize restoration and go-live handover`

## Documents à lire en premier

1. `PASSATION-KELEVA-GO-PRODUCTION.md`
2. `PROCEDURE-MIGRATION-STAGING-PRODUCTION.md`
3. `CHECKLIST-RESTAURATION-GO-LIVE.md`
4. `PUBLIC-TEST-FIXTURES.md`
5. `wordpress-package/docs/RELEASE_NOTES_0.4.19_RECONCILED.md`

## Validation réalisée

Le parcours storefront FR/AR, le responsive desktop/mobile, le portail marchand natif Hostinger, la session HMAC, les produits, catégories, options, variantes, suppléments, stocks et commandes de recette ont été documentés dans les journaux précédents. Aucun paiement réel, aucune commande réelle, aucun client réel et aucune production n’ont été utilisés.

## Export TranslatePress — statut honnête

TranslatePress Free ne fournit pas de bouton d’export autonome dans l’écran de réglages observé. Les traductions sont stockées dans la base WordPress, dans les tables de dictionnaire TranslatePress. Une tentative d’installation d’un exporteur temporaire authentifié sur le staging a été refusée par l’endpoint d’installation Hostinger avec HTTP 500 ; aucune extension temporaire n’a donc été laissée active et aucune donnée n’a été publiée.

Par conséquent, aucun lien de téléchargement d’un export complet TranslatePress ne doit être annoncé comme disponible. Pour produire cet export en toute sécurité, le prochain développeur doit effectuer un export SQL privé des tables `wp_trp_dictionary_*` et des options TranslatePress `trp_%`, après sauvegarde complète, puis le chiffrer et le conserver hors du dépôt public. Le préfixe `wp_` doit être remplacé par le préfixe réellement observé sur la production.

## Règles de sécurité

Les mots de passe, cookies, exports SQL bruts, sauvegardes Hostinger, secrets, données clients et données de commande ne sont pas présents dans le dépôt public. Ne pas ajouter ces éléments au dépôt lors de la migration.

## Go-live

Le go-live est conditionné à une sauvegarde restaurée avec succès, à la validation métier des prix/stocks/traductions, à la configuration des paiements et de la livraison en production, à une recette FR/AR complète et à la signature des deux validations go/no-go. En cas d’échec, appliquer la section Rollback de la checklist et ne jamais restaurer une base de staging sur la production.
