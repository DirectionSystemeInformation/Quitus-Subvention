# Audit UI/UX — complément du 30 septembre 2026 (état intermédiaire)

Complète `audit-ui-ux-2026-09-29.md`, qui couvre les parcours et écarte volontairement la question du template. Ce document instruit cette question, mesures à l'appui, sur l'état **actuel** du code (y compris la refonte non commitée : `platform.css`, `PlatformNavigation`, composants Blade).

**Statut : collecte terminée, rapport illustré non rédigé.** Captures (23 écrans desktop, 8 mobiles, 7 rôles) et mesures brutes : dossier scratchpad de la session, `captures/` et `captures/mesures.json`.

## Mesures

### Dette CSS (9 fichiers, 152 Ko, 1 283 règles)
- **279 couleurs hexadécimales distinctes** pour une interface qui en nécessite une vingtaine. Au moins 7 verts « primaires » (#047857, #08664f, #065f46, #173c34, #087657, #064e3b, #129850…).
- **Trois couches superposées** : template d'origine (`templatemo-crypto-*`, 4 fichiers), accrétion (`app-enhancements.css`, 40 Ko, 0 variable), nouvelle base (`platform.css` + `creation-forms.css`). Chaque page connectée charge 6 à 7 feuilles.
- 31 sélecteurs redéfinis entre fichiers (`.card`, `.btn`, `.form-input`, `.form-select` dans 3 fichiers, `.modal-box`…).
- 88 attributs `style=""` dans 23 vues ; dans le DOM rendu : 286 sur la pondération, 119 sur le canevas, 90 sur les comptes.
- La connexion et l'accueil ne chargent pas `platform.css` : ils ne partagent pas la base visuelle de l'espace connecté.
- Tailwind v4 et Vite sont installés (`package.json`, `vite.config.js`) mais inutilisés.

### Accessibilité (axe-core, WCAG 2.1/2.2 A-AA)
| Règle | Gravité | Portée | Cause |
|---|---|---|---|
| color-contrast | sérieuse | **21 pages / 23, 263 éléments** | Une douzaine de gris-verts de texte secondaire, ratio 2,5 à 4,47 au lieu de 4,5 (ex. `.platform-nav-label` #738178 sur blanc : 4,08 ; en-têtes de tableau #96a299 : 2,48) |
| select-name | critique | 3 pages, 9 éléments | Filtres de tableau `.js-table-status-filter` sans libellé |
| label | critique | 2 pages, 46 éléments | Champs d'édition en ligne du canevas et des comptes sans libellé |
| aria-prohibited-attr | sérieuse | 1 page | Pagination de l'historique |

Accueil et connexion : aucune violation. Langue `fr` déclarée partout. Aucun débordement horizontal à 390 px.

### Poids
Accueil 1 120 Ko (visuel), pages connectées ~290 Ko, fiches avec aperçu PDF ~600 Ko (pdf.js). Aucun versionnage des assets : un navigateur peut conserver un ancien JS/CSS après mise en production.

## Constat de fond
Le problème n'est pas le nom du template mais **l'absence de système** : pas de jetons de couleur partagés, donc chaque écran fabrique ses propres teintes — d'où les 279 couleurs et le contraste défaillant sur 263 éléments. La refonte en cours va dans le bon sens mais ajoute une troisième couche au lieu de remplacer les deux autres.

## Options de template

| Option | Principe | + | − |
|---|---|---|---|
| **A. Consolider l'existant** | Faire de `platform.css` l'unique base, jetons obligatoires, supprimer écran par écran les couches template et accrétion | Aucune nouvelle techno, prolonge la refonte en cours | Discipline à tenir sans outillage ; dette de specificité |
| **B. Tailwind v4 + composants Blade (recommandé)** | Utiliser Tailwind déjà installé via Vite, jetons dans `@theme`, composants Blade (`x-button`, `x-table`, `x-badge`, `x-field`…), migration page par page | Jetons imposés par construction, CSS livré minimal, cache-busting Vite natif, standard Laravel | Étape de build à ajouter au déploiement ; montée en compétence |
| **C. Tabler (Bootstrap 5)** | Gabarit d'administration libre (MIT) complet | Rendu professionnel rapide, tableaux/formulaires prêts | Réécriture des vues, esthétique générique, deuxième framework CSS |
| **D. Filament pour le back-office** | Panneaux DSHN/DGF/campagnes générés (Livewire) | Tableaux, filtres, exports, formulaires quasi gratuits | Réécriture lourde de la logique d'écran ; deux mondes (fédérations en Blade) |

Écarté : DSFR (identité réservée à l'État français) ; GOV.UK / USWDS utilisables comme **référence de patterns**, pas comme habillage.

**Recommandation : B**, en commençant par les jetons (couleurs, texte secondaire ≥ 4,5:1, espacements, rayons) puis les écrans les plus utilisés, en conservant l'identité verte actuelle. Cela absorbe `platform.css` au lieu de le jeter.

## Correctifs rapides (indépendants du choix)
1. Un jeton `--text-muted` unique à ≥ 4,5:1 (ex. #56645b) remplaçant tous les gris-verts → corrige 263 éléments.
2. Libellés sur les filtres de tableau et les champs d'édition en ligne.
3. Charger la même base CSS sur l'accueil et la connexion.
4. Versionner les assets (`?v=filemtime` à défaut de Vite).

## Reste à faire
Rapport illustré (captures avant/après, maquettes des options), vérification de l'état des P1 de l'audit du 29/09, contrôle des rôles DG/Comité/Ministre au-delà de leur écran d'accueil, clavier et zoom 200 %.
