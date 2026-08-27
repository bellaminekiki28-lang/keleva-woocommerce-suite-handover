# Preuve assainie — sauvegarde Duplicator du staging Hostinger

## Portée et confidentialité

Cette preuve atteste uniquement d’une sauvegarde complète du **staging** `aliceblue-bison-433987.hostingersite.com`, créée le 27 août 2026 avec Duplicator Lite. Elle ne concerne ni la production commerciale ni un environnement client. L’archive et l’installeur restent des artefacts privés, hors dépôt GitHub, car ils peuvent contenir les fichiers WordPress et le dump de base de données du staging.

| Élément privé | Résultat vérifié | Valeur de contrôle |
|---|---|---|
| Archive Duplicator | PASS — téléchargement achevé | `staging-duplicator-archive.zip`, `473439028` octets |
| Empreinte archive | PASS | SHA-256 `39eee23dd79c48aef36dfd8065b25e09eaadf5eef63a27664397c08a235b8cd0` |
| Installeur Duplicator | PASS — conservé privé | `installer.php`, `75275` octets |
| Empreinte installeur | PASS | SHA-256 `8d8455e1fbedea2650fa64d375c808f4808feee2ee0ee1dac3519fd919a84500` |
| Intégrité ZIP | PASS | `unzip -tqq` a terminé sans extraction ni erreur |
| Portée structurelle | PASS | L’arborescence WordPress, le thème Keleva Woo et un dump SQL Duplicator interne ont été relevés dans le listing du ZIP, sans lecture ni publication de leur contenu. |

## Restauration locale isolée

Une copie privée locale a été créée depuis cette archive sans redéployer ni restaurer Hostinger. Les `25751` fichiers ont été extraits dans un répertoire privé, puis le dump a été importé dans une base locale dédiée (`101` tables, `719` lignes `wp_options`). L’accueil français et la fiche configurable du Fauteuil Ligne Noa ont démarré et ont été contrôlés localement, sans panier, commande, paiement ni appel externe.

| Validation sur copie locale | Statut | Portée et limite |
|---|---|---|
| Fichiers et base WordPress | PASS — local/staging seulement | Extraction et import terminés ; aucune écriture Hostinger. |
| Storefront français et configurateur | PASS — local/staging seulement | Catalogue mobilier et choix radio/checkbox du fauteuil visibles. |
| Données TranslatePress | PASS — données présentes | Plugin actif ; tables de traduction importées. |
| Rendu `/ar/` TranslatePress | FAIL — non validé | La route locale `/ar/` demeure 404 sous le serveur PHP intégré, puis sous Apache local avec PHP et `mod_rewrite`. TranslatePress détecte pourtant la langue `ar`. Les adaptations temporaires ont été retirées ; une configuration Apache/Nginx isolée reproduisant l’hébergement reste requise. |
| Rollback de code | PASS — sonde locale seulement | Une sonde de code MU visible a été supprimée et son absence contrôlée sur l’accueil ; délai end-to-end observé : `128 ms`. Cette sonde ne remplace pas un rollback complet de release. |

> **Limite de preuve.** Une archive créée, téléchargée et checksumée n’est pas une restauration validée. Aucune archive n’a été restaurée sur Hostinger, aucune copie isolée n’a encore été montée, et aucun rollback chronométré n’a été exécuté.

## État de décision

La sauvegarde staging est désormais une preuve **PASS — staging seulement**. La restauration locale des fichiers et de la base ainsi qu’un rollback de sonde sont aussi prouvés, mais la recette TranslatePress arabe reste incomplète après essais sous PHP intégré et Apache local générique. Ces éléments ne remplacent pas la sauvegarde complète de production, qui demeure **FAIL**, et ne modifient pas la décision **NO-GO production**. La prochaine preuve acceptable consiste en une recette de la copie sous une configuration Apache/Nginx isolée reproduisant l’hébergement, suivie d’un rollback de release complet, sans toucher au staging ni à la production.

## Références internes de reprise

Le détenteur autorisé retrouve le manifeste privé, l’archive et l’installeur sous le dossier privé de preuves staging du 27 août 2026. Ce dépôt publie uniquement les empreintes, la taille, la portée et le résultat des contrôles ; il ne publie pas l’archive, l’installeur, le dump SQL, une URL de téléchargement, des cookies, des mots de passe, des comptes, ni des données de commande.
