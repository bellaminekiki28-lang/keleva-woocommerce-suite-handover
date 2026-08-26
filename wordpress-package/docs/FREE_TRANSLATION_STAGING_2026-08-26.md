# Traduction FR/AR gratuite — validation staging du 26 août 2026

## Décision

Le staging Keleva utilise désormais **TranslatePress Free** comme moteur de langues unique. Polylang est conservée installée mais désactivée afin de permettre un retour arrière ; les deux extensions ne sont pas actives en même temps. TranslatePress Free couvre le besoin strict de deux langues, avec le français comme source et l’arabe comme seule langue additionnelle. [1]

> Cette solution traduit le **rendu client** d’une fiche WooCommerce unique. Elle ne crée ni copie de produit, ni copie de variation, ni synchronisation de prix ou de stock à maintenir.

| Domaine | État validé sur staging |
| --- | --- |
| URLs | Français sans préfixe et arabe sous `/ar/`, sur le même domaine Hostinger |
| Direction | Routes arabes avec `lang="ar"`, `dir="rtl"` et classe `translatepress-ar` |
| Typographie | Feuille RTL Keleva et WOFF2 Noto Sans Arabic auto-hébergés, sans requête Noto externe |
| Commerce | Produit variable canonique unique conservé entre route FR et AR ; prix, stock, médias et variantes non dupliqués |
| Traduction de recette | `Informations et achat` rendu comme `معلومات الشراء` seulement en arabe |
| Sécurité | Aucune traduction automatique, clé API, paiement, commande, WhatsApp, client ou checkout soumis |

## Recette exécutée

La fiche variable `brunch-bloom-avocado-toast` a été contrôlée en français et en arabe. Les deux routes référencent le même produit WooCommerce (`postid-78`) et le même formulaire de variations. La traduction visuelle enregistrée sur la route arabe est persistée hors éditeur tandis que la route française conserve la chaîne source. Ce comportement évite le risque de divergences de prix, stock ou options qui apparaît lorsqu’un second produit est créé pour chaque langue. [2]

La recette CDP mobile finale a porté sur 12 routes à 390 × 844 px : accueil, catégorie, fiche variable, panier, checkout et portail marchand, en français et en arabe. Le correctif de thème Keleva Woo 0.4.17 distribue les lignes prix/action des cartes dans une grille qui reste dans la carte, en français comme en RTL. Après activation réelle de 0.4.17 sur le thème actif, les 12 routes ont `scrollWidth=390`, aucun débordement horizontal et aucun contrôle de carte hors viewport. Les quelques coordonnées hors-canevas conservées par le script concernent des onglets horizontaux ou des champs WooCommerce dynamiques masqués par le navigateur ; elles ne modifient pas la largeur du document et ne constituent pas un débordement de page.

## Limites à respecter

La voie gratuite ne remplace pas une traduction faite par un humain : titres, descriptions, catégories, attributs visibles, libellés d’option, contenus du panier et contenus de checkout doivent être traduits explicitement dans l’éditeur visuel. Certaines chaînes WooCommerce dynamiques ne deviennent sélectionnables qu’après avoir effectué l’action concernée, par exemple sélectionner une variante ou ouvrir le panier. [2]

Ne réactivez pas Polylang en parallèle de TranslatePress. En cas de retour arrière, désactivez TranslatePress puis réactivez Polylang, avant de purger le cache et de rejouer les routes françaises et arabes. Ne créez pas de produit AR séparé pour « traduire » un produit existant : les données commerciales doivent rester sur le produit WooCommerce canonique.

## Suite de recette

Traduire d’abord une seule fiche variable de recette et une seule catégorie. Rejouer ensuite, sans commande réelle, l’accueil, l’archive catégorie, la fiche variable avec chaque option, le panier, le checkout et le portail marchand arabe connecté. Avant une mise en production, refaire ces recettes sur une copie de contenu validée par le propriétaire.

## Références

[1] [TranslatePress — extension officielle WordPress.org](https://wordpress.org/plugins/translatepress-multilingual/)

[2] [TranslatePress — traduire les produits WooCommerce](https://translatepress.com/translate-woocommerce-products-translatepress/)


## Résultats de la matrice finale

La matrice desktop post-correction couvre 12 routes à 1280 px : accueil, archive catégorie, fiche variable, panier, checkout et portail marchand, chacun en français et en arabe. Toutes ont `scrollWidth=1265`, zéro débordement, zéro contrôle hors canevas et zéro ressource Noto externe. La matrice mobile post-correction couvre les mêmes 12 routes à 390 × 844 px : toutes ont `scrollWidth=390`, zéro débordement et aucun contrôle de carte hors viewport. Les rapports JSON détaillés sont conservés avec cette note dans le dépôt de reprise.

Le thème **Keleva Woo 0.4.17** est actif sur le staging Hostinger. Il conserve le correctif de catégorie « Végétal » qui se replie sur plusieurs lignes et ajoute la correction globale des CTA prix/action des cartes produit sur mobile, en français comme en arabe. Le même jalon conserve les correctifs précédents des onglets WCFM RTL mobiles et des onglets produit RTL.

Le périmètre traduit de la fiche variable de recette comprend le titre, l’attribut Sauce, les choix Pesto et Miel épicé, Quick view, le libellé de paiement et More Offers. Le socle de navigation arabe a également reçu des traductions contrôlées. Les titres, descriptions, catégories, panier et checkout restants nécessitent encore une traduction éditoriale méthodique ; cette étape n’a réalisé aucune commande ni aucun paiement.
