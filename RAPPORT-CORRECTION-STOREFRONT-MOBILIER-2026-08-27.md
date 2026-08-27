# Rapport de correction storefront mobilier — 27 août 2026

**Périmètre :** staging Hostinger uniquement, sur [aliceblue-bison-433987.hostingersite.com](https://aliceblue-bison-433987.hostingersite.com/).  
**Décision :** la vitrine staging est corrigée vers l’univers mobilier ; la décision globale demeure **NO-GO production**.  
**Données protégées :** aucune commande réelle, aucun paiement, aucune donnée client et aucune suppression définitive n’ont été exécutés.

> La régression provenait d’un décalage entre le thème déjà orienté décoration/objets et des fixtures WooCommerce alimentaires encore publiées. Cette intervention corrige l’exposition publique sur le staging, mais ne remplace ni sauvegarde, ni restauration isolée, ni validation de production.

## Résultat public

| Contrôle | Résultat | Preuve publique |
|---|---:|---|
| Accueil et sélection éditoriale | PASS | [Accueil Velora](https://aliceblue-bison-433987.hostingersite.com/) : hero, catégories et cartes orientés mobilier. |
| Archive catalogue française | PASS | [Boutique](https://aliceblue-bison-433987.hostingersite.com/boutique/) : quatre meubles et catégories Assises, Tables, Luminaires uniquement. |
| Archive catalogue arabe RTL | PASS avec reliquats linguistiques | [Boutique AR](https://aliceblue-bison-433987.hostingersite.com/ar/boutique/) : mêmes quatre meubles, navigation RTL ; noms, catégories et descriptions de ces nouveaux contenus restent en français. |
| Fiche fauteuil et configurateur | PASS | [Fauteuil Ligne Noa](https://aliceblue-bison-433987.hostingersite.com/produit/fauteuil-ligne-noa/) : prix simple, radio Revêtement et cases Services, sans menu de variation « Sauce ». |
| Fiche fauteuil arabe | PASS avec reliquats linguistiques | [Fauteuil Ligne Noa AR](https://aliceblue-bison-433987.hostingersite.com/ar/product/fauteuil-ligne-noa/) : RTL, image chargée et libellé de marque accessible normalisé. |
| Panier de recette | PASS | Panier remis à zéro après test ; aucun checkout ni création de commande. |

## Catalogue de démonstration maintenant publié

| ID WooCommerce | Produit | Catégorie | Prix de base | Média principal |
|---:|---|---|---:|---:|
| 24 | Fauteuil Ligne Noa | Assises | 4 900 MAD | #462 |
| 28 | Table basse Arco | Tables | 5 400 MAD | #463 |
| 31 | Canapé Modulaire Serein | Assises | 12 900 MAD | #465 |
| 34 | Lampe Atelier Halo | Luminaires | 1 800 MAD | #464 |

Les pages publiques des quatre objets ont été vérifiées après conversion en produits simples. Elles ne présentent donc plus de fourchette de prix issue d’anciennes variations. Le thème charge désormais un repli JPEG lorsqu’un dérivé AVIF/WebP de galerie échoue au décodage, ce qui a permis le retour de l’image produit sur la fiche RTL.

## Recette du configurateur Fauteuil Ligne Noa

| Élément testé | Attendu | Observé | Statut |
|---|---|---|---:|
| Type/prix | Produit simple à 4 900 MAD | Prix unique 4 900 MAD, sans plage ni sélecteur « Sauce » | PASS |
| Revêtement obligatoire | Radio Bouclé ivoire ou Lin sable (+250 MAD) | Lin sable sélectionné | PASS |
| Services facultatifs | Montage (+450 MAD) et Protection textile (+320 MAD), limite définie à 2 | Les deux cases sélectionnées et état coché visible | PASS |
| Calcul panier | 4 900 + 250 + 450 + 320 = 5 920 MAD | Ligne panier à 5 920 MAD avec les trois choix transmis | PASS |
| Clavier | Focus visible et activation sans souris | Contour de focus visible ; touche Espace sélectionne le radio | PASS |
| Nettoyage | Aucun panier conservé à l’issue | Ligne de recette retirée ; panier final vide | PASS |

Les vérifications ont été réalisées sans cliquer sur le checkout et sans ouvrir le parcours WhatsApp. Les deux services disponibles permettent d’atteindre la limite configurée de deux ; aucune hypothèse n’est faite sur un troisième choix inexistant.

## Retrait réversible des fixtures restaurant

L’archive boutique exposait encore onze fixtures alimentaires en plus des meubles. Les identifiants **#172, #125, #122, #118, #115, #112, #108, #105, #102, #98 et #95** ont été passés de `publish` à `draft` par l’API REST WordPress authentifiée, avec onze réponses HTTP 200. Les produits et catégories historiques n’ont pas été supprimés : cette mesure est réversible depuis l’administration staging.

La surcharge `woocommerce/archive-product.php` restreint la navigation publique aux slugs `assises`, `tables`, `rangements` et `luminaires`. Avec `hide_empty`, la catégorie Rangements n’apparaîtra qu’après publication d’au moins un meuble dans cette catégorie. Les caches LiteSpeed de page et OPcache ont été purgés avant relecture de l’archive FR/AR.

## Sources, nettoyage et validations

| Élement | État | Détail |
|---|---:|---|
| Routine temporaire `keleva_apply_furniture_demo` | Retirée | Le fichier cœur du plugin a été restauré ; le menu temporaire « Keleva — configuration mobilier » a disparu de l’administration. |
| Serveur local de transfert | Arrêté | Port local 8777 libéré après déploiement ; aucun service de transfert ne reste actif. |
| Sources modifiées | À intégrer dans une prochaine release versionnée | `front-page.php`, `inc/cache.php`, `style.css`, `woocommerce/content-single-product.php`, `woocommerce/archive-product.php`, `assets/js/accessibility.js`, `class-restaurant-extras.php`. |
| Lint PHP | PASS | Sept fichiers PHP corrigés vérifiés sans erreur de syntaxe. |
| Validation JavaScript | PASS | `node --check` sur `assets/js/accessibility.js`. |
| Cohérence Git | PASS | `git diff --check` sans erreur. |
| Scan de secrets des différences | PASS | Aucun motif de mot de passe, clé API, bearer ou clé privée détecté. |
| Tests portail | PASS | Vitest : 5 fichiers, 16 tests passés. |

Les archives officielles **thème 0.4.19** et **plugin 0.6.23**, leurs checksums et le manifeste restent les artefacts de release précédemment figés. Cette correction staging ne porte pas de nouvelle version ni de nouvelle archive installable : elle est donc publiée comme **correctif source à intégrer dans une release ultérieure cohérente**, pas comme release de production.

## Limites et actions avant production

| Sujet | Statut | Action requise |
|---|---:|---|
| Traduction AR des nouveaux meubles | FAIL | Traduire les noms, catégories, options et descriptions mobilières dans TranslatePress puis rejouer la recette RTL. |
| Ancien attribut WooCommerce « Sauce » | À nettoyer dans les données | Il n’est plus rendu sur les fiches mobilier grâce au filtre d’onglets ; effectuer une migration de données auditée seulement après sauvegarde/copie isolée. |
| Fixtures restaurant | Brouillons staging | Réintégration possible ; aucune suppression n’a été faite. Décider ultérieurement de leur archivage définitif après sauvegarde validée. |
| Sauvegarde/restauration/rollback production | FAIL | Suivre le tableau officiel GO/NO-GO et la procédure de restauration sur copie isolée. |
| Signatures recette métier et technique | FAIL | Réaliser la recette métier avec contenu définitif puis signer le procès-verbal. |

## Références

1. [Accueil staging Velora](https://aliceblue-bison-433987.hostingersite.com/)
2. [Archive boutique française](https://aliceblue-bison-433987.hostingersite.com/boutique/)
3. [Archive boutique arabe](https://aliceblue-bison-433987.hostingersite.com/ar/boutique/)
4. [Fiche Fauteuil Ligne Noa française](https://aliceblue-bison-433987.hostingersite.com/produit/fauteuil-ligne-noa/)
5. [Fiche Fauteuil Ligne Noa arabe](https://aliceblue-bison-433987.hostingersite.com/ar/product/fauteuil-ligne-noa/)
6. [État officiel des preuves GO/NO-GO](GO-LIVE-EVIDENCE-STATUS.md)

