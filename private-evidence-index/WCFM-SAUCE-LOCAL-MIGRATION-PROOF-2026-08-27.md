# Preuve assainie — migration locale WCFM et Sauce

Une migration réversible a été testée **uniquement sur une copie WordPress locale isolée**. Son périmètre a été limité aux quatre meubles publics : Fauteuil Ligne Noa, Table basse Arco, Canapé Modulaire Serein et Lampe Atelier Halo.

| Contrôle | Résultat |
|---|---|
| Snapshot préalable de la copie locale | PASS |
| Inventaire avant migration (auteur, vendeur WCFM, attribut Sauce, marqueurs hérités) | PASS |
| Retrait local limité des liaisons WCFM/restaurant et de l’attribut Sauce | PASS |
| Préservation des titres, statuts, prix et stocks des quatre meubles | PASS |
| Script inverse et comparaison fonctionnelle après rollback | PASS |
| Recette locale par URLs des fiches et du panier | FAIL/N/A — routage local non fidèle |
| Application au staging Hostinger | Non exécutée |

La procédure n’est pas publiée sous forme de SQL, afin de ne pas divulguer les détails opérationnels de la copie locale. Elle ne doit pas être appliquée au staging ou à la production tant qu’une décision séparée, une sauvegarde récente et une recette de rendu pertinente ne sont pas documentées.

Voir l’[addendum de mission](../RAPPORT-MISSION-STAGING-2026-08-27.md#migration-locale-wcfm-et-sauce) et le [registre GO/NO-GO](../GO-LIVE-EVIDENCE-STATUS.md).
