# Architecture technique Keleva Woo

## Principe

Keleva Woo sépare le **storefront** du **métier WooCommerce**. Le thème rend le parcours client Velora, rapide et sans jQuery. L’extension centralise les endpoints REST, les règles d’options, le dashboard marchand, le journal d’audit et les widgets Elementor. Cette séparation permet de changer l’interface sans modifier la logique catalogue et réciproquement.

```text
Client public
  └─ Thème Keleva Woo
       ├─ Templates WooCommerce
       ├─ storefront.js sans jQuery
       └─ Store API WooCommerce

Console marchand native / dashboard serveur
  └─ REST /wp-json/keleva-dashboard/v1/
       └─ Keleva Woo Addons
            ├─ produits et statuts
            ├─ attributs et variantes
            ├─ groupes d’options
            ├─ import média
            ├─ audit persistant
            └─ webhook HMAC optionnel
```

## Routes REST Keleva

| Route | Méthode | Rôle |
| --- | --- | --- |
| `/summary` | GET | Métriques, catalogue et synthèse marchand |
| `/audit` | GET | Journal des actions Keleva |
| `/products` | GET, POST | Liste et création de produits en brouillon |
| `/products/{id}` | GET, POST | Lecture et modification produit |
| `/products/{id}/status` | POST | Publication, brouillon ou désactivation avec audit |
| `/products/{id}/configuration` | GET, POST | Attributs, variantes, prix/stock et groupes d’options |
| `/products/{id}/image` | POST | Import de photo pour un produit marchand |

Toutes ces routes requièrent le header `X-Keleva-Dashboard-Key`. En production, ce header doit être envoyé par une passerelle serveur et non exposé dans le navigateur.

## Format des groupes d’options

Les groupes sont stockés dans la meta `_keleva_product_option_groups`. Chaque groupe accepte `buttons`, `radio` ou `checkbox`, une limite de `1` à `4` et des options avec supplément éventuel.

```json
[
  {
    "id": "finition",
    "label": "Finition",
    "display": "buttons",
    "max": 1,
    "required": false,
    "options": [
      {"id": "naturelle", "label": "Naturelle", "price": 0},
      {"id": "satin", "label": "Émail satin", "price": 5}
    ]
  }
]
```

## Évolution recommandée

Utilisez Git, un environnement local Docker ou WP-CLI, et une préproduction séparée. Développez le thème et l’extension dans leurs répertoires propres ; ne modifiez pas les fichiers avec l’éditeur WordPress en production. Gardez une procédure de build reproductible pour les archives ZIP et une recette avant chaque mise en ligne.
