# Interface métier Quitus — 29 septembre 2026

## Ce qui a changé

La plateforme dispose d'un cadre visuel propre : navigation organisée par rôle, identité Quitus, recherche générale, profil et déconnexion regroupés. Le suivi existant du dossier fédération est conservé dans ce nouveau cadre.

L'espace DSHN / administrateur met en avant un dossier à examiner et les files de travail. Les documents affichent leur type réel, y compris les programmes. Les indicateurs conduisent aux écrans concernés.

Les listes des rapports et programmes et des activités DGF disposent de filtres persistants, d'une recherche sur l'ensemble des résultats et d'une pagination. La file des documents s'ouvre sur les éléments soumis. Sur téléphone, les lignes deviennent des cartes dont l'action est directement accessible.

Les fiches des documents regroupent le contenu, les totaux, les autres documents de la même campagne, la décision et les dates connues. Les brouillons restent privés à leur fédération. Le rapport N et les programmes N+1 sont rapprochés ; les documents d'une autre fédération ne sont jamais inclus.

La fiche DGF rassemble informations, observations, justificatifs et décision. L'absence de justificatif explique l'indisponibilité de la validation. Un document validé ne présente plus de bouton de rejet inapplicable. La validation d'un document demande une confirmation avec son effet explicite.

## Composants communs

- `PlatformNavigation` : groupes de navigation et intitulés par rôle.
- `platform.css` : fondations visuelles, cadre général, boutons, cartes, tableaux, filtres et fiches.
- `page-heading`, `action-card`, `record-history`, `ui-icon` : composants Blade réutilisables.
- `empty-state` : état vide avec explication et emplacement pour une action.
- Les composants de création précédents continuent d'être utilisés.

Le cadre de navigation n'utilise plus les classes de mise en page du template. Les anciens fichiers CSS restent chargés pour les écrans spécialisés qui en dépendent encore. Leur suppression complète doit suivre la migration de ces écrans ; cette livraison ne prétend pas avoir éliminé toute dépendance visuelle historique.

## Vérifications

Les essais ont été effectués avec des comptes et des données fictifs dans une base SQLite temporaire. La base métier et le fichier `.env` n'ont pas été modifiés.

- Six nouveaux tests, 75 assertions : navigation des sept rôles, filtrage des documents, recherche au-delà de la première page, persistance des filtres, exclusion des brouillons, documents associés et recherche DGF.
- Suite combinée : 17 tests réussis sur 19, 139 assertions. Les deux échecs préexistants restent inchangés : attente d'un 403 au lieu d'un 404 pour un brouillon et attente d'une activité sans pièce dans la file filtrée avec justificatifs.
- Vérification dans le navigateur : tableau de bord administrateur, dossier fédération, fiche de programme, file des documents, recherche, fiche DGF sans pièce et confirmation de validation annulée.
- Contrôle à 390 px : absence de débordement de page, filtres utilisables, actions accessibles dans les cartes et menu mobile. Le menu gère le focus, ferme avec Échap et rend le contenu sous-jacent inactif lorsqu'il est ouvert.
- Compilation Blade, syntaxe JavaScript et contrôle des différences.

Les téléchargements de pièces et les décisions finales n'ont pas été exercés dans cette vérification visuelle. Les règles de validation et les autorisations existantes sont conservées.

## Aperçus avec données fictives

- [Tableau de bord](previews/plateforme-tableau-de-bord.png)
- [Fiche de dossier](previews/fiche-dossier-desktop.png)
- [Liste sur mobile](previews/liste-dossiers-mobile.png)
