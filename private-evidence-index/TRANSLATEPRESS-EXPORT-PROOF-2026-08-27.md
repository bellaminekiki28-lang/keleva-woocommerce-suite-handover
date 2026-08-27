# Preuve d’export TranslatePress — staging Keleva

## Artefact privé

L’artefact a été généré depuis l’administration authentifiée du staging et n’est **pas** inclus dans ce dépôt public.

| Champ | Valeur vérifiée |
|---|---|
| Nom du fichier | `keleva-translatepress-private-furniture-20260827-121218.json` |
| Taille | 246 503 octets |
| Format | `keleva-translatepress-private-v2` |
| Horodatage UTC | 2026-08-27T12:12:18+00:00 |
| SHA-256 | `23cbb12b2da275ccb076ec8250bd6123dbff3f8b4d258c3f835d1d42f18eec20` |
| Remise | Artefact privé uniquement ; ne pas publier sur GitHub |

## Périmètre exporté et méthode de relevé

Le préfixe WordPress relevé est `wp_`. L’exporteur a recherché les dictionnaires avec le motif `wp_trp_dictionary_%`, n’a retenu que les noms correspondant à `^wp_trp_dictionary_[a-z0-9_]+$`, puis a exporté leurs lignes dans le JSON privé. Une seule table répondait à ces conditions : `wp_trp_dictionary_fr_fr_ar`.

| Élément | Relevé |
|---|---:|
| Tables dictionnaire exportées | 1 |
| Table | `wp_trp_dictionary_fr_fr_ar` |
| Lignes exportées | 934 |
| Options TranslatePress exportées | 9 |

Les 934 lignes correspondent à la longueur du tableau JSON exporté pour `wp_trp_dictionary_fr_fr_ar`. Les 9 options correspondent au nombre de clés `trp_%` dans l’objet `translatepress_options`. Le relevé structuré équivalent est dans [`TRANSLATEPRESS-EXPORT-INVENTORY-2026-08-27.json`](./TRANSLATEPRESS-EXPORT-INVENTORY-2026-08-27.json).

| Options relevées |
|---|
| `trp_advanced_settings` |
| `trp_db_stored_data` |
| `trp_language_switcher_settings` |
| `trp_onboarding_started` |
| `trp_plugin_optin` |
| `trp_plugin_version` |
| `trp_settings` |
| `trp_updated_database_gettext_original_lookup_hash` |
| `trp_updated_database_gettext_tables_optimization` |

> Les produits, commandes, clients, comptes WordPress, mots de passe, cookies, clés de paiement et options WordPress générales ne faisaient pas partie de la collecte.

## Limite de la preuve

L’export est **prouvé comme généré et vérifié**. Les données TranslatePress ont été importées dans une copie WordPress isolée, mais la route locale `/ar/` reste en 404 sous les configurations testées. Il est donc incorrect de présenter la restauration bilingue complète comme validée. Une recette à parité de routage avec l’hébergement reste nécessaire. Le statut global demeure **NO-GO production**.
