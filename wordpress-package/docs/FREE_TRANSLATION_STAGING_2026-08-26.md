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

La recette CDP mobile a porté sur l’accueil arabe, la fiche variable arabe et le portail arabe à 390 × 844 px. Elle a détecté un onglet WCFM `Enquiries` hors canevas ; le thème Keleva Woo 0.4.14 le distribue maintenant dans une grille RTL à deux colonnes. Après correction, chaque route a `scrollWidth=390`, aucun débordement horizontal et aucune zone interactive hors viewport.

## Limites à respecter

La voie gratuite ne remplace pas une traduction faite par un humain : titres, descriptions, catégories, attributs visibles, libellés d’option, contenus du panier et contenus de checkout doivent être traduits explicitement dans l’éditeur visuel. Certaines chaînes WooCommerce dynamiques ne deviennent sélectionnables qu’après avoir effectué l’action concernée, par exemple sélectionner une variante ou ouvrir le panier. [2]

Ne réactivez pas Polylang en parallèle de TranslatePress. En cas de retour arrière, désactivez TranslatePress puis réactivez Polylang, avant de purger le cache et de rejouer les routes françaises et arabes. Ne créez pas de produit AR séparé pour « traduire » un produit existant : les données commerciales doivent rester sur le produit WooCommerce canonique.

## Suite de recette

Traduire d’abord une seule fiche variable de recette et une seule catégorie. Rejouer ensuite, sans commande réelle, l’accueil, l’archive catégorie, la fiche variable avec chaque option, le panier, le checkout et le portail marchand arabe connecté. Avant une mise en production, refaire ces recettes sur une copie de contenu validée par le propriétaire.

## Références

[1] [TranslatePress — extension officielle WordPress.org](https://wordpress.org/plugins/translatepress-multilingual/)

[2] [TranslatePress — traduire les produits WooCommerce](https://translatepress.com/translate-woocommerce-products-translatepress/)
