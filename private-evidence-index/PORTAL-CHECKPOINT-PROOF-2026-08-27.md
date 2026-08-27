# Preuve de checkpoint — portail marchand Keleva

## Identifiant et emplacement

Le checkpoint du portail est un snapshot géré de l’application, restaurable depuis l’historique du projet. Son identifiant court est également le préfixe du commit Git associé.

| Champ | Valeur vérifiée |
|---|---|
| URI de restauration | `manus-webdev://2e04398a` |
| ID court | `2e04398a` |
| Hash/commit complet | `2e04398a863d8ecd7466735f43741f7d881246b0` |
| Horodatage UTC | 2026-08-27T00:12:00+00:00 |
| Répertoire de travail au moment de la vérification | `/home/ubuntu/keleva-merchant-portal` |
| État Git juste après le checkpoint | propre lors de la vérification initiale |
| Contenu du checkpoint | suivi et preuves du plan GO/NO-GO ; voir le message du commit |

Le hash ci-dessus est le SHA-1 Git du snapshot géré. Ce n’est pas un SHA-256 d’archive et il ne doit pas être présenté comme tel.

## Journal disponible

> `Checkpoint: Plan GO final appliqué : versions et release réconciliées, archives officielles checksumées et manifeste publiés, preuves privées TranslatePress staging exportées puis outil temporaire retiré, checklist restauration/go-live publiée, statut NO-GO production maintenu jusqu’aux preuves production et signatures.`

Le journal Git confirme que ce checkpoint a enregistré la mise à jour de `todo.md` correspondante.

## Limite de restauration

Aucune restauration du checkpoint `2e04398a` n’a été déclenchée, afin de ne pas écraser le travail et les preuves générés après lui. Il n’existe donc **pas** de journal de restauration réussie. Cette absence est distincte de l’existence vérifiée du checkpoint et maintient le statut **NO-GO production**.

Pour produire cette preuve, le prochain opérateur doit restaurer le checkpoint sur une copie ou utiliser l’historique du projet pour créer une branche de vérification, contrôler l’URL, lancer les tests Vitest et archiver le journal horodaté. Cette opération ne doit pas être confondue avec la restauration de la sauvegarde WordPress/Hostinger de production.
