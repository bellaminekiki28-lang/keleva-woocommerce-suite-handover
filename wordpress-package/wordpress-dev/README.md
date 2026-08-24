# Environnement WordPress reproductible

Depuis la racine du bundle, exécutez :

```bash
npx @wordpress/env start
```

Le fichier `.wp-env.json` monte les sources réelles du thème et de l’extension. Le script `bin/provision.sh` installe WooCommerce, active Keleva, crée quatre catégories et quinze produits de preuve. Avant toute exécution partagée, remplacez le mot de passe local défini dans le script et ne le réutilisez jamais hors machine locale.

Pour arrêter l’environnement :

```bash
npx @wordpress/env stop
```

Pour une remise de lot, archivez sous `preuves/lot-N/` les rapports de tests, captures et relevé d’exécution prévus dans le cahier des charges.
