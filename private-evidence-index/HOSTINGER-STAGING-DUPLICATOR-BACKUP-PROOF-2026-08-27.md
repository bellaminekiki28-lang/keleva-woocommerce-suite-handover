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

> **Limite de preuve.** Une archive créée, téléchargée et checksumée n’est pas une restauration validée. Aucune archive n’a été restaurée sur Hostinger, aucune copie isolée n’a encore été montée, et aucun rollback chronométré n’a été exécuté.

## État de décision

La sauvegarde staging est désormais une preuve **PASS — staging seulement**. Elle ne remplace pas la sauvegarde complète de production, qui demeure **FAIL**, et ne modifie pas la décision **NO-GO production**. La prochaine preuve acceptable consiste en une restauration sur une copie locale isolée, suivie d’un rollback chronométré, sans toucher au staging ni à la production.

## Références internes de reprise

Le détenteur autorisé retrouve le manifeste privé, l’archive et l’installeur sous le dossier privé de preuves staging du 27 août 2026. Ce dépôt publie uniquement les empreintes, la taille, la portée et le résultat des contrôles ; il ne publie pas l’archive, l’installeur, le dump SQL, une URL de téléchargement, des cookies, des mots de passe, des comptes, ni des données de commande.
