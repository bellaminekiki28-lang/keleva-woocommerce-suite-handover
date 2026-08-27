# Rapport de preuves final — Keleva

## Objet et décision

Ce rapport corrige et précise l’état de la remise. **La release code est PASS. La préparation production reste incomplète. La décision est NO-GO production.** Il ne remplace pas la checklist de migration ; il pointe vers les preuves dont l’existence est vérifiable.

| Domaine | État | Preuve |
|---|---|---|
| Release code | PASS | Commit source `8663ce3f59da5549b3c0b29b2a2245a82789d3b2`, thème 0.4.19, plugin 0.6.23, archives et SHA dans `wordpress-package/` |
| Export TranslatePress | PASS — export généré | [`TRANSLATEPRESS-EXPORT-PROOF-2026-08-27.md`](./private-evidence-index/TRANSLATEPRESS-EXPORT-PROOF-2026-08-27.md) et inventaire JSON associé |
| Restauration isolée TranslatePress | FAIL | Aucun journal de restauration ne peut être fourni, car l’exercice n’a pas été exécuté |
| Checkpoint portail | PASS — checkpoint créé | [`PORTAL-CHECKPOINT-PROOF-2026-08-27.md`](./private-evidence-index/PORTAL-CHECKPOINT-PROOF-2026-08-27.md) |
| Restauration du checkpoint | FAIL | Aucun rollback/restore n’a été déclenché après la création du checkpoint |
| Sauvegarde et restauration production | FAIL | À réaliser sur une copie isolée sous contrôle Hostinger |
| Rollback, inventaire production et signatures | FAIL | À réaliser avant tout GO |

## Export TranslatePress privé

L’artefact privé est remis séparément au demandeur et ne doit pas être commité. Son nom exact est `keleva-translatepress-private-20260827-001010.json`, son horodatage est `2026-08-27T00:10:10+00:00`, sa taille est 241 812 octets et son SHA-256 est `0f6bdd5e961249c15306acec74b288e0ee280d989fa75476c217c5bd25d50427`.

Le relevé montre le préfixe `wp_`, la table `wp_trp_dictionary_fr_fr_ar` et 912 lignes. Les neuf options exportées suivent le motif `trp_%` et sont listées dans le relevé public. Le contenu des traductions, les valeurs d’options et tout SQL brut restent privés.

> La preuve de génération d’un export n’est pas la preuve d’une restauration réussie. La restauration isolée reste donc FAIL.

## Checkpoint du portail

Le checkpoint géré est accessible via `manus-webdev://2e04398a`. Son hash Git complet est `2e04398a863d8ecd7466735f43741f7d881246b0`, son horodatage est `2026-08-27T00:12:00+00:00` et son répertoire de travail associé est `/home/ubuntu/keleva-merchant-portal`. L’URI et le hash sont deux identifiants de la même snapshot, pas une archive WordPress.

Le checkpoint existe et son journal est documenté ; aucune restauration n’a été effectuée afin de ne pas écraser les preuves créées ensuite. Il n’existe donc pas de journal de restauration réussie, et cela reste FAIL dans le tableau GO/NO-GO.

## Preuves et actions restantes

Le détail des contrôles est maintenu dans [`GO-LIVE-EVIDENCE-STATUS.md`](./GO-LIVE-EVIDENCE-STATUS.md). Avant la production, il faut créer une copie isolée, restaurer d’abord l’export TranslatePress puis la sauvegarde WordPress selon le runbook, documenter les résultats, mesurer un rollback, relever la configuration production, puis recueillir les signatures technique et métier.
