# Keleva Woo

Keleva Woo rassemble les sources du storefront WordPress/WooCommerce, du portail marchand Keleva natif et des outils de recette associés. Le dépôt est public et ne contient volontairement aucun identifiant, secret, cookie, sauvegarde, journal de session ou archive de déploiement.

> Pour reprendre le projet sans dépendance à l’environnement local d’origine, commencez par [HANDOVER.md](HANDOVER.md), puis consultez le [registre GO/NO-GO](GO-LIVE-EVIDENCE-STATUS.md) et l’[addendum de mission staging du 27 août 2026](RAPPORT-MISSION-STAGING-2026-08-27.md). Ces documents distinguent les résultats staging/copie isolée des validations de production qui restent à obtenir.

## Structure

| Répertoire | Contenu | Point de départ |
| --- | --- | --- |
| `wordpress-package/wordpress/theme/keleva-woo` | Thème storefront WordPress/WooCommerce, palettes et templates | `style.css`, `functions.php`, `inc/`, `woocommerce/` |
| `wordpress-package/wordpress/plugin/keleva-woo-addons` | Portail marchand, intégrations WooCommerce, options, variantes, audit et réglages | `keleva-woo-addons.php`, `includes/` |
| `wordpress-package/wordpress-dev` | Scripts de recette, utilitaires et tests WordPress | `README.md`, `tests/`, `qa/` |
| `wordpress-package/docs` | Documentation d’audit, modèle d’architecture et rapports de validation | `VALIDATION_REPORT_2026-08-24.md` |
| `merchant-console` | Console React/TypeScript historique et BFF | `docs/ARCHITECTURE.md`, `server/`, `client/` |

## État fonctionnel de référence

Le parcours marchand actif est un portail PHP rendu par WordPress. Il est séparé de wp-admin et prend en charge les produits, le prix, le stock, les catégories, les options, les suppléments, les limites de sélection et les vraies variantes WooCommerce avec un prix, un stock et une disponibilité propres à chaque option.

La release de référence du thème Keleva Woo est en version **0.4.19** et celle de l’extension `Keleva Woo Addons` en version **0.6.23**. Elles comprennent le portail de variantes stockées, le retrait des anciens raccourcis externes, la fondation i18n/RTL, l’auto-hébergement de Noto Sans Arabic, les notices AJAX localisées et les correctifs mobiles de la fiche variable et des cartes produit. Les détails de recette et de reprise sont dans [HANDOVER.md](HANDOVER.md), le [registre GO/NO-GO](GO-LIVE-EVIDENCE-STATUS.md), l’[addendum de mission staging](RAPPORT-MISSION-STAGING-2026-08-27.md), [la note i18n/RTL staging](wordpress-package/docs/I18N_RTL_STAGING_2026-08-26.md) et [la validation TranslatePress Free](wordpress-package/docs/FREE_TRANSLATION_STAGING_2026-08-26.md).

La fondation française-arabe utilise **TranslatePress Free** comme moteur unique : elle traduit le rendu d’un produit WooCommerce canonique au lieu de dupliquer produits, variantes, prix, stocks ou médias. La passe éditoriale arabe du storefront, du catalogue, des catégories, de la fiche variable, du panier et du checkout est documentée dans [la recette publique finale](wordpress-package/docs/FINAL_PUBLIC_RECIPE_2026-08-26.md). Les contenus incrustés dans les images restent graphiques et ne sont pas filtrables par TranslatePress. Gardez Polylang désactivé, n’installez aucun second gestionnaire multilingue et ne créez pas de produit arabe séparé.

## Démarrage rapide

### WordPress / WooCommerce

Installez les sources du thème et du plugin dans une instance WordPress locale avec WooCommerce et Elementor. Les prérequis et recettes spécifiques sont documentés dans `wordpress-package/README.md` et `wordpress-package/wordpress-dev/README.md`.

```bash
find wordpress-package/wordpress/plugin/keleva-woo-addons -name '*.php' -print0 | xargs -0 -n1 php -l
find wordpress-package/wordpress/theme/keleva-woo -name '*.php' -print0 | xargs -0 -n1 php -l
git diff --check
```

### Console React historique

```bash
cd merchant-console
pnpm install
export KELEVA_CONNECTION_ENCRYPTION_KEY="<clé-base64-de-32-octets-pour-le-test>"
pnpm check
pnpm test --run
pnpm build
```

La console React est conservée pour la continuité technique, mais le portail WordPress natif est le parcours marchand de référence. Consultez [HANDOVER.md](HANDOVER.md) avant de réutiliser ou redéployer la console.

## Publication et sécurité

Les contributions doivent respecter [CONTRIBUTING.md](CONTRIBUTING.md) et [SECURITY.md](SECURITY.md). Ne publiez jamais de mot de passe, clé API, Consumer Key/Secret WooCommerce, secret de webhook, jeton OAuth, cookie, `.env`, base de données, journal, capture de session ou archive WordPress.

Ne déployez jamais sur production sans sauvegarde vérifiée, fenêtre de changement, plan de restauration et recette finale séparée. Les paiements, WhatsApp, n8n, Merchant Center et les données clients doivent être configurés avec des secrets hors Git et validés en sandbox.

## Licence

Le thème et les extensions WordPress sont publiés sous licence GPL-2.0-or-later conformément à leur intégration WordPress. La console conserve les licences déclarées dans ses propres manifestes. Vérifiez les obligations des dépendances avant toute redistribution commerciale.
