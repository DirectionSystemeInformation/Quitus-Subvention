@php
    /** @var \App\Support\Quitus $quitus */
    $campaign = $quitus->campaign;
    $federation = $quitus->federation;
    $allocation = $quitus->allocation;
    $montant = $quitus->montantSubvention();
    $apercu = $apercu ?? false;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Quitus {{ $federation->federation_name }} : saison {{ $campaign->annee_n1 }}</title>
    {{-- Reprend le modèle officiel « QUITUS POUR LE RETRAIT D'UNE SUBVENTION » :
         variante avec observations du Directeur de la gestion des finances ;
         visas et dates apposés à la main sur le document imprimé. --}}
    <style>
        @page { margin: 1.6cm 1.8cm 1.8cm; }
        body { font-family: 'Times New Roman', Times, serif; color: #000; font-size: 11.5pt; line-height: 1.3; }
        .arial { font-family: Helvetica, Arial, sans-serif; }

        table.entete { width: 100%; border-collapse: collapse; }
        table.entete td { vertical-align: top; padding: 0; font-weight: bold; }
        .ministere { width: 58%; text-align: center; font-size: 11pt; }
        .ministere .sep { font-weight: normal; letter-spacing: 1px; line-height: 1; margin: 1px 0 2px; }
        .etat { text-align: center; font-size: 11pt; }
        .etat .devise { display: block; margin-top: 2px; font-size: 9.5pt; font-style: italic; }

        .saison { margin: 22px 0 14px; font-weight: bold; font-size: 12pt; }
        h1 { margin: 0 0 18px; text-align: center; font-size: 14.5pt; line-height: 1.35; font-weight: bold; text-transform: uppercase; }

        table.activites { width: 100%; border-collapse: collapse; font-family: Helvetica, Arial, sans-serif; font-size: 9.5pt; }
        table.activites th, table.activites td { border: 1px solid #000; padding: 6px 7px; vertical-align: middle; }
        table.activites th { font-weight: bold; text-align: center; font-size: 9pt; }
        table.activites .num { width: 6%; text-align: center; }
        table.activites .montant { width: 16%; text-align: right; white-space: nowrap; }
        table.activites .date { width: 14%; text-align: center; }
        table.activites .delai { width: 15%; text-align: center; }
        table.activites tr.total td { font-weight: bold; }
        table.activites tr.total .libelle { text-align: center; }

        .arrete { margin: 18px 0 20px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; font-style: italic; font-size: 10.5pt; text-align: justify; }

        table.visas { width: 100%; border-collapse: collapse; font-family: Helvetica, Arial, sans-serif; font-size: 10pt; }
        table.visas td { width: 50%; vertical-align: top; padding: 0; }
        table.visas td.droite { padding-left: 24px; }
        .espace-visa { height: 72px; }
        .observations { margin-top: 20px; font-family: Helvetica, Arial, sans-serif; font-size: 10pt; }
        .espace-observations { height: 58px; }

        .nb { margin-top: 18px; font-family: Helvetica, Arial, sans-serif; font-size: 8.8pt; font-weight: bold; text-align: justify; line-height: 1.35; }
        .nb p { margin: 0 0 6px; }
        .nb u { text-decoration: underline; }

        .pied { position: fixed; bottom: -1.2cm; left: 0; right: 0; font-family: Helvetica, Arial, sans-serif; font-size: 7.5pt; color: #666; text-align: right; }
        .filigrane { position: fixed; top: 38%; left: 0; right: 0; text-align: center; font-family: Helvetica, Arial, sans-serif; font-size: 70pt; font-weight: bold; color: #e6e6e6; transform: rotate(-30deg); z-index: -1; }
    </style>
</head>
<body>
    @if ($apercu)
        <div class="filigrane">APERÇU</div>
    @endif

    <table class="entete">
        <tr>
            <td class="ministere">
                MINISTERE DES SPORTS,<br>
                DE LA JEUNESSE ET DE L’EMPLOI
                <div class="sep">------------------</div>
                SECRETARIAT GENERAL
                <div class="sep">------------------</div>
                DIRECTION GENERALE DES SPORTS<br>
                ET DES LOISIRS
                <div class="sep">------------------</div>
                DIRECTION DU SPORT DE HAUT NIVEAU
            </td>
            <td class="etat">
                BURKINA FASO
                <span class="devise">La Patrie ou la Mort, nous Vaincrons</span>
            </td>
        </tr>
    </table>

    <p class="saison">Saison {{ $campaign->annee_n1 }}</p>

    <h1>Quitus pour le retrait d’une subvention accordée à la {{ mb_strtoupper($federation->federation_name) }}</h1>

    <table class="activites">
        <thead>
            <tr>
                <th class="num">N°</th>
                <th>ACTIVITE</th>
                <th class="montant" style="text-align: center">MONTANT</th>
                <th class="date">DATE DE L’ACTIVITE</th>
                <th class="delai">DELAI DE JUSTIFICATION</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($quitus->lignes as $index => $ligne)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $ligne->designation ?: $ligne->sous_axe_label }}</td>
                    <td class="montant">{{ \App\Support\Quitus::fcfa((float) $ligne->montant) }}</td>
                    <td class="date">{{ $ligne->date?->format('d/m/Y') }}</td>
                    <td class="delai">{{ $ligne->delai_justification?->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td class="num">1</td><td></td><td class="montant"></td><td class="date"></td><td class="delai"></td></tr>
            @endforelse
            <tr class="total">
                <td class="num"></td>
                <td class="libelle">TOTAL</td>
                <td class="montant">{{ \App\Support\Quitus::fcfa($quitus->totalActivites()) }} FCFA</td>
                <td class="date"></td>
                <td class="delai"></td>
            </tr>
        </tbody>
    </table>

    <p class="arrete">
        Arrêté la présente subvention à la somme de {{ \App\Support\Quitus::enLettres($montant) }}
        ({{ \App\Support\Quitus::fcfa($montant) }}) francs CFA.
    </p>

    <table class="visas">
        <tr>
            <td>Visa du Directeur du sport de haut niveau</td>
            <td class="droite">Visa de la Directrice générale<br>des sports et des Loisirs</td>
        </tr>
        <tr><td class="espace-visa"></td><td class="espace-visa"></td></tr>
        <tr>
            <td>Date :</td>
            <td class="droite">Date :</td>
        </tr>
    </table>

    <div class="observations">
        Observations du Directeur de la gestion des finances
        <div class="espace-observations"></div>
        Date :
    </div>

    <div class="nb">
        <p><u>NB</u> : 1. Dans le cadre de la mise en œuvre des présentes activités, les directeurs de structures de supervision, le Président de la fédération devront adresser à monsieur le Ministre des sports et des loisirs au moins 14 jours avant, une correspondance faisant état de la date, de l’heure et du lieu de la tenue desdites activités.</p>
        <p>2. Le déblocage des tranches suivantes est conditionné par la justification des 1ères tranches.</p>
    </div>

    <div class="pied">
        @if ($apercu)
            Aperçu : document non délivré
        @else
            Réf. {{ $allocation->quitus_reference }} : délivré le {{ $allocation->quitus_delivered_at?->format('d/m/Y') }}
        @endif
    </div>
</body>
</html>
