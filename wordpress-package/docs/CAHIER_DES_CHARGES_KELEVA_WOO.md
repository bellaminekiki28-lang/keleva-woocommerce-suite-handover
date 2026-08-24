# Cahier des charges senior — Keleva Woo

**Version :** 1.0  
**Statut :** document de cadrage pour conception et développement  
**Produit :** thème WooCommerce premium + plugin métier Elementor + console marchand externe  
**Positionnement :** storefront e-commerce ultra lisible, rapide, mobile-first et orienté conversion  
**Auteur :** Manus AI

> **Décision structurante :** Keleva Woo ne sera pas seulement un thème esthétique. Ce sera un système produit composé d’un thème WordPress léger, d’un plugin Keleva Woo Addons pour Elementor/WooCommerce et d’une console marchand mobile accessible hors de wp-admin. Le thème restera fonctionnel sans Elementor, la boutique restera fonctionnelle sans la console externe et le frontend ne dépendra pas de jQuery.

## 1. Résumé exécutif

Keleva Woo doit devenir un thème WooCommerce premium capable d’offrir une expérience de boutique comparable aux meilleurs patterns de Shopify et Uber Eats, sans transformer WordPress en application lourde. Le client doit pouvoir parcourir les produits, filtrer, consulter un produit en quick view, choisir ses variantes, ajouter au panier, modifier les quantités et finaliser sa commande sans être forcé de multiplier les pages.

Le commerçant doit pouvoir gérer ses produits, son stock et ses commandes dans une **console dédiée hors de wp-admin**, pensée comme un tableau de bord opérationnel : informations prioritaires, actions rapides, états lisibles, recherche instantanée et utilisabilité mobile. Cette console ne doit pas devenir un second WooCommerce complet ; elle doit simplifier les tâches quotidiennes et laisser les réglages avancés dans WordPress/WooCommerce lorsqu’ils sont nécessaires.

La performance doit être définie par des mesures terrain, pas par une promesse de score. Google recommande de viser au 75e percentile un LCP inférieur ou égal à 2,5 secondes, un INP inférieur à 200 millisecondes et un CLS inférieur à 0,1 [1]. **Un score PageSpeed de 100 est un objectif de laboratoire possible sur certaines vues, mais il ne peut pas être garanti sur tous les hébergeurs, appareils, extensions, produits et campagnes marketing.** Le cahier des charges vise donc des Core Web Vitals verts en données réelles et un haut score Lighthouse sur un scénario de référence contrôlé.

Le SEO et le GEO seront traités comme une conséquence d’un contenu humainement utile, crawlable, accessible et techniquement propre. Le projet ne doit pas chercher à fabriquer un signal artificiel pour les LLM. Il doit fournir des pages produit complètes, des données structurées cohérentes, des images indexables, un maillage clair, un flux Merchant Center fiable et des contenus originaux orientés usage.

## 2. Vision et objectifs

### 2.1 Vision produit

**Keleva Woo est le comptoir de commande le plus clair pour les boutiques WooCommerce ambitieuses.** Il aide le client à décider sans se perdre et le commerçant à opérer sans ouvrir dix écrans d’administration.

### 2.2 Objectifs business

| Objectif | Indicateur cible | Méthode de mesure |
|---|---:|---|
| Réduire la friction catalogue → panier | Quick view ouvert puis ajout en moins de 3 interactions principales | Analytics événementiels et session replay conforme au consentement |
| Rendre le panier toujours compréhensible | Panier visible sur desktop et barre sticky sur mobile | Tests UX, taux d’ouverture, erreurs de quantité |
| Simplifier la gestion produit | Création d’un produit simple en moins de 90 secondes après onboarding | Test utilisateur chronométré |
| Désactiver sans supprimer | Produit rendu indisponible en une action avec confirmation et undo | Test fonctionnel et audit de statut |
| Accélérer la boutique | CWV verts au p75 en mobile sur les pages de référence | CrUX/Search Console/RUM |
| Améliorer la visibilité | Pages produit crawlables, données structurées valides, flux marchand sans erreur critique | Search Console, Rich Results Test, Merchant Center |
| Réduire les erreurs d’exploitation | Aucun conflit critique avec paiement, stock, taxes ou livraison dans la matrice validée | Tests d’intégration et journal d’erreurs |

### 2.3 Non-objectifs

Keleva Woo ne doit pas recréer toute la suite d’administration WooCommerce, devenir un ERP, stocker les données bancaires, remplacer un CRM ou promettre un classement Google automatique. Il ne doit pas masquer les données produit derrière un rendu JavaScript obligatoire ni inventer des avis, notes, témoignages ou chiffres de conversion.

## 3. Utilisateurs et rôles

| Profil | Besoin principal | Interface prioritaire |
|---|---|---|
| Acheteur mobile | Trouver, comparer rapidement et commander avec le pouce | Storefront, quick view, barre panier, checkout court |
| Acheteur desktop | Parcourir avec densité, filtres et panier visible | Storefront, side cart, recherche, grille/rail produit |
| Commerçant propriétaire | Voir l’état de la boutique et agir vite | Dashboard externe mobile/desktop |
| Opérateur commandes | Traiter, filtrer, imprimer ou mettre à jour des commandes | Dashboard commandes |
| Gestionnaire catalogue | Ajouter, modifier, désactiver produits et variantes | Dashboard produits |
| Administrateur technique | Gérer intégrations, cache, permissions et logs | WordPress/WooCommerce + paramètres Keleva |
| Rédacteur SEO | Produire des contenus produits et catégories utiles | WooCommerce/console avec champs SEO guidés |

Les permissions doivent être alignées sur les capabilities WordPress/WooCommerce. Un utilisateur ne doit pas voir ou exécuter une action uniquement parce que le bouton est masqué dans l’interface. WordPress recommande de vérifier les capabilities pour chaque opération sensible [10].

## 4. Périmètre fonctionnel global

### 4.1 Livrables principaux

| Livrable | Description |
|---|---|
| Thème `keleva-woo` | Templates WordPress/WooCommerce, design tokens, rendu serveur, hooks, compatibilité Elementor et fallbacks. |
| Plugin `keleva-woo-addons` | Widgets Elementor, quick view, side cart, product grid, carousel, intégration Store API et contrôles métier. |
| Console `merchant.keleva.com` | Dashboard externe responsive, connecté de manière sécurisée à WooCommerce, avec opérations produit, stock, commandes et médias. |
| Bridge WordPress | Plugin de connexion, routes REST dédiées, webhooks, capabilities, réglages, health checks et compatibilité. |
| Pipeline médias | Conversion AVIF/WebP, variantes responsive, fallback, alt text, dimensions et suivi de traitement. |
| Documentation | Installation, personnalisation, intégration Elementor, compatibilité, cache, sécurité, SEO, maintenance et dépannage. |

### 4.2 Architecture de référence

```text
Navigateur client
    │
    ├── HTML SSR du thème Keleva Woo
    ├── CSS critique + assets conditionnels
    └── JS natif ciblé : quick view, side cart, filtres, checkout
            │
            ├── WooCommerce Store API pour panier/session storefront
            └── WordPress REST / routes Keleva pour données publiques ciblées

Commerçant
    │
    └── Console merchant.keleva.com
            │
            └── Backend-for-Frontend Keleva
                    ├── Session sécurisée et rôles
                    ├── Secrets chiffrés côté serveur
                    ├── WooCommerce REST API wc/v3
                    ├── Webhooks idempotents
                    └── Read model analytique optionnel
                            │
                            └── WordPress + WooCommerce, source de vérité métier
```

La console ne doit jamais exposer une Consumer Secret WooCommerce au navigateur. Le navigateur parle au backend-for-frontend ; celui-ci applique les permissions, journalise l’action et appelle WooCommerce. Les clés WooCommerce doivent rester côté serveur. La REST API officielle utilise des clés liées à un utilisateur et à des permissions read/write/read-write [8] [9].

## 5. Architecture technique détaillée

### 5.1 Thème WordPress

Le thème doit être développé en PHP moderne compatible avec la version WordPress et WooCommerce supportée au moment de la livraison. La structure doit être modulaire, namespaceée et testable. Les templates WooCommerce ne doivent être surchargés que lorsqu’une raison UX ou sémantique est documentée ; chaque surcharge doit être suivie par version de template WooCommerce.

Le thème doit fournir les vues suivantes : accueil, archive produit, catégorie, recherche, produit simple, produit variable, produit groupé, panier, checkout, compte, confirmation de commande, page 404, résultats sans contenu, page maintenance et pages éditoriales. Toutes les vues essentielles doivent rester utilisables sans JavaScript, avec un rendu natif acceptable.

### 5.2 Plugin Keleva Woo Addons

Le plugin doit encapsuler les widgets Elementor, les interactions et les intégrations. Il ne doit pas rendre le thème inutilisable s’il est désactivé. Chaque widget doit charger ses CSS/JS uniquement lorsqu’il est présent ou lorsqu’une fonction globale le nécessite.

Le plugin doit fournir une catégorie Elementor `Keleva Woo` et des contrôles cohérents : source de produits, requête, nombre, colonnes, breakpoints, ordre, pagination, filtres, styles, typographie, espacements, badges, boutons, animations et accessibilité. Les contrôles doivent être séparés entre contenu, style et avancé, avec des valeurs par défaut cohérentes.

### 5.3 Console marchand externe

La console doit être une application web indépendante, construite en TypeScript avec un framework React moderne, une validation de schémas côté client et serveur, une couche de requêtes typée, une gestion stricte des permissions et un système de design partagé avec Keleva Woo. Le choix exact du framework de déploiement pourra être finalisé après l’étude de l’hébergement, mais la console doit être servie hors de wp-admin et ne doit pas transmettre de secret API au client.

Pour l’authentification initiale d’un magasin, le projet doit préférer un flux d’autorisation WooCommerce ou une intégration OAuth/SSO adaptée plutôt que demander au commerçant son mot de passe WordPress. Les Application Passwords WordPress sont conçus pour des applications et scripts, sont révocables par intégration et ne sont pas destinés à une connexion humaine à wp-admin [7].

### 5.4 Synchronisation et source de vérité

WordPress/WooCommerce reste la source de vérité pour les produits, commandes, prix, taxes, stock, clients et statuts. La console peut posséder un read model séparé pour les agrégats analytiques et les recherches rapides, mais aucune donnée commerciale critique ne doit être modifiée uniquement dans ce read model.

Les webhooks doivent être idempotents, signés ou vérifiés, rejouables et observables. En cas de retard ou d’échec, la console doit afficher l’état de synchronisation et permettre une resynchronisation contrôlée. Les opérations de stock et de commande doivent éviter les doubles écritures et gérer les conflits de version.

## 6. Storefront : UX/UI de référence

### 6.1 Principes UX

La boutique doit réduire la charge cognitive sans cacher l’information. Le client doit savoir où il se trouve, ce qu’il regarde, ce qu’il a ajouté, ce qu’il lui manque pour la livraison et ce qui va se passer au clic suivant. Les actions de conversion doivent être visibles, mais l’interface ne doit pas utiliser de popups agressifs qui empêchent la lecture ou la navigation.

La direction artistique recommandée est **Signal Market** : fond ivoire chaud, encre sombre, couleur Mandarine Signal pour les actions, typographie display distinctive et texte de lecture confortable. L’orange ne doit signaler qu’une action, un état actif, une progression ou le panier.

### 6.2 Header et navigation

Le header doit comporter le logo, une recherche accessible, les catégories prioritaires, le compte, les favoris optionnels et le panier. Il peut devenir sticky après le scroll, mais doit garder un fond suffisamment opaque pour garantir le contraste. Sur mobile, la recherche ouvre un panneau ou un champ plein écran, le panier reste immédiatement disponible et la navigation ne doit pas occuper toute la hauteur.

### 6.3 Catalogue et carte produit

La carte produit doit afficher au minimum l’image, le nom, la catégorie ou taxonomie utile, le prix, le prix barré s’il est réel, le stock ou une information de disponibilité pertinente, l’action d’ajout lorsqu’elle est possible et le déclencheur quick view. Le nom et l’image doivent rester des liens HTML vers la fiche produit.

Les produits variables ne doivent pas être ajoutés directement si une variation obligatoire n’est pas sélectionnée. La carte doit alors proposer le quick view ou la fiche complète. Les états attendus sont : produit disponible, faible stock réel, rupture, précommande, désactivé, prix variable, prix sur demande, produit externe et produit nécessitant une configuration.

### 6.4 Filtres, recherche et tri

Les filtres doivent fonctionner en progressive enhancement : l’URL doit pouvoir représenter une sélection utile, les facettes inutiles ne doivent pas générer une infinité de pages indexables et un bouton de réinitialisation doit être présent. La recherche doit gérer état de chargement, résultats, aucun résultat, erreur et suggestions utiles sans remplacer les liens crawlables.

### 6.5 Quick view

Le quick view est une fonction centrale. Il doit s’ouvrir au-dessus du catalogue, conserver la position de scroll et se fermer par le bouton, Échap, clic extérieur contrôlé ou navigation retour. Le focus doit entrer dans le panneau et revenir au déclencheur à la fermeture. Le contenu doit inclure image, titre, prix, description courte, stock, variations, quantité, ajout au panier, message de résultat et lien vers la fiche complète.

L’ajout doit utiliser le Store API WooCommerce lorsque possible et renvoyer l’état actualisé du panier. L’API documente les opérations d’ajout, suppression, mise à jour, coupons, client et choix du tarif de livraison ; ses mutations exigent un nonce ou Cart Token [11]. Le quick view doit afficher une erreur lisible si le produit n’est plus disponible et ne doit jamais simuler une réussite.

### 6.6 Side cart desktop

Sur desktop, le side cart est un rail sticky à droite du catalogue. Il affiche les lignes, images, quantités, suppression, sous-total, remise, livraison, seuil de livraison offerte, taxes lorsque pertinent et CTA checkout. Il doit rester compact et ne pas réduire le catalogue à une largeur inutilisable.

Le rail doit être cohérent avec les règles de cache. Le panier, le compte et le checkout ne doivent pas être servis comme une page publique statique. LiteSpeed documente une gestion WooCommerce qui respecte les pages non cacheables et les changements de stock ; l’intégration devra néanmoins être testée avec les extensions du projet [6].

### 6.7 Barre panier mobile

Sur mobile, le side cart devient une barre sticky basse respectant la safe area iOS. Elle affiche quantité, total et bouton `Voir le panier`. Son ouverture crée un drawer ou une page de panier complète, sans recouvrir les champs importants. Elle ne doit pas bloquer les boutons système, la navigation du navigateur ou les lecteurs d’écran.

### 6.8 Checkout

Le checkout doit être court, séquencé et rassurant. Les champs doivent être regroupés par intention : coordonnées, livraison, paiement et résumé. La validation doit être inline, les erreurs associées aux champs, les moyens de paiement visibles et les états de chargement explicites.

Le thème doit supporter WooCommerce Cart/Checkout Blocks et le checkout classique. Les extensions de paiement, livraison, taxes, conformité ou champs personnalisés peuvent ne pas être compatibles avec les blocs ; WooCommerce recommande de vérifier cette compatibilité et de prévoir un retour au checkout classique en cas de besoin [12]. Le projet ne doit pas supprimer les identifiants WooCommerce attendus ni contourner les règles des passerelles.

## 7. Console marchand : dashboard hors WordPress

### 7.1 Principes d’interface

Le dashboard doit ressembler à un **centre opérationnel mobile-first**, pas à une copie de wp-admin. La première vue doit répondre à quatre questions : qu’est-ce qui se passe aujourd’hui, quelles commandes nécessitent une action, quels produits sont en rupture ou désactivés, et quelle anomalie mérite l’attention.

| Zone | Desktop | Mobile |
|---|---|---|
| Navigation | Sidebar persistante avec sections | Barre basse ou drawer compact |
| KPI | Cartes de synthèse avec variation et période | Cartes empilées, une information forte par écran |
| Commandes | Tableau filtrable et panneau détail | Liste de cartes avec actions rapides |
| Produits | Table, filtres, actions en masse | Liste avec édition rapide et recherche |
| Éditeur | Deux colonnes, prévisualisation et panneau propriétés | Étapes successives avec autosave contrôlé |
| Alertes | Centre d’activité et anomalies | Inbox d’actions urgentes |
| Images | Upload, recadrage, variantes et statut AVIF | Capture/upload direct, statut asynchrone clair |

### 7.2 Écran d’accueil marchand

L’accueil doit afficher les commandes à traiter, chiffre d’affaires de la période sélectionnée, panier moyen si calculable, produits actifs, alertes de stock, tâches de contenu, traitement média en attente et état de synchronisation. Les métriques doivent indiquer période, devise, fuseau horaire et source. Aucun indicateur ne doit être nommé « conversion » sans définition précise.

### 7.3 Gestion des produits

Le commerçant doit pouvoir créer, modifier, dupliquer, désactiver, réactiver, archiver et rechercher un produit. La désactivation ne supprime pas les données : elle change le statut, purge ou invalide les caches nécessaires, retire le produit des listes de vente et conserve l’URL avec un comportement SEO défini.

Le formulaire produit doit être divisé en sections : identité, description, prix, stock, expédition, attributs, variations, médias, SEO, visibilité et aperçu. L’interface doit distinguer les champs obligatoires, recommandés et optionnels. L’autosave doit produire des brouillons versionnés, pas écraser silencieusement un produit publié.

| Fonction produit | Exigence |
|---|---|
| Ajout simple | Nom, slug, description, prix, image, stock et statut en moins de 90 secondes après prise en main. |
| Variantes | Attributs, matrice de variations, prix/stock/image par variation, validation des combinaisons. |
| Désactivation | Confirmation claire, raison optionnelle, undo court, journal d’action, purge de cache. |
| Actions en masse | Activer, désactiver, stock, catégorie et export ; confirmation avant action destructive. |
| Recherche | Nom, SKU, slug, catégorie, état et stock ; réponse progressive sur grands catalogues. |
| Aperçu | Prévisualisation responsive avant publication, sans exposer un brouillon aux moteurs. |
| Import/export | CSV sécurisé, rapport d’erreurs ligne par ligne, idempotence et rollback documenté. |

### 7.4 Commandes

La liste commandes doit fournir recherche par numéro, client, email, statut, date et montant. Le panneau détail doit afficher les lignes, taxes, livraison, paiement, notes, adresse, historique de statut et actions autorisées. Les actions de remboursement, annulation et changement de statut doivent exiger une confirmation et être journalisées.

### 7.5 Stock et alertes

Le stock doit afficher quantité disponible, seuil bas, backorders, produits désactivés et variations problématiques. Une alerte ne doit pas être envoyée deux fois pour le même événement sans raison. Le dashboard doit indiquer l’heure de dernière synchronisation et la source du stock.

### 7.6 Médias et AVIF

L’upload doit accepter les formats source nécessaires, conserver l’original et lancer une conversion asynchrone. Chaque média doit produire des tailles responsive et, selon la politique qualité, AVIF et WebP. L’interface doit montrer : original reçu, traitement en cours, variantes disponibles, erreur, retry et fallback actif.

La livraison recommandée est :

```html
<picture>
  <source type="image/avif" srcset="produit-480.avif 480w, produit-960.avif 960w">
  <source type="image/webp" srcset="produit-480.webp 480w, produit-960.webp 960w">
  <img src="produit-960.jpg"
       srcset="produit-480.jpg 480w, produit-960.jpg 960w"
       sizes="(max-width: 768px) 50vw, 320px"
       width="960" height="960"
       alt="Description réelle du produit">
</picture>
```

Google recommande les éléments `img`, un fallback `src`, `srcset`/`picture`, les formats AVIF/WebP, des dimensions connues, des noms descriptifs et un alt informatif sans keyword stuffing [3]. LiteSpeed/QUIC.cloud peut fournir une pipeline AVIF, mais il faut choisir un seul optimiseur d’images pour éviter les conflits et tenir compte du quota Advanced pour AVIF [4] [5].

### 7.7 Réglages et intégrations

Le dashboard doit permettre de connecter ou diagnostiquer le magasin, voir l’état API, vérifier les webhooks, tester la synchronisation, gérer les notifications et ouvrir le panneau WordPress pour les réglages avancés. Les secrets restent côté serveur et doivent être révocables. Le système doit fournir une procédure de déconnexion qui révoque ou invalide la liaison.

## 8. Elementor et personnalisation

Le thème doit être compatible Elementor Free et Elementor Pro dans les limites déclarées. Elementor Pro ne doit pas être requis pour consulter la boutique, mais il peut être requis pour certaines zones de Theme Builder. Le plugin Keleva Woo Addons doit fournir les widgets métier suivants en priorité :

| Priorité | Widget | Objectif |
|---:|---|---|
| P0 | Keleva Product Grid | Catalogue, requête, cartes, pagination et modes responsive. |
| P0 | Keleva Product Card | Carte réutilisable avec prix, stock, actions et quick view. |
| P0 | Keleva Quick View | Dialog produit avec variantes, quantité et ajout. |
| P0 | Keleva Side Cart | Panier sticky desktop et synchronisation Store API. |
| P0 | Keleva Mobile Cart Bar | Barre panier sticky mobile. |
| P0 | Keleva Mini Cart | Résumé panier dans header et composants. |
| P0 | Keleva Add to Cart | Ajout simple, variation obligatoire, états et fallback. |
| P1 | Keleva Product Filters | Catégories, attributs, prix, stock et URL contrôlable. |
| P1 | Keleva Product Search | Recherche AJAX progressive, accessible et crawlable. |
| P1 | Keleva Product Media | Galerie, lightbox, zoom optionnel et images AVIF/WebP. |
| P1 | Keleva Product Badges | Promotions, nouveau, stock bas réel et labels administrables. |
| P1 | Keleva Carousel | Grille, scroll-snap mobile et contrôles accessibles. |
| P1 | Keleva Product Archive Header | Titre, description, tri, compteurs et breadcrumbs. |
| P2 | Keleva Checkout Shell | Wrapper visuel compatible Blocks/classique sans casser WooCommerce. |
| P2 | Keleva Product Tabs | Description, informations, livraison et contenu réel. |
| P2 | Keleva Wishlist | Intégration optionnelle, sans stockage fragile ni fausse donnée. |
| P2 | Keleva Compare | Comparaison optionnelle, progressive et désactivable. |
| P3 | Keleva Mega Menu | Navigation éditoriale et catégories, chargée seulement si activée. |
| P3 | Keleva Analytics Cards | KPI visibles dans des pages marchandes, non comme moteur de vérité. |

Chaque widget doit posséder un rendu serveur, une version editor/frontend, un mode vide, un mode erreur, un mode sans JS, des contrôles responsive, des labels accessibles et une documentation de compatibilité. Les styles doivent être namespaceés `.keleva-*` pour éviter les conflits Elementor et WooCommerce.

## 9. Design system

### 9.1 Tokens

Le design system doit centraliser couleurs, typographies, espaces, rayons, ombres, largeurs de conteneur, hauteurs de contrôles, z-index et motion. Les tokens doivent être exposés à la fois en CSS variables et dans les contrôles Elementor utiles.

| Token | Règle recommandée |
|---|---|
| Fond | Ivoire chaud ou neutralité de marque configurable, jamais dépendante d’une image pour le contraste. |
| Texte | Encre sombre avec contraste vérifié. |
| Action | Mandarine Signal réservé aux CTA, états actifs, progression et panier. |
| Confirmation | Vert réservé aux succès, disponibilité réelle et sécurité. |
| Typographie | Display distinctive pour titres, sans-serif lisible pour interface et chiffres. |
| Contrôle | Zone tactile minimale 44 × 44 CSS px sur mobile. |
| Rayons | Système limité et cohérent ; pas de cartes uniformément sur-arrondies. |
| Motion | Transitions courtes, `transform`/`opacity`, désactivation avec `prefers-reduced-motion`. |

### 9.2 États obligatoires

Chaque composant interactif doit documenter les états normal, hover, focus visible, active, disabled, loading, success, error, empty, unavailable et offline lorsque pertinent. Les états de produit doivent être compréhensibles sans couleur seule.

## 10. Performance, LiteSpeed et infrastructure

### 10.1 Budgets d’acceptation

| Mesure | Objectif p75/contrôlé | Condition |
|---|---:|---|
| LCP réel | ≤ 2,5 s | Mobile et desktop, pages de référence [1]. |
| INP réel | < 200 ms | Mobile et desktop, interactions catalogue/quick view [1]. |
| CLS réel | < 0,1 | Mobile et desktop [1]. |
| PageSpeed mobile | ≥ 90 sur catalogue, produit et catégorie | Scénario contrôlé, sans tiers non prévu. |
| Lighthouse mobile | ≥ 95 sur pages de référence | Build production, cache chaud et réseau simulé documenté. |
| TTFB cache chaud | ≤ 600 ms cible | Hébergement LiteSpeed configuré. |
| JavaScript initial | ≤ 80 kB gzip cible | Catalogue sans fonctionnalités non utilisées. |
| CSS critique | ≤ 30 kB gzip cible | Above-the-fold de la page test. |
| Images LCP | ≤ 150 kB cible | AVIF/WebP selon complexité visuelle. |
| Requêtes réseau initiales | ≤ 50 cible | Hors analytics consentis et dépendances nécessaires. |

Ces budgets sont des critères d’ingénierie et non une garantie universelle. Les données réelles doivent être mesurées au 75e percentile et séparées mobile/desktop, car Google recommande cette méthode pour les Core Web Vitals [1].

### 10.2 LiteSpeed Cache

Le déploiement de référence doit utiliser LiteSpeed Enterprise ou OpenLiteSpeed avec LSCache lorsque l’hébergement le permet, HTTPS, HTTP/2 ou HTTP/3 selon l’environnement, Brotli si disponible, compression, cache objet Redis et cron système réel.

La stratégie de cache doit être explicitement documentée :

| Zone | Cache public | Règle |
|---|---:|---|
| Accueil éditorial | Oui | Purge ciblée après changement de contenu. |
| Catégories | Oui sous conditions | Purge après produit, stock, prix ou taxonomie. |
| Fiche produit | Oui sous conditions | Purge après stock, prix, variation ou visibilité. |
| Panier | Non comme page publique | État via Store API, ESI ou mécanisme Woo compatible. |
| Compte | Non | Réponse personnalisée. |
| Checkout | Non | Paiement, session, shipping et nonce. |
| Dashboard marchand | Non via cache public | Session privée et headers adaptés. |

Le projet ne doit pas empiler plusieurs plugins d’optimisation d’images. LiteSpeed recommande de choisir une seule solution d’optimisation, et sa documentation décrit la génération de variantes AVIF/WebP et le fallback navigateur [4].

### 10.3 JavaScript et jQuery

Le frontend Keleva doit fonctionner sans jQuery. Les modules doivent utiliser `fetch`, `AbortController`, `URLSearchParams`, `dialog` ou primitives accessibles équivalentes, `IntersectionObserver` avec fallback et transitions CSS. Aucune dépendance globale ne doit injecter jQuery sur les pages qui n’en ont pas besoin.

Une exception peut exister dans l’administration WordPress ou dans une extension tierce, mais elle doit être isolée et ne doit pas entrer dans le budget frontend Keleva. Le pipeline CI doit échouer si jQuery est importé dans les modules Keleva sans justification explicite.

## 11. SEO, GEO et contenu produit

### 11.1 Principe

Le guide Google fourni dans le brief indique que l’optimisation pour les fonctionnalités génératives repose sur les fondamentaux Search : contenu utile et original, crawlabilité, indexation, bonne expérience, structure claire et informations produit fiables. Il n’existe pas de raccourci technique garanti pour être cité par un LLM.

### 11.2 Exigences techniques

| Élément | Exigence |
|---|---|
| HTML | Titres, descriptions, prix, disponibilité et liens présents dans le HTML rendu. |
| Canonical | Une URL canonique par produit, catégorie et contenu éditorial. |
| Facettes | Paramètres maîtrisés, noindex/canonical selon intention, pas de génération infinie. |
| Sitemap | Sitemap pages, produits, catégories et images selon le plugin SEO choisi. |
| Robots | Règles explicites pour panier, compte, checkout, recherche interne et facettes inutiles. |
| Schema Product | `Product`, `Offer`, disponibilité, prix, devise, image et SKU réels. |
| Schema Breadcrumb | `BreadcrumbList` alignée avec les liens visibles. |
| Organization | Marque, logo, contacts et profils réellement possédés. |
| FAQ | FAQ utile et visible, sans répétition artificielle ni contenu généré pour chaque requête. |
| Merchant Center | Feed produits complet, prix/stock/livraison cohérents avec la page. |
| Images | AVIF/WebP + fallback, alt descriptif, filename, `srcset`, dimensions, page contextuelle [3]. |
| International | `hreflang`, devises, taxes et unités si plusieurs marchés. |

Les données structurées ne doivent jamais déclarer une note, un avis ou un témoignage inexistant. Une donnée structurée n’est pas une autorisation d’inventer du contenu.

### 11.3 GEO opérationnel

Chaque catégorie et produit important doit fournir des informations réellement utiles à la compréhension : cas d’usage, public, matériaux, dimensions, compatibilités, entretien, livraison, retours et limites. Le contenu doit être organisé avec des titres clairs, des paragraphes explicatifs et des tableaux lorsque la comparaison aide réellement l’utilisateur.

Le dashboard doit guider le commerçant avec un score éditorial compréhensible, mais ce score ne doit pas devenir une métrique SEO magique. Il peut signaler : titre trop court, description absente, bénéfice non explicite, image sans alt, attributs incomplets, stock non renseigné ou absence de FAQ pertinente.

## 12. Accessibilité et conformité UX

La cible est WCAG 2.2 AA pour le storefront, le dashboard et les widgets Elementor. Les points obligatoires sont la navigation clavier, les focus rings visibles, les dialogs correctement annoncés, la gestion du focus, les labels explicites, les erreurs liées aux champs, le contraste, les zones tactiles, la réduction de mouvement, les lecteurs d’écran et les états non exprimés par la couleur seule.

Le quick view doit être utilisable au clavier. Le side cart doit être annoncé comme une région ou un dialog selon son ouverture. La barre mobile ne doit pas se comporter comme un élément fixe inaccessible. Les textes de statut d’ajout, d’erreur réseau et d’état de synchronisation doivent être annoncés avec `role=status` ou une structure équivalente.

## 13. Sécurité et confidentialité

| Risque | Contrôle exigé |
|---|---|
| Secret WooCommerce exposé | Aucun secret dans le navigateur ; stockage serveur chiffré, rotation et révocation. |
| Action non autorisée | Vérification capability côté API, serveur et interface [10]. |
| CSRF | Nonces ou tokens adaptés, SameSite, validation origin/referrer lorsque pertinent. |
| XSS | Échappement à la sortie, sanitation à l’entrée, validation de schémas. |
| Injection | Requêtes préparées, validation des IDs et paramètres, aucun SQL concaténé. |
| Bruteforce | Rate limiting, verrouillage progressif, 2FA/SSO recommandé pour marchands. |
| Paiement | Aucun stockage de carte ; délégation aux gateways PCI adaptées. |
| Webhooks | Signature, idempotence, replay contrôlé et logs sans données sensibles. |
| Médias | Validation MIME réelle, taille maximale, noms normalisés et suppression de métadonnées EXIF si politique choisie. |
| Logs | Pas de secret, token, carte ou donnée client non nécessaire. Rétention définie. |
| Headers | HTTPS, CSP progressive, HSTS selon environnement, `frame-ancestors`, `Referrer-Policy`. |

## 14. Compatibilité à garantir

La matrice de compatibilité doit être exécutée avant chaque release majeure.

| Catégorie | Matrice minimale |
|---|---|
| CMS | WordPress supporté et WooCommerce supporté selon la version de lancement. |
| Builder | Elementor Free, Elementor Pro et éditeur natif WordPress. |
| WooCommerce | Produits simples, variables, groupés, externes, virtuels, téléchargeables, abonnements si extension retenue. |
| Checkout | Cart/Checkout Blocks et checkout classique. |
| Paiement | Au moins une gateway carte, un wallet et un moyen local du marché cible. |
| Livraison | Forfait, gratuite, retrait/local pickup et calcul conditionnel si nécessaire. |
| Navigateurs | Chrome, Safari, Firefox, Edge récents ; Safari iOS et Chrome Android. |
| Écrans | 320 px, 390 px, 768 px, 1024 px, 1280 px et 1440 px. |
| Cache | LiteSpeed, cache objet, CDN et environnement sans LiteSpeed pour fallback. |
| Traduction | Text domain, chaînes traduisibles, RTL si marché concerné. |

## 15. Lots de développement

### Lot 0 — Architecture et contrats

Créer le dépôt, le thème, le plugin, la console, les conventions de code, les tokens, les environnements, les schémas API, les rôles, les logs et les tests de base. Produire un environnement de démonstration avec données non publiées et sans faux avis.

### Lot 1 — Thème storefront SSR

Implémenter header, navigation, archive, catégorie, fiche produit, panier, checkout, compte, responsive, tokens, accessibilité et fallbacks sans JS. Définir les hooks et les points d’extension.

### Lot 2 — Performance et médias

Implémenter `srcset`, `picture`, AVIF/WebP/JPEG fallback, traitement asynchrone, dimensions, lazy-loading, LCP preload, cache LiteSpeed, cache objet, purge ciblée et mesure RUM.

### Lot 3 — Conversion storefront

Implémenter product grid, filtres, recherche, quick view, ajout, side cart, barre mobile, coupons, livraison, erreurs, checkout Blocks/classique et analytics consentis.

### Lot 4 — Elementor

Implémenter les widgets P0/P1, catégories, contrôles responsive, Theme Builder, Woo Builder, preview editor/frontend, styles namespaceés, documentation et tests de régression.

### Lot 5 — Dashboard marchand

Implémenter connexion, rôles, overview, commandes, produits, variantes, stock, médias, AVIF, désactivation, bulk actions, alertes, audit logs, synchronisation et mobile UX.

### Lot 6 — SEO/GEO, hardening et release

Implémenter schema, canoniques, robots, facettes, sitemaps, Merchant Center, champs éditoriaux guidés, sécurité, monitoring, documentation, tests de charge et publication progressive.

## 16. Critères d’acceptation

### Fonctionnels

Le produit est accepté lorsque l’utilisateur peut parcourir le catalogue, utiliser la recherche et les filtres, ouvrir un quick view, sélectionner une variation, ajouter et modifier une ligne panier, appliquer un coupon, choisir une livraison et effectuer un checkout réel avec au moins une passerelle testée. Chaque erreur métier doit avoir un feedback compréhensible.

Le commerçant est accepté lorsqu’il peut se connecter avec son rôle, consulter les commandes, créer un produit simple, modifier prix/stock/image, gérer une variation, désactiver puis réactiver un produit, voir l’état AVIF et comprendre toute erreur de synchronisation depuis mobile.

### Performance

Les pages de référence doivent atteindre les budgets définis sur l’environnement de staging représentatif. Les tests doivent être exécutés en réseau mobile simulé et sur appareil réel. Les métriques terrain doivent être collectées après déploiement avec séparation mobile/desktop. Une régression de plus de 10 % sur LCP, INP, CLS, TTFB ou poids JS bloque la release jusqu’à analyse.

### SEO/GEO

Les pages produit principales doivent être accessibles sans JS, valides dans les outils de données structurées lorsque le markup est applicable, cohérentes avec le contenu visible et sans erreurs critiques Merchant Center. Les facettes ne doivent pas créer de duplication incontrôlée. Chaque image produit doit avoir une URL stable, un fallback, des dimensions et un alt pertinent.

### Accessibilité

Un audit automatisé ne doit pas présenter de violation critique ou sérieuse non justifiée. Les parcours catalogue → quick view → ajout → panier → checkout doivent être testés au clavier et avec un lecteur d’écran. Les contrastes, focus, erreurs de formulaire et mouvements réduits doivent être documentés.

### Sécurité

Les secrets ne doivent apparaître ni dans le bundle frontend, ni dans les logs, ni dans le HTML. Les endpoints externes doivent vérifier authentication, capability, nonce/token, validation d’entrée et rate limit. Une analyse de dépendances et un test d’intrusion applicatif doivent être réalisés avant production.

## 17. QA et observabilité

La qualité doit être testée par couches : unit tests pour les calculs et transformations, tests d’intégration pour les routes WooCommerce, tests contractuels pour Store API/REST, tests E2E Playwright pour les parcours, tests visuels responsive et tests de performance.

Le monitoring doit suivre erreurs JavaScript, erreurs API, temps de réponse, échec de synchronisation, traitement AVIF bloqué, checkout abandonné, erreurs de paiement et régressions de CWV. Les événements analytics doivent respecter le consentement, minimiser les données et éviter les identifiants personnels inutiles.

## 18. Risques et décisions à valider avant développement

| Risque | Décision recommandée |
|---|---|
| Score 100 impossible partout | Contractualiser des seuils p75 et budgets contrôlés, pas une promesse absolue. |
| AVIF dépendant d’un quota | Prévoir pipeline choisie, fallback WebP/JPEG et statut de traitement. |
| Extensions incompatibles avec Checkout Blocks | Supporter Blocks et classique, avec matrice de compatibilité. |
| Dashboard externe trop large | Limiter le MVP aux commandes, produits, stock, médias et alertes. |
| Cache et panier personnalisé | Store API/ESI, exclusions de cache et tests de stock. |
| Elementor surcharge le frontend | Rendu SSR, assets conditionnels et widgets métier ciblés. |
| Sécurité API | BFF côté serveur, secrets chiffrés, rôles, rotation et audit logs. |
| Données produit pauvres | Score éditorial guidé et champs obligatoires selon type de produit. |

## 19. Découpage MVP recommandé

Le MVP doit livrer un thème rapide avec archive, catégorie, produit, panier, checkout compatible, quick view, side cart, barre mobile, AVIF/WebP, SEO de base et une console avec commandes, produits, stock, médias et désactivation. Les fonctions wishlist, compare, multi-entrepôt, abonnements complexes, marketplace multi-vendeurs et analytics avancés doivent être planifiées après validation du cœur d’achat.

Cette discipline est essentielle : la valeur de Keleva Woo vient d’une expérience complète et fiable, pas de l’empilement de fonctionnalités visibles dans une démo. Le dashboard doit être simple à utiliser, mais il ne doit pas simplifier au point de masquer les conséquences d’une modification de stock, prix, statut ou commande.

## 20. Références

[1]: https://developers.google.com/search/docs/appearance/core-web-vitals "Google Search Central — Understanding Core Web Vitals and Google search results"
[2]: https://developers.google.com/search/docs/appearance/page-experience "Google Search Central — Understanding page experience in Google Search results"
[3]: https://developers.google.com/search/docs/appearance/google-images "Google Search Central — Google image SEO best practices"
[4]: https://docs.litespeedtech.com/lscache/lscwp/imageopt/ "LiteSpeed Cache for WordPress — Image Optimization"
[5]: https://docs.quic.cloud/services/imageopt/ "QUIC.cloud — Optimizing Images"
[6]: https://blog.litespeedtech.com/2017/05/24/wpw-using-lscache-with-woocommerce/ "LiteSpeed — LSCache + WooCommerce"
[7]: https://developer.wordpress.org/advanced-administration/security/application-passwords/ "WordPress Developer — Application Passwords"
[8]: https://developer.woocommerce.com/docs/apis/rest-api/ "WooCommerce Developer — REST API"
[9]: https://developer.woocommerce.com/docs/apis/rest-api/authentication/ "WooCommerce Developer — REST API Authentication"
[10]: https://developer.wordpress.org/apis/security/user-roles-and-capabilities/ "WordPress Developer — User Roles and Capabilities"
[11]: https://developer.woocommerce.com/docs/apis/store-api/resources-endpoints/cart/ "WooCommerce Developer — Cart API"
[12]: https://woocommerce.com/document/woocommerce-store-editing/customizing-cart-and-checkout/ "WooCommerce — Customizing Cart and Checkout"
