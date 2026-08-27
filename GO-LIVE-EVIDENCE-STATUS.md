# État des preuves GO/NO-GO — Keleva

**Référence release candidate :** `8663ce3f59da5549b3c0b29b2a2245a82789d3b2` (`main`)

| Contrôle | Statut | Preuve ou action restante |
|---|---|---|
| Commit officiel et versions documentés | PASS | Manifeste 0.4.19/0.6.23 et guide de passation |
| Archive installable du thème 0.4.19 | PASS | `wordpress-package/installables/keleva-woo-0.4.19.zip` |
| Archive installable du plugin 0.6.23 | PASS | `wordpress-package/installables/keleva-woo-addons-0.6.23.zip` |
| SHA-256 des archives finales | PASS | `wordpress-package/RELEASE-SHA256-0.4.19-0.6.23.txt` |
| Manifest fichiers/dépendances/ordre/rollback | PASS | `wordpress-package/RELEASE-MANIFEST-0.4.19-0.6.23.md` |
| Correction storefront mobilier sur staging | PASS — staging uniquement | Rapport factuel `RAPPORT-CORRECTION-STOREFRONT-MOBILIER-2026-08-27.md` ; la vitrine ne publie plus que quatre meubles et les fixtures restaurant restent en brouillon réversible. |
| Checkpoint géré du portail | PASS | URI `manus-webdev://2e04398a`, commit `2e04398a863d8ecd7466735f43741f7d881246b0` ; détail dans `private-evidence-index/PORTAL-CHECKPOINT-PROOF-2026-08-27.md` |
| Restauration du checkpoint du portail sur copie | FAIL | Non exécutée ; aucune branche ou copie isolée de vérification n’a été créée. |
| Sauvegarde complète de production téléchargée | FAIL | À réaliser avec accès Hostinger production |
| Restauration de sauvegarde sur copie isolée | FAIL | À exécuter et documenter |
| Export TranslatePress privé généré | PASS | Artefact privé `keleva-translatepress-private-20260827-001010.json` ; SHA-256 `0f6bdd5e961249c15306acec74b288e0ee280d989fa75476c217c5bd25d50427` ; inventaire assaini dans `private-evidence-index/TRANSLATEPRESS-EXPORT-INVENTORY-2026-08-27.json` |
| Restauration isolée de l’export TranslatePress | FAIL | Journal de restauration absent : aucune copie WordPress isolée n’a été fournie ou créée. L’export ne doit pas être qualifié de « restaurable validé ». |
| Cause des bascules de thème et stabilité observée | N/A non approuvé | À diagnostiquer sur l’environnement concerné et faire approuver |
| Exercice de rollback sur copie | FAIL | À exécuter avec temps de retour mesuré |
| Inventaire de configuration production | FAIL | À relever séparément du staging |
| Paiements/livraison/emails/webhooks sandbox | N/A non approuvé | À valider si ces fonctions sont activées, sinon signer l’exclusion |
| Recette métier FR/AR signée | FAIL | Validation responsable métier manquante |
| SEO, sécurité et monitoring production | FAIL | Vérification production manquante |
| Signatures technique et métier | FAIL | Procès-verbal go/no-go manquant |

## Décision

**NO-GO production.** Les archives de code, l’export TranslatePress privé et le checkpoint du portail sont prouvés comme créés. Les restaurations isolées, la sauvegarde production, le rollback mesuré, l’inventaire production, la recette métier et les signatures ne sont pas disponibles. Aucun déploiement, paiement réel, commande réelle ou écrasement de données ne doit être effectué sur la base de ce fichier.
