# État des preuves GO/NO-GO — Keleva

**Référence packages de release :** `8663ce3f59da5549b3c0b29b2a2245a82789d3b2` (`main`). **Dernière correction source storefront staging :** `8b08a45f1203fc9c5fb7b9aa4e4d9b61b8694c82`.

| Contrôle | Statut | Preuve ou action restante |
|---|---|---|
| Commit officiel et versions documentés | PASS | Manifeste 0.4.19/0.6.23 et guide de passation |
| Archive installable du thème 0.4.19 | PASS | `wordpress-package/installables/keleva-woo-0.4.19.zip` |
| Archive installable du plugin 0.6.23 | PASS | `wordpress-package/installables/keleva-woo-addons-0.6.23.zip` |
| SHA-256 des archives finales | PASS | `wordpress-package/RELEASE-SHA256-0.4.19-0.6.23.txt` |
| Manifest fichiers/dépendances/ordre/rollback | PASS | `wordpress-package/RELEASE-MANIFEST-0.4.19-0.6.23.md` |
| Correction storefront mobilier sur staging | PASS — staging uniquement | Rapport factuel `RAPPORT-CORRECTION-STOREFRONT-MOBILIER-2026-08-27.md` ; la vitrine ne publie plus que quatre meubles et les fixtures restaurant restent en brouillon réversible. |
| Sauvegarde privée complète du staging créée et checksumée | PASS — staging uniquement | Archive privée de `473439028` octets, SHA-256 `39eee23dd79c48aef36dfd8065b25e09eaadf5eef63a27664397c08a235b8cd0`, intégrité ZIP vérifiée sans extraction ; preuve assainie : `private-evidence-index/HOSTINGER-STAGING-DUPLICATOR-BACKUP-PROOF-2026-08-27.md`. |
| Checkpoint géré du portail | PASS | URI `manus-webdev://2e04398a`, commit `2e04398a863d8ecd7466735f43741f7d881246b0` ; détail dans `private-evidence-index/PORTAL-CHECKPOINT-PROOF-2026-08-27.md` |
| Restauration du checkpoint du portail sur copie | FAIL | Non exécutée ; aucune branche ou copie isolée de vérification n’a été créée. |
| Sauvegarde complète de production téléchargée | FAIL | À réaliser avec accès Hostinger production |
| Restauration fichiers/base de sauvegarde staging sur copie isolée | PASS — local/staging uniquement | `25751` fichiers extraits et dump importé dans une base locale dédiée : `101` tables et `719` lignes `wp_options`. Accueil FR et fiche configurable contrôlés localement ; aucune écriture Hostinger. Détail : `private-evidence-index/HOSTINGER-STAGING-DUPLICATOR-BACKUP-PROOF-2026-08-27.md`. |
| Export TranslatePress privé généré | PASS | Artefact privé `keleva-translatepress-private-20260827-001010.json` ; SHA-256 `0f6bdd5e961249c15306acec74b288e0ee280d989fa75476c217c5bd25d50427` ; inventaire assaini dans `private-evidence-index/TRANSLATEPRESS-EXPORT-INVENTORY-2026-08-27.json` |
| Restauration isolée de l’export TranslatePress | FAIL | Les données et tables TranslatePress sont importées et le plugin est actif dans la copie locale, mais la route `/ar/` reste 404 sous le serveur PHP intégré puis sous Apache local avec PHP et `mod_rewrite`; une entrée locale explicitement forcée conserve le rendu français. Une recette sous une configuration Apache/Nginx reproduisant l’hébergement est nécessaire. La restauration bilingue complète ne doit pas être qualifiée de validée. |
| Cause des bascules de thème et stabilité observée | N/A non approuvé | À diagnostiquer sur l’environnement concerné et faire approuver |
| Exercice de rollback sur copie | PASS — sonde locale seulement | Sonde MU locale visible, supprimée, puis absence contrôlée sur l’accueil ; délai end-to-end `128 ms`. Cette preuve de mécanisme ne constitue pas encore un rollback complet de release. |
| Inventaire de configuration production | FAIL | À relever séparément du staging |
| Paiements/livraison/emails/webhooks sandbox | N/A non approuvé | À valider si ces fonctions sont activées, sinon signer l’exclusion |
| Recette métier FR/AR signée | FAIL | Validation responsable métier manquante |
| SEO, sécurité et monitoring production | FAIL | Vérification production manquante |
| Signatures technique et métier | FAIL | Procès-verbal go/no-go manquant |

## Décision

**NO-GO production.** Les archives de code, l’export TranslatePress privé, le checkpoint du portail et une sauvegarde Duplicator privée du **staging** sont prouvés comme créés. Les fichiers et la base de cette sauvegarde ont été restaurés sur copie locale ; un rollback de sonde de code y a été mesuré. Toutefois, la route arabe TranslatePress n’a pas été validée sous l’émulation locale minimale, et aucun rollback complet de release ni sauvegarde de production n’est disponible. L’inventaire production, la recette métier et les signatures restent absents. Aucun déploiement, paiement réel, commande réelle ou écrasement de données ne doit être effectué sur la base de ce fichier.
