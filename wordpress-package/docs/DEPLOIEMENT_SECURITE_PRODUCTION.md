# Mise en production — sécurité du dashboard Keleva

Le dashboard Keleva est prêt à lire ses paramètres depuis l’environnement du serveur ou depuis des constantes déclarées dans `wp-config.php`. **Aucun secret ne doit être ajouté au thème, au plugin, à un dépôt Git ou à une fiche produit WordPress.**

## Paramètres requis

| Variable ou constante | Rôle | Rotation |
|---|---|---|
| `KELEVA_DASHBOARD_TOKEN` | Jeton actif du dashboard mobile | Remplacer à chaque rotation |
| `KELEVA_DASHBOARD_PREVIOUS_TOKEN` | Jeton immédiatement précédent | Retirer après la fenêtre de bascule |
| `KELEVA_DASHBOARD_WEBHOOK_URL` | URL HTTPS de réception des événements | Modifier lors du changement de service |
| `KELEVA_DASHBOARD_WEBHOOK_SECRET` | Secret de signature HMAC SHA-256 | Faire tourner avec le destinataire |

## Exemple de configuration hors code

> Les valeurs ci-dessous sont des espaces réservés. Elles doivent être remplacées dans hPanel, le gestionnaire de secrets de l’hébergeur ou `wp-config.php` **hors dépôt**.

```php
define('KELEVA_DASHBOARD_TOKEN', 'VALEUR_SECRETE_COURANTE');
define('KELEVA_DASHBOARD_PREVIOUS_TOKEN', 'VALEUR_SECRETE_PRECEDENTE');
define('KELEVA_DASHBOARD_WEBHOOK_URL', 'https://dashboard.exemple.tld/webhooks/keleva');
define('KELEVA_DASHBOARD_WEBHOOK_SECRET', 'VALEUR_SECRETE_WEBHOOK');
```

Le dashboard attend le header HTTP `X-Keleva-Dashboard-Key`. Les réponses privées portent `Cache-Control: no-store`. Les webhooks sortants n’acceptent qu’une URL HTTPS, ne suivent pas les redirections et portent les headers `X-Keleva-Signature`, `X-Keleva-Event` et `X-Keleva-Occurred-At`.

## Rotation et validation

La rotation s’effectue en définissant d’abord le nouveau jeton actif tout en conservant le précédent dans `KELEVA_DASHBOARD_PREVIOUS_TOKEN`. Après validation des clients dashboard, retirer le précédent. Le script local `test-dashboard-security.php` valide le jeton courant, le jeton précédent, le refus d’un jeton erroné, la signature HMAC et l’existence du journal d’audit persistant.

La réception du webhook doit recalculer `sha256=` suivi du HMAC SHA-256 du **corps brut** de la requête avec `KELEVA_DASHBOARD_WEBHOOK_SECRET`, puis comparer le résultat en temps constant. Avant d’activer le webhook en production, vérifier son endpoint HTTPS avec une transaction de statut produit sur une copie de préproduction, sans déclencher de commande ni modifier de données client.
