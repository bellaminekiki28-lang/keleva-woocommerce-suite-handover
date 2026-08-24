# Runbook d’installation développeur

## 1. Préparer l’environnement

Installez une instance neuve de WordPress en HTTPS avec PHP 8.2 ou supérieur, WooCommerce actif et des permaliens activés. Installez Elementor si le projet doit utiliser les widgets Keleva ou les layouts Elementor. Avant toute manipulation, prenez une sauvegarde de fichiers et de base de données.

## 2. Installer les deux packages WordPress

Téléversez `installables/keleva-woo-0.3.5.zip` dans **Apparence → Thèmes**, puis activez Keleva Woo. Téléversez ensuite `installables/keleva-woo-addons-0.3.4.zip` dans **Extensions → Ajouter une extension**, puis activez Keleva Woo Addons.

Le thème doit être activé après WooCommerce. L’extension contrôle elle-même la disponibilité de WooCommerce et d’Elementor pour les zones qui en dépendent.

## 3. Vérifier le storefront

Créez une page d’accueil, une page boutique et au moins un produit simple. Vérifiez les actions suivantes : ouverture du quick view, ajout au panier, mise à jour du compteur et passage au checkout. Ajoutez ensuite un produit variable avec deux attributs, puis validez l’achat d’une combinaison disponible.

## 4. Configurer les groupes d’options

Dans l’édition d’un produit WooCommerce, utilisez la métabox Keleva pour ajouter un groupe. Définissez son type d’affichage, son nombre maximal de choix et ses suppléments. Contrôlez la fiche produit et le quick view : les mêmes règles doivent bloquer tout dépassement de plafond.

## 5. Configurer le dashboard marchand

Dans **WooCommerce → Keleva Dashboard**, créez une clé de dashboard propre à l’environnement. Conservez-la dans un gestionnaire de secrets. Créez une page `keleva-merchant` et insérez `console/keleva-native-console.html` dans un bloc HTML.

La console ne doit être accessible qu’à des opérateurs autorisés. En préproduction, elle peut utiliser la clé saisie au démarrage. En production, préférez le dashboard React avec passerelle serveur et cookie HTTP-only : le navigateur ne doit jamais recevoir la clé de dashboard.

## 6. Vérifier les routes REST

Avec une clé valide, testez la lecture de `/summary`, la création d’un produit brouillon, sa modification et `GET /products/{id}/configuration`. Testez les variantes et options uniquement sur un brouillon dédié. Vérifiez le journal `/audit` après chaque mutation.

## 7. Déployer une mise à jour

Travaillez depuis les sources décompressées dans `wordpress/`. Générez de nouvelles archives ZIP avec un numéro de version mis à jour dans les en-têtes `style.css` et `keleva-woo-addons.php`. Testez en local, sauvegardez la préproduction, installez l’archive, videz uniquement les caches pertinents, puis exécutez la recette console assainie.

## 8. Retour arrière

Conservez les ZIP de la version précédente et une sauvegarde WordPress complète. En cas de régression, désactivez l’extension fautive, réinstallez la dernière archive validée, puis contrôlez le storefront, le panier et les endpoints dashboard avant de reprendre les changements.
