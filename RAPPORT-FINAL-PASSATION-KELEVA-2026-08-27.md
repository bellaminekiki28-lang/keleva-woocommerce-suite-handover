# Rapport final de passation — Keleva

**Date :** 27 août 2026
**Auteur :** Manus AI
**Périmètre :** staging Hostinger et copies locales isolées uniquement
**Décision officielle :** **NO-GO production**

> **Statut documentaire :** ce rapport est un instantané historique. Les résultats les plus récents de recette, d’export TranslatePress, de rollback code, de migration WCFM/Sauce et de nettoyage staging sont consolidés dans l’[addendum de mission staging](RAPPORT-MISSION-STAGING-2026-08-27.md) et le [registre GO/NO-GO](GO-LIVE-EVIDENCE-STATUS.md), qui prévalent en cas d’écart.

> Ce rapport distingue strictement les résultats prouvés sur le staging et sur des copies locales des validations de production. Aucune commande réelle, aucun paiement réel, aucune donnée client, aucune restauration Hostinger et aucune modification de production commerciale n’ont été exécutés.

## 1. Résumé exécutif

La régression qui faisait apparaître des produits restaurant a été corrigée sur le staging Keleva : l’accueil et les archives françaises et arabes n’exposent plus que quatre meubles premium. Le configurateur du fauteuil combine bien un choix radio obligatoire et des services à cocher avec majorations de prix contrôlées. Le portail marchand reste natif au domaine Hostinger, white-label et séparé de `wp-admin`.

Une sauvegarde privée Duplicator du staging a été créée, téléchargée, checksumée et vérifiée. Les fichiers et la base ont été restaurés sur une copie locale isolée. Le checkpoint historique du portail a également été restauré et recetté dans une copie indépendante, avec tests automatisés, build, connexion, persistance de session et déconnexion réussis. La validation locale du routage/rendu TranslatePress `/ar/` a en revanche échoué : ce point bloque toute déclaration de restauration bilingue complète et, a fortiori, tout GO production.

## 2. État de réalisation

| Domaine | Résultat | Portée | Preuve |
|---|---|---|---|
| Storefront mobilier | **PASS** | Staging uniquement | Accueil, boutique et fiches : [rapport mobilier][1] |
| Catalogue visible | **PASS** | Staging uniquement | Quatre meubles publiés : Fauteuil Ligne Noa, Table basse Arco, Canapé Modulaire Serein, Lampe Atelier Halo [1] |
| Configurateur fauteuil | **PASS** | Staging uniquement | Revêtement radio, services checkbox, limite, clavier et total panier 5 920 MAD [1] |
| Fixtures restaurant | **PASS — réversible** | Staging uniquement | Onze fixtures sont en brouillon, non supprimées [1] |
| Storefront arabe RTL | **PASS avec reliquats** | Staging uniquement | Structure RTL, routes et médias contrôlés ; noms/catégories/descriptions des nouveaux meubles restent à traduire [1] |
| Portail marchand white-label | **PASS** | Staging / code | Portail natif, authentification distincte, sans accès marchand à `wp-admin` [2] |
| Tests code portail actuels | **PASS** | Projet portail | Vitest : cinq fichiers, seize tests ; TypeScript vérifié [1] |
| Sauvegarde Duplicator | **PASS — staging uniquement** | Privé | Archive de 473 439 028 octets, SHA-256 attesté et intégrité ZIP vérifiée [3] |
| Restauration WordPress locale | **PASS — partiel** | Copie isolée | Fichiers et base restaurés, accueil FR et fiche configurable contrôlés ; aucune écriture Hostinger [3] |
| Restauration checkpoint portail | **PASS** | Copie isolée | Dépendances, Vitest, TypeScript, build, login, persistance, logout et révocation validés [4] |
| TranslatePress rendu `/ar/` local | **FAIL** | Copie isolée | Données et plugin présents, mais route locale `/ar/` en 404 et rendu non validé ; ne pas forcer un faux succès [2] |
| Rollback complet de release | **FAIL** | À réaliser | La sonde locale en 128 ms valide un mécanisme minimal, pas un retour complet à une release précédente [2] |
| Sauvegarde/restauration production | **FAIL** | Production | Aucune preuve production créée ni restaurée [2] |
| Recette métier et signatures | **FAIL** | Production | Validation métier, inventaire production et signatures technique/métier manquent [2] |

## 3. Pages de recette staging

Les liens suivants sont publics et peuvent être ouverts par le développeur pour constater le résultat actuel du staging. Ils ne donnent ni accès administrateur ni accès aux artefacts privés.

| Écran | URL de contrôle |
|---|---|
| Accueil mobilier FR | [https://aliceblue-bison-433987.hostingersite.com/](https://aliceblue-bison-433987.hostingersite.com/) |
| Boutique FR | [https://aliceblue-bison-433987.hostingersite.com/boutique/](https://aliceblue-bison-433987.hostingersite.com/boutique/) |
| Fauteuil Ligne Noa FR | [https://aliceblue-bison-433987.hostingersite.com/produit/fauteuil-ligne-noa/](https://aliceblue-bison-433987.hostingersite.com/produit/fauteuil-ligne-noa/) |
| Accueil AR RTL | [https://aliceblue-bison-433987.hostingersite.com/ar/](https://aliceblue-bison-433987.hostingersite.com/ar/) |
| Boutique AR RTL | [https://aliceblue-bison-433987.hostingersite.com/ar/boutique/](https://aliceblue-bison-433987.hostingersite.com/ar/boutique/) |
| Fauteuil Ligne Noa AR | [https://aliceblue-bison-433987.hostingersite.com/ar/product/fauteuil-ligne-noa/](https://aliceblue-bison-433987.hostingersite.com/ar/product/fauteuil-ligne-noa/) |
| Portail marchand natif | [https://aliceblue-bison-433987.hostingersite.com/espace-marchand/](https://aliceblue-bison-433987.hostingersite.com/espace-marchand/) |
| Portail marchand AR RTL | [https://aliceblue-bison-433987.hostingersite.com/ar/espace-marchand/](https://aliceblue-bison-433987.hostingersite.com/ar/espace-marchand/) |

## 4. Dépôt GitHub et éléments publiés

Les **sources de handover, la documentation et les preuves assainies sont publiées** sur le dépôt GitHub public ci-dessous. En revanche, les archives de sauvegarde, dump SQL, identifiants, cookies, mots de passe, URL de téléchargement et données de test privées n’y sont volontairement pas publiés.

| Ressource | Lien |
|---|---|
| Dépôt de passation | [keleva-woocommerce-suite-handover][5] |
| Dernier commit de preuves | [`c5ba4f0`][6] |
| Registre officiel GO/NO-GO | [GO-LIVE-EVIDENCE-STATUS.md][2] |
| Rapport correction mobilier | [RAPPORT-CORRECTION-STOREFRONT-MOBILIER-2026-08-27.md][1] |
| Preuve sauvegarde/restauration staging | [HOSTINGER-STAGING-DUPLICATOR-BACKUP-PROOF-2026-08-27.md][3] |
| Preuve restauration checkpoint portail | [PORTAL-CHECKPOINT-PROOF-2026-08-27.md][4] |
| Procédure staging vers production | [PROCEDURE-MIGRATION-STAGING-PRODUCTION.md][7] |
| Manifeste de release thème/plugin | [RELEASE-MANIFEST-0.4.19-0.6.23.md][8] |

## 5. Artefacts privés et règles d’accès

Les éléments suivants existent dans l’espace privé de preuves du sandbox et ne doivent pas être copiés dans GitHub public. Le développeur doit demander un transfert sécurisé ou régénérer les artefacts dans son propre environnement autorisé.

| Artefact | État | Règle de sécurité |
|---|---|---|
| Archive Duplicator staging + installeur | Présents, permissions restrictives | Ne jamais publier ni joindre dans un espace public ; conserver les SHA-256 dans le registre de preuve [3] |
| Manifeste privé backup staging | Présent | Contient portée, tailles, hashes et limites, sans dump exposé |
| Copie locale WordPress restaurée | Présente, services arrêtés | Utiliser seulement pour poursuite de recette isolée ; ne jamais l’exposer publiquement |
| Journal privé restauration WordPress | Présent | Inclut détails opérationnels de l’environnement privé |
| Journal privé restauration checkpoint portail | Présent, permissions restrictives | Confirme les tests de session ; aucun identifiant de recette n’est publiable |
| Export TranslatePress historique | Présent mais insuffisant pour le mobilier actuel | Régénérer depuis le staging mobilier avant toute nouvelle restauration ; ne pas publier le JSON brut |

## 6. Recommandations priorisées pour la suite

### P0 — Bloquants avant toute proposition de GO

1. **Régénérer un export TranslatePress privé depuis le catalogue mobilier actuel.** L’export historique date d’un état précédent ; il ne doit pas devenir une preuve de restauration des nouvelles fiches mobilier. Calculer un nouveau SHA-256, produire un manifeste privé, puis mettre à jour uniquement un index assaini dans GitHub.

2. **Reproduire le routage Hostinger dans une copie à parité d’hébergement.** Construire une recette isolée avec version PHP, serveur web, règles de réécriture, permaliens, `home/siteurl`, cache et proxy équivalents. Vérifier réellement FR/AR, RTL, panier et checkout arabe ; ne jamais marquer `/ar/` PASS sur la seule réponse HTTP d’un routeur forcé.

3. **Exécuter un rollback complet de release.** Définir l’archive antérieure exacte, simuler un incident sur copie isolée, déployer cette version, vérifier le storefront, le configurateur et le portail, mesurer le délai de retour, puis réinstaller la release candidate et rejouer la recette. Le rollback-sonde de 128 ms reste une preuve partielle.

### P1 — Préparation production contrôlée

4. **Faire l’inventaire séparé de la production réelle.** Relever domaine, versions PHP/WordPress/WooCommerce, extensions, thème, pages système, permaliens, monnaie, taxes, livraison, cache/CDN, cron, Action Scheduler, emails et droits utilisateurs. Ne pas inférer ces valeurs à partir du staging.

5. **Créer puis restaurer une sauvegarde privée de production sur copie isolée.** Cette étape doit être autorisée et documentée séparément. Elle inclut fichiers, base, médias, thème, extensions et réglages utiles. Une archive staging ne doit jamais être restaurée sur la production.

6. **Valider les intégrations ou faire approuver leur exclusion.** Paiement, livraison, email transactionnel, webhooks, idempotence, statuts et stock doivent être validés en sandbox. Toute fonction hors périmètre doit porter le statut exact `N/A — exclusion approuvée`, avec validation écrite.

### P2 — Qualité catalogue et décision métier

7. **Traduire les nouveaux meubles en arabe.** Traduire noms, catégories, descriptions et libellés d’options dans TranslatePress, puis rejouer la recette desktop/mobile de `/ar/`, `/ar/boutique/` et de la fiche fauteuil. Ne pas déclarer ce chantier clos sur la seule structure RTL.

8. **Nettoyer durablement l’attribut historique « Sauce ».** Il est masqué sur les fiches mobilier, mais demeure dans les données. Préparer une migration réversible, limitée aux quatre meubles, uniquement après succès de la sauvegarde et de la recette isolée bilingue.

9. **Obtenir les signatures métier et technique.** La recette métier doit couvrir catalogue, prix, stock, options, suppléments, panier, checkout, FR/AR, RTL, portail et responsive. Ajouter Safari iOS et Edge si ces navigateurs font partie de la cible. Produire deux signatures distinctes avant décision finale.

## 7. Décision et conditions minimales du GO

La décision actuelle est **NO-GO production**. Le passage à GO ne peut être proposé qu’après fermeture documentée de tous les échecs suivants : restauration TranslatePress du catalogue mobilier à parité d’hébergement, rollback complet de release, sauvegarde/restauration production, inventaire production, validations sandbox ou exclusions approuvées, recette métier FR/AR et signatures technique/métier.

Le développeur doit tenir le registre [GO-LIVE-EVIDENCE-STATUS.md][2] à jour. Un contrôle ne peut passer à **PASS** que lorsqu’une action a été réellement exécutée et documentée. L’existence d’une archive, d’un ZIP, d’un checkpoint ou d’un export ne suffit jamais à valider sa restauration.

## Références

[1]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/RAPPORT-CORRECTION-STOREFRONT-MOBILIER-2026-08-27.md "Rapport de correction storefront mobilier"
[2]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/GO-LIVE-EVIDENCE-STATUS.md "État des preuves GO/NO-GO"
[3]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/private-evidence-index/HOSTINGER-STAGING-DUPLICATOR-BACKUP-PROOF-2026-08-27.md "Preuve assainie de sauvegarde staging"
[4]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/private-evidence-index/PORTAL-CHECKPOINT-PROOF-2026-08-27.md "Preuve de restauration du checkpoint portail"
[5]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover "Dépôt GitHub de passation Keleva"
[6]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/commit/c5ba4f032a6452ff1b11b5a4272e1b548098883a "Commit de validation du checkpoint portail"
[7]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/PROCEDURE-MIGRATION-STAGING-PRODUCTION.md "Procédure staging vers production"
[8]: https://github.com/bellaminekiki28-lang/keleva-woocommerce-suite-handover/blob/main/wordpress-package/RELEASE-MANIFEST-0.4.19-0.6.23.md "Manifeste de release"
