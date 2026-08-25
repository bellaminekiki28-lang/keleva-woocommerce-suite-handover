# Contribuer à Keleva Woo

## Règle principale

Le dépôt est public. Ne versionnez jamais un mot de passe, une clé WooCommerce, un secret de webhook, un jeton OAuth, un cookie, une base de données, une archive de sauvegarde, un journal de navigateur, une capture de session ou un fichier `.env`.

## Organisation des changements

Les changements de storefront vont dans `wordpress-package/wordpress/theme/keleva-woo`. Les changements de portail marchand, d’options WooCommerce, de variantes et d’intégrations vont dans `wordpress-package/wordpress/plugin/keleva-woo-addons`. La console React historique reste dans `merchant-console`, mais elle n’est pas le parcours marchand actif du staging.

Chaque changement fonctionnel doit inclure une description claire, un test reproductible et une mise à jour du guide concerné. Les changements de schéma WooCommerce, de suppression ou de paiement doivent être séparés et testés sur un environnement local ou de staging dédié avant toute production.

## Contrôles minimaux avant une pull request

Depuis la racine du dépôt, contrôlez la syntaxe PHP du plugin et du thème, puis les différences Git :

```bash
find wordpress-package/wordpress/plugin/keleva-woo-addons -name '*.php' -print0 | xargs -0 -n1 php -l
find wordpress-package/wordpress/theme/keleva-woo -name '*.php' -print0 | xargs -0 -n1 php -l
git diff --check
```

Pour la console React/TypeScript, exécutez depuis `merchant-console` :

```bash
cd merchant-console
pnpm install
export KELEVA_CONNECTION_ENCRYPTION_KEY="<clé-base64-de-32-octets-pour-le-test>"
pnpm check
pnpm test --run
pnpm build
```

Ne déployez pas les archives générées, `node_modules`, `vendor`, les journaux, les captures, les sessions ou les contenus WordPress téléversés. Les règles `.gitignore` du dépôt couvrent ces éléments.

## Déploiement staging

Le thème et l’extension doivent être archivés avec leur répertoire à la racine du ZIP. Avant tout remplacement sur WordPress, sauvegardez la base et les fichiers du staging. Après déploiement, vérifiez le chemin public marchand, le formulaire Keleva, les mutations concernées et les éventuels messages d’erreur PHP. Une recette impliquant panier, paiement, WhatsApp, client ou commande réelle est hors périmètre tant qu’elle n’a pas été expressément autorisée et configurée avec des données sandbox.
