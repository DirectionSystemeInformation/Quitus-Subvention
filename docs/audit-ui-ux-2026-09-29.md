# Audit UI/UX — Quitus et subventions

Date : 29 septembre 2026.

## Avis général

La plateforme dispose d'une base visuelle sobre et d'un parcours fédération déjà bien structuré. La priorité est de fiabiliser les interactions, de rendre les attentes administratives compréhensibles et de faciliter le travail des agents. Une refonte complète de l'identité visuelle apporterait moins de valeur à court terme que ces améliorations.

Direction recommandée : **un dossier guidé pour les fédérations, un espace de traitement pour les agents, une synthèse de décision pour les responsables**. Ces trois expériences peuvent partager les mêmes composants et les mêmes données.

## Périmètre et limites

- Lecture des routes, vues, contrôleurs, styles et interactions JavaScript.
- Consultation réelle dans le navigateur : accueil, connexion, dossier fédération, programme budgétisé, création d'activité, tableau de bord administrateur, campagnes, pondération et file des activités DGF accessible à l'administrateur.
- Examen de la création d'activité sur un écran de 390 × 844 pixels ; aperçu du programme à cette largeur. Ce contrôle partiel ne constitue pas une recette mobile exhaustive.
- Aperçu local avec une base SQLite temporaire et des comptes fictifs. La base métier MySQL n'a pas été modifiée. Le schéma de démonstration reprend les migrations utiles ; il ne sert pas à conclure sur les performances ou l'intégrité de la base de production.
- Les interfaces DG, Comité et Ministre ont été examinées dans le code partagé, sans parcours connecté complet pour chacun de ces rôles.
- Aucun entretien utilisateur, audit complet de conformité, mesure de performance réseau ou test d'envoi réel de notification. Les difficultés d'usage anticipées restent à confronter au terrain.
- Aucun changement fonctionnel effectué : ce document constitue le livrable de l'audit.

## Les acquis à conserver

1. **Le dossier fédération donne une prochaine action.** Il réunit campagne, années du rapport et du programme, progression, documents et dernier événement. Il existe déjà une base solide pour guider l'utilisateur.
2. **Le programme possède un vrai mode brouillon**, un récapitulatif avant soumission, des totaux, des rubriques repliables, l'annulation du retrait d'une ligne et une alerte de sortie sans enregistrement.
3. **La file DGF distingue les activités avec et sans justificatif.** C'est une distinction métier utile, à rendre cohérente dans tous les écrans.
4. **Plusieurs dispositifs d'accessibilité sont déjà présents** : lien d'évitement, focus visible, erreurs serveur liées aux champs, libellés des scores et réduction de certaines animations.
5. **La base graphique est appropriée** : fond clair, vert institutionnel, cartes blanches, typographie lisible, états accompagnés de texte. Les surcharges CSS récentes neutralisent déjà plusieurs effets décoratifs du thème d'origine.

## Constats prioritaires

P1 : impact fort sur la tâche ou obstacle d'accès. P2 : friction importante et amélioration structurante. P3 : optimisation. Les efforts indiqués sont relatifs, pas des estimations contractuelles.

### 1. L'année du dossier se perd à la création d'une activité — P1

**Confirmé dans le navigateur et le code.** L'ouverture de `/activites/nouvelle?annee=2025` affiche « Année d'exercice 2026 ». Le contrôleur impose l'année courante et l'écran présente l'année en lecture seule. Le bouton « Nouvelle activité » ne transmet pas non plus le filtre annuel de la liste.

**Conséquence :** une fédération qui régularise une ancienne campagne risque d'enregistrer ses activités dans le mauvais exercice, sans pouvoir corriger cette année depuis le formulaire.

**Proposition :** propager le contexte annuel sur tous les liens, afficher « Activités réalisées en 2025 — Dossier 2026 » et permettre le choix parmi les exercices autorisés par les règles métier.

**Réussite attendue :** partir d'une campagne antérieure, créer une activité et revenir à la liste conserve le même exercice. Effort : faible à moyen.

Sources : `app/Http/Controllers/ActivityController.php:32`, `resources/views/activities/index.blade.php`, `resources/views/activities/create.blade.php`.

### 2. Le dépôt de pièces comporte un obstacle clavier — P1

**Confirmé dans le DOM rendu.** Le champ fichier de la zone de dépôt est en `display: none` ; le label qui le remplace n'est pas focusable. L'arbre d'accessibilité présente l'instruction de dépôt mais pas un contrôle fichier utilisable au clavier.

**Proposition :** conserver un véritable champ fichier accessible ou proposer un bouton natif « Ajouter des justificatifs », associé au sélecteur. Afficher le focus, la liste des fichiers choisis et les erreurs de format ou de taille.

**Réussite attendue :** ouvrir le sélecteur, choisir un fichier et retirer ce fichier sans souris. Effort : faible.

Sources : `public/css/app-enhancements.css:485`, `resources/views/activities/create.blade.php`.

### 3. La promesse de verrouillage après soumission est contradictoire — P1

**Confirmé par lecture croisée.** Le formulaire annonce que les informations sont verrouillées après soumission. Pourtant, la fiche conserve « Modifier » tant que l'activité n'est pas validée, et les actions `edit` et `update` autorisent une activité soumise.

**Conséquence :** fédération et agent peuvent avoir des attentes différentes sur la version en cours d'examen.

**Proposition :** fixer une règle métier commune. Si le dossier reste modifiable, l'annoncer et enregistrer une nouvelle version avec date de modification ; si le dossier est verrouillé, appliquer ce verrouillage dans l'interface et côté serveur. Un ajout de justificatif doit rester clairement distingué d'une modification des données.

**Réussite attendue :** chaque statut possède les mêmes droits annoncés, affichés et appliqués. Effort : moyen.

Sources : `resources/views/activities/create.blade.php`, `resources/views/activities/show.blade.php`, `app/Http/Controllers/ActivityController.php:73`.

### 4. La pondération impose une saisie trop étendue — P1

**Mesuré dans le navigateur à 1280 px.** La table fait environ **3 333 px**, pour **867 px visibles**, avec **23 champs de score par fédération**. Les champs mesurent environ **52 × 19 px**. La première colonne fixe et les intitulés dépliables sont utiles, mais ne suffisent pas à rendre cette saisie confortable.

**Proposition :** une vue de saisie par fédération, divisée en sept rubriques, avec intitulé complet, plafond, justification et sous-total visibles. Garder la grande matrice comme vue comparative. Ajouter un indicateur « 15 critères renseignés sur 23 » qui distingue un score nul d'un critère non évalué.

**Réussite attendue :** noter une rubrique sans déplacement horizontal ; conserver le total et l'état d'enregistrement visibles. Effort : moyen à élevé.

Sources : `resources/views/campaign/show.blade.php:75`, `database/migrations/2026_09_17_100000_create_ponderation_tables.php`.

### 5. La protection de la saisie est inégale — P1

**Constat dans le code.** Le programme avertit avant de quitter une saisie modifiée. Aucun dispositif équivalent n'a été trouvé pour la création d'activité ou la saisie des scores. Le programme n'effectue pas non plus de sauvegarde automatique : son alerte protège contre certaines sorties, pas contre une interruption imprévue.

**Proposition :** généraliser d'abord l'avertissement de sortie. Puis ajouter un enregistrement automatique serveur des brouillons, avec « Enregistré à 14:32 », « Enregistrement en cours » et « Échec — Réessayer ». Ne jamais afficher une réussite avant confirmation du serveur.

**Réussite attendue :** après interruption, retrouver la dernière version effectivement enregistrée ; rester informé des modifications encore locales. Effort : faible pour l'alerte, élevé pour une reprise robuste.

Sources : `public/js/programme-form.js:100`, `resources/views/activities/create.blade.php`, `resources/views/campaign/show.blade.php`.

### 6. Les indicateurs ne reflètent pas toujours les tâches réellement traitables — P2

**Confirmé avec la même donnée fictive.** Le tableau de bord admin affiche une activité « à vérifier », alors que la file DGF affiche « À vérifier (0) » et « En attente de justificatif (1) ». Le tableau des actions de l'admin inclut également cette activité incomplète. Dans l'onglet sans justificatif, le statut de ligne reste « À vérifier ».

**Proposition :** partager une définition commune des compteurs et libellés : « Prêts à examiner », « Pièces attendues », « Corrections demandées ». Rendre chaque compteur cliquable vers le filtre correspondant.

**Réussite attendue :** le nombre annoncé et le nombre de résultats correspondent, et chaque élément précise qui doit agir. Effort : faible à moyen.

Sources : `app/Http/Controllers/Dshn/DashboardController.php`, `resources/views/dgf/activities/index.blade.php`.

### 7. Le tableau de bord agent met le suivi avant le travail — P2

**Observation visuelle.** Les cartes statistiques puis les diagrammes occupent le premier écran ; la liste « Éléments nécessitant une action » vient ensuite. Les compteurs sont des blocs non cliquables. La navigation admin nécessite aussi un défilement à la hauteur observée.

**Proposition :** afficher d'abord une file priorisée avec type, fédération, ancienneté, blocage et action précise. Réduire les indicateurs à une ligne ; déplacer les graphiques dans une vue « Suivi ». Regrouper les menus en « Traitement », « Campagnes » et « Administration » ; placer Canevas et Barème dans les paramètres.

**Réussite attendue :** atteindre un dossier prêt à traiter depuis le premier écran en une action. Effort : moyen.

Source : `resources/views/dshn/dashboard.blade.php:68`, `resources/views/layouts/dashboard.blade.php`.

### 8. Le tri des tableaux n'est pas accessible au clavier — P2

**Confirmé dans le DOM et le script.** Les en-têtes triables ont `tabIndex: -1`, sans bouton ni `aria-sort`. Le tri est relié au clic sur l'en-tête. Les groupes repliables de tableaux utilisent un mécanisme similaire à examiner.

**Proposition :** placer un bouton dans chaque en-tête triable, annoncer le sens du tri et conserver le focus après réorganisation.

**Réussite attendue :** trier par date au clavier, puis identifier le sens du tri avec une technologie d'assistance. Effort : faible à moyen.

Sources : `public/js/templatemo-crypto-script.js:683`, `resources/views/dgf/activities/index.blade.php`.

### 9. L'attente d'activation manque d'issue explicite — P2

**Constat dans le code.** La connexion d'un compte en attente promet une notification après activation. Les actions d'activation examinées enregistrent un changement de statut et un historique ; aucun envoi de notification d'activation n'y a été trouvé. La notification de réinitialisation de mot de passe existe. Une éventuelle intégration externe reste à vérifier.

**Proposition :** fournir une page de suivi de demande, la date de dépôt et un contact d'assistance validé par le ministère ; mettre en place et tester l'avis d'activation avant de le promettre. Afficher un délai seulement si le service s'engage réellement dessus.

**Réussite attendue :** une fédération sait comment suivre son activation et reçoit l'avis annoncé. Effort : moyen.

Sources : `app/Http/Controllers/Auth/AuthController.php:40`, `app/Http/Controllers/Dshn/FederationController.php:43`.

### 10. Les termes et repères de progression peuvent être unifiés — P2

**Constats.** Le menu dit « Tableau de bord », l'écran « Mon dossier ». « Rapport & Programme » devient « Mes documents » dans le fil d'Ariane. Le rapport automatique absent est nommé « Non déposé », ce qui peut suggérer un dépôt manuel. Le circuit campagne annonce douze étapes mais ne représente que les étapes 3 à 12.

**Proposition :** vocabulaire stable : « Mon dossier », « Activités réalisées », « Programme prévisionnel », « Documents ». Pour le rapport : « En attente d'activités validées ». Expliquer les étapes 1 et 2 ou afficher cinq phases lisibles, avec les numéros administratifs dans le détail. Distinguer le paramétrage du barème de la notation d'une campagne.

**Réussite attendue :** retrouver la même tâche sous le même nom et comprendre l'étape courante sans connaître le circuit interne. Effort : faible à moyen.

### 11. L'accueil gagnerait à devenir plus pratique — P3

**Observation.** Le grand visuel sportif et les appels à créer un compte dominent le premier écran. Le parcours est expliqué plus bas, mais il manque un accès visible à l'aide, aux pièces à préparer et aux modalités de suivi d'une demande.

**Proposition :** raccourcir le bandeau, mettre « Accéder à mon dossier » au premier plan pour les visiteurs récurrents, ajouter une courte liste de prérequis et une aide. Préférer un visuel multisport représentatif du public ; éviter une rotation automatique sans commande de pause. Vérifier les informations de campagne avant de les publier.

**Réussite attendue :** un nouvel utilisateur identifie les documents nécessaires et un utilisateur récurrent accède immédiatement à son espace. Effort : faible à moyen.

## Trois directions de produit

| Direction | Expérience proposée | Bénéfice | Compromis |
|---|---|---|---|
| **A — Dossier guidé** | Une prochaine action, liste de pièces, cinq phases, historique des décisions, assistance contextuelle | Réduit l'incertitude des fédérations ; prolonge les acquis actuels | Demande un modèle précis des blocages et responsabilités |
| **B — Bureau de traitement** | File priorisée, dossier et justificatifs côte à côte, commentaire ciblé, décision puis dossier suivant | Limite les allers-retours des agents DSHN/DGF | À adapter sur mobile en vues successives |
| **C — Pilotage des campagnes** | Vue par phase, exceptions à résoudre, synthèse des montants et comparaison proposé/arbitré/final | Aide les responsables à décider et repérer les blocages | Dépend de données fiables et d'indicateurs métier définis |

**Choix recommandé : A pour les fédérations et B pour les agents.** Introduire C ensuite pour DG, Comité et Ministre. Ce sont des vues adaptées aux rôles, compatibles avec une identité graphique commune.

## Direction UI commune

- Conserver le vert institutionnel, les surfaces blanches et un fond gris clair ; réserver l'or aux accents et le rouge aux erreurs ou décisions négatives.
- Rendre l'action principale évidente, avec des verbes concrets : « Examiner le rapport », « Ajouter un justificatif », « Renvoyer pour correction ».
- Prévoir deux densités : confortable pour la saisie, compacte pour les listes et comparaisons. Ne pas réduire les champs de saisie au profit du nombre de colonnes visibles.
- Utiliser des chiffres alignés et des formats FCFA cohérents. Expliquer la différence entre coût total et contribution des partenaires.
- Présenter les états avec un libellé et une icône, pas uniquement une couleur ; distinguer « Reçu », « À compléter », « En examen », « Validé ».
- Sur mobile, afficher un résumé et l'action utile dans les listes, avec les détails à l'ouverture. Tester les longs intitulés et le clavier virtuel, pas seulement la largeur de page.
- Centraliser les composants et les règles de style pour éviter les surcharges contradictoires. Le nom des fichiers du thème d'origine ne justifie pas à lui seul une migration technique.

## Ordre de réalisation proposé

| Lot | Contenu | Validation |
|---|---|---|
| **1 — Fiabilité et accès** | Année conservée, dépôt clavier, droits après soumission, compteurs cohérents, avertissement de sortie | Parcours anciens exercices, tâche au clavier, cohérence des statuts |
| **2 — Travail quotidien** | File agent en premier, compteurs cliquables, notation par rubrique, vocabulaire, suivi d'activation | Scénarios complets avec fédérations et agents |
| **3 — Évolutions** | Sauvegarde automatique robuste, historique des versions, espace de décision, reprise d'un programme antérieur avec vérification | Tests d'interruption, traçabilité et contrôle des données reportées |

Éviter de copier automatiquement dates et montants d'une année antérieure sans revue : la duplication doit créer un brouillon explicitement à vérifier.

## Validation avec les utilisateurs

Organiser une première session avec 3 à 5 représentants de fédérations et 2 à 3 agents, puis vérifier les décisions avec au moins un acteur du circuit d'arbitrage. Cet échantillon exploratoire sert à trouver des obstacles, pas à produire des statistiques représentatives.

Scénarios : préparer un ancien exercice ; reprendre un brouillon ; soumettre une activité sans pièce puis la compléter ; comprendre une correction ; noter une fédération ; retrouver le quitus ; accomplir le dépôt sans souris.

Mesurer d'abord la situation actuelle, puis comparer : réussite sans assistance, erreurs d'exercice, temps de traitement d'un dossier, retours pour pièce manquante, pertes de saisie et demandes d'aide. Aucun gain chiffré ne peut être affirmé avant ces mesures.

Les prochains contrôles d'accessibilité doivent couvrir clavier, libellés, focus, zoom, contrastes et petits écrans. Les tableaux bidimensionnels peuvent nécessiter un défilement horizontal ; celui-ci n'est pas à lui seul une preuve de non-conformité. Références : [WCAG 2.2 — W3C](https://www.w3.org/TR/WCAG22/) et [guide de vérification WAI](https://www.w3.org/WAI/WCAG22/quickref/). Le présent audit ne certifie pas la conformité WCAG.
