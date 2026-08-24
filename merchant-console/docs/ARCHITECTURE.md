# Architecture cible — Keleva Merchant Console

## Séparation des responsabilités

La boutique publique reste servie par WordPress et WooCommerce. Elle demeure SSR et ne dépend pas de la console. WooCommerce reste la source de vérité pour les produits, variations, commandes, prix, taxes, stock et statuts.

La console est une application React/TypeScript distincte de `wp-admin`. Son navigateur ne connaît jamais une Consumer Key, une Consumer Secret, un secret webhook ou un token de connexion magasin. Les appels de la console passent uniquement par le BFF Node.js, qui applique l’authentification, les rôles, les validations Zod, le rate limiting et le journal d’audit.

```text
Navigateur marchand
  -> Console React/TypeScript
  -> BFF Node.js / tRPC
  -> coffre de connexion chiffré + base opérationnelle
  -> API WooCommerce REST v3 / WordPress Bridge

WooCommerce
  -> webhook signé
  -> endpoint BFF
  -> vérification HMAC + déduplication + audit + read model
```

## Modèles de données

| Modèle | Responsabilité | Données sensibles |
|---|---|---|
| `stores` | Référence du magasin, URL, état de connexion et dernière synchronisation | aucune clé en clair |
| `store_connections` | Identifiants API chiffrés, empreinte, révocation et rotation | clés uniquement côté serveur |
| `store_memberships` | Rôle console par magasin : propriétaire, opérateur, catalogue, lecture | aucun secret |
| `sync_runs` | Resynchronisations déclenchées, progression, résultat et erreurs | payload minimisé |
| `webhook_events` | ID de livraison, signature vérifiée, idempotence, statut, tentative et trace | jamais de secret |
| `audit_logs` | Action, acteur, cible, résultat, raison et métadonnées filtrées | jamais de token ou carte |
| `media_assets` | Original, variantes, statut de traitement, erreur, retry et fallback | objets stockés hors base |
| `import_jobs` / `import_rows` | Validation CSV, erreurs ligne, application et rollback | données métier contrôlées |

## Contrats de sécurité

Les mutations sensibles — désactivation, réactivation, suppression, variation, stock, import, resynchronisation et révocation — exigent un rôle serveur autorisé, une confirmation UI, une validation d’entrée et une écriture dans `audit_logs`. Les webhooks sont acceptés uniquement après vérification de la signature sur le corps brut ; un identifiant de livraison ou une empreinte stable empêche la double application.

Les jobs longs ne sont pas tenus en mémoire. Les transformations média, resynchronisations et imports sont matérialisés par des lignes d’état idempotentes, traitées par des callbacks déterministes et relançables. Un import passe de `ready` à `applying` au moyen d’une mise à jour conditionnelle : un second worker qui ne réserve pas la ligne s’arrête sans appeler WooCommerce. Chaque mutation produit enrichit un snapshot pré-mutation ; en cas d’échec, les créations sont supprimées et les mises à jour sont restaurées en ordre inverse. Les tests unitaires mockés couvrent création, mise à jour SKU, échec partiel avec rollback, rollback manuel et conflit de réservation ; ils ne remplacent pas une preuve contre un WooCommerce HTTPS réel. Un environnement de staging public avec TLS valide sera requis avant de vérifier des webhooks tiers, les paiements, les mesures Lighthouse et le RUM.

Les callbacks planifiés sont limités à `/api/scheduled/*` et exigent une identité planifiée authentifiée. Le callback import ne renvoie pas d’erreur WooCommerce ni de détail de connexion à l’appelant ; le diagnostic détaillé reste dans les journaux serveur et l’audit applicatif.

## Mise à niveau sécurité des dépendances — 24 août 2026

La console a été migrée vers **Express 5** avec routes wildcard nommées (`/manus-storage/*key`) et fallbacks SPA compatibles (`/{*splat}`). Le proxy de stockage reconstruit explicitement les segments du paramètre wildcard et une régression Vitest couvre le chemin de redirection signé. Les dépendances HTTP, BFF, ORM et build ont été mises à niveau, ainsi que Streamdown/Recharts ; le composant de graphique a été adapté aux types Recharts v3.

L’audit pnpm est passé de **76 avis** (dont 17 high) à **un avis moderate**, sans high ni critical. L’avis résiduel provient d’`esbuild@0.18.20` transitif à `drizzle-kit@0.31.10`, outil de migration absent du bundle de production. Une vérification du registre montre que cette version est aussi la dernière disponible ; malgré son `esbuild` direct corrigé, elle conserve `@esbuild-kit/core-utils@3.3.2`, dont la contrainte est `esbuild~0.18.20`. Un override forcé ne modifiait pas cette résolution de façon compatible et n’a pas été conservé. La configuration pnpm a été migrée vers `pnpm-workspace.yaml` pour supprimer l’avertissement de configuration dépréciée, puis une installation hors ligne contrôlée, TypeScript, **25 tests Vitest**, le build production et l’audit ont été rejoués avec succès. Cela reste un risque résiduel documenté, pas un audit à zéro. Un avertissement de taille de chunk frontend reste un sujet de performance, distinct de la sécurité.

## Contraintes WooCommerce vérifiées

WooCommerce transmet les webhooks avec une signature `X-WC-Webhook-Signature` correspondant à un HMAC-SHA256 encodé en base64 du corps brut. La livraison expose aussi `X-WC-Webhook-Delivery-ID`, qui sert de clé de déduplication par magasin. Le BFF vérifie la signature avant de parser le JSON, conserve uniquement le digest et les métadonnées nécessaires, puis retourne `202` ou `200` pour une livraison déjà traitée. WooCommerce désactive par défaut un webhook après cinq échecs consécutifs ; la console doit donc afficher les erreurs et proposer une resynchronisation contrôlée plutôt que compter sur une reprise implicite.[1]

[1]: https://developer.woocommerce.com/docs/apis/rest-api/v2/webhooks/ "WooCommerce developer documentation — Webhooks"

## Dépendances à fournir avant validation production

| Dépendance | Usage | Condition de validation |
|---|---|---|
| URL WooCommerce de staging en HTTPS valide | API, webhooks, E2E et Lighthouse | aucun certificat auto-signé |
| Consumer Key/Secret WooCommerce à privilège limité | BFF uniquement | stockage chiffré, révocation testée |
| Secret webhook par magasin | vérification HMAC | livraison signée réellement reçue |
| Marché cible | sélection carte, wallet et moyen local | sandbox officielle des fournisseurs retenus |
| Compte Merchant Center et domaine vérifié | feed et diagnostics commerce | aucun flux fictif |
| Hébergeur/cache de staging | TLS, cache, CDN et observabilité | configuration représentative documentée |

## Décision React

React est réservé à la console parce qu’elle est une application métier interactive avec navigation, formulaires complexes, états asynchrones et tables denses. Il ne remplace pas WordPress ni le rendu public de la boutique. Une interface PHP/WordPress pourrait couvrir un MVP plus petit, mais ne satisferait pas, à elle seule, l’exigence explicite du CDC d’une console externe TypeScript et d’un BFF séparé.
