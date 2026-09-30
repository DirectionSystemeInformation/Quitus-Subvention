# Harmonisation des formulaires — 29 septembre 2026

## Direction visuelle appliquée

Une présentation institutionnelle contemporaine : vert profond, fonds légèrement teintés, cartes blanches, bordures discrètes et espaces réguliers. Les formulaires longs sont divisés en sections explicites ; les formulaires courts conservent une saisie directe.

## Périmètre

- Activité : identification, financement et réalisation, justificatifs ; aide latérale et actions de brouillon et de transmission.
- Programme annuel : en-tête, récapitulatif, axes, lignes d'activité et actions.
- Campagne : bloc de création avec année et aide contextuelle.
- Comptes agents : formulaire dédié, sorti du pied de tableau, avec libellés visibles.
- Canevas : création d'axes et de sous-axes.
- Pondération : création de rubriques, critères et paliers.
- Inscription d'une fédération : deux groupes, identité de la fédération puis accès au compte.

Les règles métier et les droits d'accès sont conservés. Les valeurs précédemment saisies et les erreurs des formulaires d'administration sont rattachées au formulaire de création concerné.

## Base réutilisable

- `public/css/creation-forms.css` : styles communs limités aux conteneurs `.creation-ui`.
- `resources/views/components/creation-heading.blade.php` : titre, description et contexte.
- `resources/views/components/form-section.blade.php` : carte de section avec repère et description.

Pour un nouveau formulaire, utiliser ces composants, `.creation-grid` pour les champs, des libellés liés aux identifiants, `.creation-hint` pour les aides et `.creation-actions` pour la validation. Les grilles passent en une colonne sur petit écran ; les états de focus et d'erreur restent visibles.

## Vérifications

Vérifications réalisées avec des données fictives dans une base SQLite temporaire, sans modification de la configuration ni de la base métier.

- Inspection visuelle sur ordinateur et à 390 px : activité, programme, administration et inscription.
- Enregistrement d'un brouillon d'activité et d'un programme ; calcul du récapitulatif du programme.
- Erreur de validation du code d'un axe : restitution de la saisie et erreur sur le bon champ.
- Compilation des vues Blade, vérification de syntaxe JavaScript et contrôle des différences : réussis.
- Suite `DossierWorkflowTest` : 11 tests réussis sur 13. Les deux échecs ont aussi été reproduits avec l'ancienne vue partagée : rapport brouillon renvoyant 404 au lieu de 403, et attente d'un dossier sans justificatifs dans la file DGF filtrée sur les dossiers avec justificatifs. Ces points préexistants restent à traiter séparément.

L'envoi réel d'un dossier, la création effective de comptes et le téléversement de fichiers n'ont pas été exercés dans cette vérification visuelle.

## Aperçus

- [Activité sur ordinateur](previews/formulaire-activite-desktop.png)
- [Création d'un compte agent](previews/formulaire-compte-desktop.png)
- [Inscription sur mobile](previews/formulaire-inscription-mobile.png)

Les autres directions d'amélioration sont détaillées dans l'[audit UI/UX](audit-ui-ux-2026-09-29.md).
