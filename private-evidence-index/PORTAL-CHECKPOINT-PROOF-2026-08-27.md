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

## Restauration sur copie isolée

Le 27 août 2026, le checkpoint `2e04398a` a été restauré dans l’espace de travail, puis copié dans un répertoire de recette privé. L’état de travail plus récent a été figé au préalable et rétabli après la recette ; le staging WordPress, le portail WordPress natif et les données Hostinger n’ont jamais été modifiés.

| Contrôle de la copie du checkpoint | Statut | Résultat vérifié |
|---|---|---|
| Dépendances verrouillées | PASS | `pnpm install --frozen-lockfile` terminé. |
| Tests et vérification TypeScript | PASS | `5` fichiers Vitest et `16` tests réussis ; `pnpm run check` terminé sans erreur. |
| Build production | PASS | `pnpm run build` terminé ; avertissement non bloquant sur la taille d’un chunk uniquement. |
| Connexion portail | PASS | Connexion par formulaire avec compte de recette strictement local. |
| Persistance de session | PASS | Dashboard maintenu après rechargement local. |
| Déconnexion et révocation | PASS | Retour au formulaire et session révoquée. |
| Identifiant invalide | PASS | Accès refusé sans divulgation d’information sensible. |

Le journal complet est privé car il inclut le chemin de copie et des détails d’environnement local. Aucun mot de passe, cookie, chaîne de connexion, URL de recette locale, donnée utilisateur ou base n’est publié.

> **Limite.** Cette restauration valide le checkpoint du portail en copie isolée ; elle ne vaut ni restauration WordPress, ni rollback complet de release, ni validation de production. Le statut global reste **NO-GO production**.
