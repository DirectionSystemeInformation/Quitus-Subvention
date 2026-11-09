<?php

/*
 * Configuration fonctionnelle de la plateforme.
 * Ajouter un module ou un widget ici suffit à le rendre disponible dans les rôles et le tableau de bord.
 */
return [

    // Modules => actions disponibles (chaque couple génère une permission "module.action")
    'modules' => [
        'talents' => ['label' => 'Talents (identification)', 'actions' => ['voir', 'creer', 'modifier', 'supprimer', 'valider', 'exporter']],
        'licences' => ['label' => 'Licences', 'actions' => ['voir', 'creer', 'modifier', 'supprimer', 'verifier']],
        'competitions' => ['label' => 'Compétitions', 'actions' => ['voir', 'creer', 'modifier', 'supprimer']],
        'performances' => ['label' => 'Performances', 'actions' => ['voir', 'creer', 'modifier', 'supprimer', 'exporter']],
        'evaluations' => ['label' => 'Évaluations physiques', 'actions' => ['voir', 'creer', 'modifier', 'supprimer']],
        'medical' => ['label' => 'Suivi médical (confidentiel)', 'actions' => ['voir', 'creer', 'modifier', 'supprimer']],
        'parcours' => ['label' => 'Parcours & carrière', 'actions' => ['voir', 'creer', 'modifier', 'supprimer']],
        'bourses' => ['label' => 'Bourses & appuis', 'actions' => ['voir', 'creer', 'modifier', 'supprimer', 'decider']],
        'reconversions' => ['label' => 'Retraite & reconversion', 'actions' => ['voir', 'creer', 'modifier', 'supprimer']],
        'entraineurs' => ['label' => 'Encadrement technique', 'actions' => ['voir', 'creer', 'modifier', 'supprimer']],
        'federations' => ['label' => 'Fédérations (réglages talents)', 'actions' => ['voir', 'modifier']],
        'disciplines' => ['label' => 'Disciplines & spécialités', 'actions' => ['voir', 'gerer']],
        'referentiels' => ['label' => 'Listes de référence', 'actions' => ['gerer']],
        'utilisateurs' => ['label' => 'Utilisateurs', 'actions' => ['gerer']],
        'roles' => ['label' => 'Rôles & permissions', 'actions' => ['gerer']],
        'rapports' => ['label' => 'Rapports & statistiques', 'actions' => ['voir']],
        'journal' => ['label' => 'Journal d\'activité', 'actions' => ['voir']],
        'acces' => ['label' => 'Périmètre', 'actions' => ['national']],
    ],

    // Rôle talents attribué automatiquement à chaque type de compte Quitus (users.role).
    // Les comptes complémentaires (role = talents) reçoivent leurs rôles depuis le portail talents.
    // Un rôle Quitus absent de cette liste (ex. dgf) n'a pas accès au portail talents.
    'correspondance_roles' => [
        'admin' => 'Super administrateur',
        'dshn' => 'Ministère des Sports',
        'federation' => 'Fédération',
        'ministre' => 'Observateur national',
        'dg' => 'Observateur national',
        'comite_arbitrage' => 'Observateur national',
    ],

    // Menu latéral : chaque entrée n'apparaît que si l'utilisateur a la permission.
    'menu' => [
        ['titre' => null, 'liens' => [
            ['Tableau de bord', 'suivi.dashboard', 'bi-grid-1x2', null],
        ]],
        ['titre' => 'Athlètes', 'liens' => [
            ['Talents', 'suivi.talents.index', 'bi-people', 'talents.voir'],
            ['Validation des profils', 'suivi.talents.validations', 'bi-patch-check', 'talents.valider'],
            ['Licences', 'suivi.licences.index', 'bi-card-heading', 'licences.voir'],
            ['Disciplines & spécialités', 'suivi.disciplines.index', 'bi-diagram-3', 'disciplines.voir'],
        ]],
        ['titre' => 'Performance', 'liens' => [
            ['Compétitions', 'suivi.competitions.index', 'bi-calendar-event', 'competitions.voir'],
            ['Performances', 'suivi.performances.index', 'bi-trophy', 'performances.voir'],
            ['Évaluations physiques', 'suivi.evaluations.index', 'bi-speedometer2', 'evaluations.voir'],
        ]],
        ['titre' => 'Santé', 'liens' => [
            ['Suivi médical', 'suivi.medical.index', 'bi-heart-pulse', 'medical.voir'],
        ]],
        ['titre' => 'Carrière', 'liens' => [
            ['Parcours & clubs', 'suivi.parcours.index', 'bi-signpost-split', 'parcours.voir'],
            ['Bourses & appuis', 'suivi.bourses.index', 'bi-cash-coin', 'bourses.voir'],
            ['Retraite & reconversion', 'suivi.reconversions.index', 'bi-mortarboard', 'reconversions.voir'],
            ['Entraîneurs', 'suivi.entraineurs.index', 'bi-person-badge', 'entraineurs.voir'],
        ]],
        ['titre' => 'Pilotage', 'liens' => [
            ['Rapports & exports', 'suivi.rapports.index', 'bi-bar-chart-line', 'rapports.voir'],
            ['Journal d\'activité', 'suivi.journal.index', 'bi-clock-history', 'journal.voir'],
        ]],
        ['titre' => 'Administration', 'liens' => [
            ['Fédérations', 'suivi.federations.index', 'bi-bank', 'federations.voir'],
            ['Listes de référence', 'suivi.referentiels.index', 'bi-list-check', 'referentiels.gerer'],
            ['Utilisateurs', 'suivi.utilisateurs.index', 'bi-person-gear', 'utilisateurs.gerer'],
            ['Rôles & permissions', 'suivi.roles.index', 'bi-shield-lock', 'roles.gerer'],
        ]],
    ],

    // Widgets du tableau de bord : chacun est indépendant, protégé par une permission, masquable par l'utilisateur.
    'widgets' => [
        'kpi_talents' => ['titre' => 'Chiffres clés des talents', 'permission' => 'talents.voir', 'largeur' => 12],
        'validation_attente' => ['titre' => 'Profils en attente de validation', 'permission' => 'talents.valider', 'largeur' => 6],
        'repartition_discipline' => ['titre' => 'Talents par discipline', 'permission' => 'talents.voir', 'largeur' => 6],
        'repartition_genre' => ['titre' => 'Répartition par genre', 'permission' => 'talents.voir', 'largeur' => 4],
        'categories_age' => ['titre' => 'Catégories d\'âge', 'permission' => 'talents.voir', 'largeur' => 4],
        'statut_carriere' => ['titre' => 'Statut de carrière', 'permission' => 'talents.voir', 'largeur' => 4],
        'repartition_federation' => ['titre' => 'Talents par fédération', 'permission' => 'acces.national', 'largeur' => 6],
        'diaspora' => ['titre' => 'Talents évoluant à l\'étranger', 'permission' => 'licences.voir', 'largeur' => 6],
        'licences' => ['titre' => 'Licences : types & échéances', 'permission' => 'licences.voir', 'largeur' => 6],
        'medailles' => ['titre' => 'Médailles par niveau de compétition', 'permission' => 'performances.voir', 'largeur' => 6],
        'dernieres_performances' => ['titre' => 'Dernières performances', 'permission' => 'performances.voir', 'largeur' => 12],
        'alertes_medicales' => ['titre' => 'Alertes médicales (indisponibles)', 'permission' => 'medical.voir', 'largeur' => 6],
        'evolution_physique' => ['titre' => 'Moyennes des tests physiques', 'permission' => 'evaluations.voir', 'largeur' => 6],
        'bourses' => ['titre' => 'Bourses & appuis de l\'État', 'permission' => 'bourses.voir', 'largeur' => 6],
        'reconversion' => ['titre' => 'Suivi de la reconversion', 'permission' => 'reconversions.voir', 'largeur' => 6],
        'activite_recente' => ['titre' => 'Activité récente', 'permission' => 'journal.voir', 'largeur' => 12],
    ],
];
