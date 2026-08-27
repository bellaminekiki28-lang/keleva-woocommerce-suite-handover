# Passation Keleva WooCommerce Suite — état et go production

**Document destiné au prochain développeur.** Dernière mise à jour : 27 août 2026. Auteur : Manus AI.

## 1. Réponse courte : le travail est-il entièrement sur GitHub ?

Le dépôt public contient les sources WordPress de reprise, le plugin Keleva Woo Addons, le thème Keleva Woo, la console historique, les tests, les journaux de recette, les rapports FR/AR, les contrôles de sécurité, la procédure de migration et la documentation technique. Le dépôt distant vérifié est : [bellaminekiki28-lang/keleva-woocommerce-suite-handover](https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover), branche `main`, dernier commit vérifié `9f39ece11285dd9da7bcbd8cc6d0ac9b491f4298` (`9f39ece`). Les commits `6c69e41` et `9c7e5f3` sont historiques et ne constituent pas la release autorisée.

Cependant, **tout ce qui existe sur le site WordPress ne peut pas être contenu dans GitHub**. Les traductions enregistrées par TranslatePress Free, les produits, catégories, variations, médias, réglages, comptes portail, cookies, commandes et autres données du staging résident dans la base et le stockage Hostinger. Ils ne sont pas exportés dans le dépôt public, conformément à la protection des secrets et des données métier. Le prochain développeur doit donc récupérer une sauvegarde privée du staging et un accès Hostinger séparé.

## 2. Contenu présent dans le dépôt public

| Élément | Emplacement | État |
|---|---|---|
| Plugin WordPress | `wordpress-package/wordpress/plugin/keleva-woo-addons/` | Source de release avec en-tête 0.6.23 |
| Thème WordPress | `wordpress-package/wordpress/theme/keleva-woo/` | Source versionnée ; en-tête officiel 0.4.19 |
| Documentation bilingue/RTL | `wordpress-package/docs/` | Rapports, inventaires i18n, recettes et limites |
| Journaux de recette finaux | `AR_STOREFRONT_SCAN_2026-08-26.md`, `AR_PORTAL_AUTHENTICATED_2026-08-26.md`, `FINAL_HTTP_CHECK_2026-08-26.md` | Présents dans `main` |
| Procédure de migration | `PROCEDURE-MIGRATION-STAGING-PRODUCTION.md` | Présente dans `main` |
| Passe éditoriale arabe | `TRANSLATION-AR-EDITORIAL-2026-08-27.md` | Présente dans `main` |
| Console historique | `merchant-console/` | Code de reprise, à ne pas confondre avec le portail natif Hostinger |
| Sécurité et contribution | `SECURITY.md`, `CONTRIBUTING.md`, `README.md` | Présents |
| Intégrité historique | `wordpress-package/CHECKSUMS-SHA256.txt` | Archives historiques ; ne pas utiliser comme checksum de la release actuelle |
| Release candidate | `wordpress-package/RELEASE-MANIFEST-0.4.19-0.6.23.md` | Archives 0.4.19/0.6.23 reconstruites et documentées |
| Checksums release candidate | `wordpress-package/RELEASE-SHA256-0.4.19-0.6.23.txt` | SHA-256 des deux archives actuelles |
| État des preuves GO/NO-GO | `GO-LIVE-EVIDENCE-STATUS.md` | Code PASS ; preuves production encore bloquantes |
| Fixtures publiques | `PUBLIC-TEST-FIXTURES.md` | Manifeste assaini des produits, options et scénarios de démonstration |

Pour récupérer le projet :

```bash
gh repo clone bellaminekiki28-lang/keleva-woocommerce-suite-handover
cd keleva-woocommerce-suite-handover
git checkout main
git log -1 --oneline
```

Ne jamais ajouter de fichier `.env`, mot de passe, token, export SQL, sauvegarde Hostinger, cookie ou donnée client au dépôt public.

## 3. État actuel du staging Hostinger

| Domaine | Valeur vérifiée |
|---|---|
| Staging | `https://aliceblue-bison-433987.hostingersite.com` |
| Portail français | `/espace-marchand/` |
| Portail arabe | `/ar/espace-marchand/` |
| Moteur multilingue | TranslatePress Free actif ; français source, arabe sous `/ar/` |
| Plugin actif observé après la dernière installation | Keleva Woo Addons 0.6.23 |
| Thème source documenté dans le dépôt | Keleva Woo 0.4.19 |
| Données | Staging uniquement ; aucune commande, client ou paiement réel prévu |
| Portail | Authentification dédiée Keleva, séparée de wp-admin, session signée HMAC |

Le compte de recette est `keleva.recette`. Son mot de passe n’est volontairement pas écrit ici ni dans GitHub ; il doit être transmis par un canal privé ou régénéré depuis WordPress avant recette. Le manifeste public `PUBLIC-TEST-FIXTURES.md` décrit les données de démonstration sans publier ce secret. Le portail ne doit pas être relié à une session wp-admin.

## 4. Fonctionnalités livrées

Le portail permet l’ajout guidé d’un produit de test, la modification du prix, du stock, de la photo et du texte alternatif, la sélection d’une catégorie, la recherche par nom, la visualisation du stock, la gestion des commandes de staging, les palettes et l’apparence, ainsi que les groupes d’options, limites, suppléments payants et variantes simples. Les suppressions sont limitées aux données explicitement marquées comme test.

Le storefront bilingue couvre les routes françaises et arabes testées, le RTL, la police arabe auto-hébergée, les catégories, options, variations, panier, checkout et notices AJAX principales. Les descriptions de recette traduites récemment dans TranslatePress Free sont : avocado toast, pancakes fleur d’oranger et œufs shakshuka. Les marques, noms commerciaux et textes incrustés dans les images ne sont pas modifiés automatiquement.

## 5. Écart important à vérifier avant toute release

La source du dépôt porte désormais l’en-tête officiel **0.4.19** et le plugin porte **0.6.23**, avec `Stable tag: 0.6.23`. Les mentions 0.4.17 et 0.5.6 conservées dans certains journaux sont historiques. Avant production, le développeur doit encore comparer les fichiers réellement actifs et la feuille CSS servie sur Hostinger, reconstruire les archives installables et recalculer les checksums finaux.

La base TranslatePress du staging n’est pas dans GitHub. Il faut exporter ou sauvegarder les traductions par une méthode privée et vérifiable, puis décider précisément si elles doivent être reportées en production. Les commandes, clients, stocks et paiements de production doivent rester la source de vérité et ne doivent jamais être remplacés par ceux du staging.

## 6. Checklist go production

| Phase | Action obligatoire | Validation |
|---|---|---|
| Décision | Obtenir l’accord de migration et définir une fenêtre de maintenance | Responsable nommé, heure de début et fin |
| Inventaire | Comparer versions thème/plugin et extensions staging/production | Manifeste signé, checksum cohérent |
| Sauvegarde | Sauvegarder fichiers, base, médias et configuration de production | Archive privée téléchargée et restaurable |
| Traductions | Sauvegarder/exporter TranslatePress et sélectionner les contenus approuvés | Aucune donnée client ou commande écrasée |
| Release | Construire les archives depuis GitHub et relancer lint/tests | Tests verts, scan de secrets propre |
| Déploiement | Déployer thème puis plugin, sans supprimer une extension active | Version affichée et fichiers servis corrects |
| WooCommerce | Vérifier devise, taxes, livraison, stock et moyens de paiement | Aucun paiement réel soumis pendant la recette |
| FR/AR | Tester accueil, catalogue, produit, panier et checkout sur desktop/mobile | HTTP 200, RTL correct, aucun débordement |
| Portail | Tester connexion dédiée, produits, commandes, apparence et déconnexion | Aucune session wp-admin, aucun 5xx |
| Clôture | Purger cache, réactiver cache, sortir de maintenance et archiver le rapport | Go final écrit |

La procédure détaillée est dans [`PROCEDURE-MIGRATION-STAGING-PRODUCTION.md`](./PROCEDURE-MIGRATION-STAGING-PRODUCTION.md). Le tableau reproductible des preuves est dans [`GO-LIVE-EVIDENCE-STATUS.md`](./GO-LIVE-EVIDENCE-STATUS.md). Le manifeste de release est dans [`wordpress-package/RELEASE-MANIFEST-0.4.19-0.6.23.md`](./wordpress-package/RELEASE-MANIFEST-0.4.19-0.6.23.md). Ces documents doivent être lus intégralement avant toute intervention sur la production.

## 7. Rollback obligatoire

En cas de HTTP 5xx, checkout bloqué, stock incohérent, perte de traduction, défaut RTL critique ou erreur PHP, activer la maintenance, restaurer les anciennes archives du thème et du plugin, purger les caches et rejouer la matrice FR/AR. Une restauration SQL complète n’est autorisée qu’après sauvegarde de l’état courant et décision explicite ; elle ne doit jamais écraser les commandes ou clients créés depuis le début de la migration.

Le rollback doit utiliser le point de sauvegarde correspondant au même instant que la release. Si le problème concerne uniquement le code, préférer le rollback code plutôt qu’une restauration de base. Si le problème concerne uniquement les traductions, restaurer sélectivement les données TranslatePress plutôt que la base entière.

## 8. Tests à exécuter par le prochain développeur

```bash
cd merchant-console
pnpm install
pnpm test

cd ../wordpress-package
find wordpress/plugin/keleva-woo-addons -name '*.php' -print0 | xargs -0 -n1 php -l
find wordpress/theme/keleva-woo -name '*.php' -print0 | xargs -0 -n1 php -l
git diff --check
```

Sur staging, vérifier au minimum les routes suivantes : `/`, `/ar/`, `/boutique/`, `/ar/boutique/`, une fiche produit variable en français et en arabe, `/panier/`, `/ar/panier/`, `/checkout/`, `/ar/checkout/`, `/espace-marchand/` et `/ar/espace-marchand/`. Rejouer la matrice à 390 × 844 px et 1280 px. Ne pas créer de client réel, commande réelle ou paiement réel pendant cette recette.

## 9. Ce qui manque avant le go production

Le go production n’est pas encore automatique. Il manque la comparaison finale thème actif/dépôt, l’export privé et la décision de migration des traductions TranslatePress, la sauvegarde restaurable de production, la validation d’un vrai plan de rollback sur une copie, la confirmation des réglages de paiement et livraison de production, ainsi qu’une recette métier signée. L’intégration Stripe et l’automatisation WhatsApp/n8n ne doivent pas être considérées comme livrées : elles ont été maintenues hors périmètre des tests de transaction réelle.

Il manque également une décision éditoriale sur les noms produits/options encore latins et sur les éventuels textes incrustés dans les images. Ils sont actuellement conservés volontairement ou hors portée de TranslatePress.

## 10. Références

[1]: https://developer.wordpress.org/advanced-administration/upgrade/wordpress/ — WordPress, procédure de mise à jour.
[2]: https://developer.wordpress.org/advanced-administration/upgrade/backup/ — WordPress, sauvegarde avant mise à jour.
[3]: https://woocommerce.com/document/understanding-the-woocommerce-system-status-report/ — WooCommerce, rapport d’état système.

> **Règle finale :** le staging est une zone de recette. Aucune mise en production ne doit être exécutée à partir de ce document sans sauvegarde privée vérifiée, comparaison des versions réellement actives et décision go/no-go documentée.
