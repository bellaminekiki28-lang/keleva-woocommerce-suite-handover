# Preuve d’export TranslatePress — staging Keleva

## Artefact privé

L’artefact a été généré depuis l’administration authentifiée du staging et n’est **pas** inclus dans ce dépôt public.

| Champ | Valeur vérifiée |
|---|---|
| Nom du fichier | `keleva-translatepress-private-20260827-001010.json` |
| Taille | 241 812 octets |
| Format | `keleva-translatepress-private-v1` |
| Horodatage UTC | 2026-08-27T00:10:10+00:00 |
| SHA-256 | `0f6bdd5e961249c15306acec74b288e0ee280d989fa75476c217c5bd25d50427` |
| Emplacement de remise | Pièce jointe privée de la conversation ; ne pas publier sur GitHub |

## Périmètre exporté et méthode de relevé

Le préfixe WordPress relevé est `wp_`. L’exporteur a recherché les dictionnaires avec le motif `wp_trp_dictionary_%`, n’a retenu que les noms correspondant à `^wp_trp_dictionary_[a-z0-9_]+$`, puis a exporté leurs lignes dans le JSON privé. Une seule table répondait à ces conditions : `wp_trp_dictionary_fr_fr_ar`.

| Élément | Relevé |
|---|---:|
| Tables dictionnaire exportées | 1 |
| Table | `wp_trp_dictionary_fr_fr_ar` |
| Lignes exportées | 912 |
| Options TranslatePress exportées | 9 |

Les 912 lignes correspondent à la longueur du tableau JSON exporté pour `wp_trp_dictionary_fr_fr_ar`. Les 9 options correspondent au nombre de clés `trp_%` dans l’objet `translatepress_options`. Le relevé structuré équivalent est dans [`TRANSLATEPRESS-EXPORT-INVENTORY-2026-08-27.json`](./TRANSLATEPRESS-EXPORT-INVENTORY-2026-08-27.json).

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

L’export est **prouvé comme généré et vérifié**. En revanche, aucun journal de restauration isolée n’existe encore. Il est donc incorrect de le présenter comme « restaurable validé ». La restauration doit être exécutée sur une copie WordPress isolée, avec inventaire avant/après, puis consignation du hash, du temps de restauration et de la recette FR/AR. Le statut global reste **NO-GO production**.
