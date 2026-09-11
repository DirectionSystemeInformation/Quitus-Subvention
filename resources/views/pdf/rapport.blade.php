<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} {{ $report->year }} — {{ $report->user->federation_name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1c1c1e;
            font-size: 11px;
            line-height: 1.5;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-table .logo-cell {
            width: 60px;
        }
        .header-table .logo-cell img {
            width: 50px;
        }
        .header-table .ministry-cell {
            font-size: 10px;
            text-align: center;
        }
        .header-table .ministry-cell strong {
            font-size: 11px;
        }
        .divider {
            border-top: 2px solid #1DAA5E;
            margin: 16px 0 20px;
        }
        h1 {
            text-align: center;
            font-size: 17px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .subtitle {
            text-align: center;
            font-size: 11px;
            color: #6e6e6e;
            margin-bottom: 4px;
        }
        .federation-name {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary-table td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            text-align: center;
            font-size: 10px;
            color: #6e6e6e;
        }
        .summary-table strong {
            display: block;
            font-size: 14px;
            color: #1c1c1e;
            margin-bottom: 2px;
        }
        .validation-line {
            text-align: center;
            font-size: 11px;
            color: #047857;
            margin-bottom: 20px;
        }
        h2.axe-title {
            font-size: 13px;
            text-transform: uppercase;
            margin: 20px 0 4px;
            padding-bottom: 4px;
            border-bottom: 1px solid #1c1c1e;
        }
        h3.sousaxe-title {
            font-size: 11px;
            font-weight: bold;
            margin: 12px 0 6px;
            color: #333;
        }
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.lines th, table.lines td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            font-size: 10px;
        }
        table.lines th {
            background: #f5f5f7;
            text-align: left;
        }
        .col-num { width: 4%; text-align: center; }
        .col-designation { width: 33%; }
        .col-montant { width: 13%; text-align: right; }
        .col-contrib { width: 15%; text-align: right; }
        .col-date { width: 12%; }
        .col-obs { width: 23%; }
        table.totals {
            width: 60%;
            margin: 20px 0 0 auto;
            border-collapse: collapse;
        }
        table.totals td {
            border: 1px solid #ccc;
            padding: 8px 10px;
        }
        table.totals td.label {
            font-weight: bold;
            background: #f5f5f7;
        }
        table.totals td.value {
            text-align: right;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 9px;
            color: #6e6e6e;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo-cell"><img src="{{ public_path('img/armoiries.png') }}" alt="Armoiries"></td>
            <td class="ministry-cell">
                <strong>MINISTÈRE DES SPORTS, DE LA JEUNESSE ET DE L'EMPLOI</strong><br>
                Secrétariat Général<br>
                Direction du Sport de Haut Niveau
            </td>
            <td class="logo-cell"></td>
        </tr>
    </table>

    <div class="divider"></div>

    <h1>{{ $title }} {{ $report->year }}</h1>
    @if ($programmeLabel)
        <p class="subtitle">{{ $programmeLabel }}</p>
    @endif
    <p class="federation-name">{{ $report->user->federation_name }}</p>

    @if ($report->validated_at)
        <p class="validation-line">✓ Validé par la DSHN le {{ $report->validated_at->locale('fr')->translatedFormat('d F Y') }} à {{ $report->validated_at->format('H:i') }}</p>
    @endif

    <table class="summary-table">
        <tr>
            <td><strong>{{ $report->budgetLines->count() }}</strong>ligne(s)</td>
            <td><strong>{{ $axeGroups->count() }}</strong>axe(s)</td>
            <td><strong>{{ number_format((float) $totalMontant, 0, ',', ' ') }} FCFA</strong>Montant total</td>
        </tr>
    </table>

    @foreach ($axeGroups as $axeGroup)
        <h2 class="axe-title">
            Axe {{ $axeGroup['number'] ?? $axeGroup['axe'] }}@if ($axeGroup['label_parts']['description']) — {{ $axeGroup['label_parts']['description'] }}@endif
        </h2>

        @foreach ($axeGroup['sous_axes'] as $sousAxe)
            <h3 class="sousaxe-title">{{ $sousAxe['sous_axe_code'] }} — {{ $sousAxe['sous_axe_label'] }}</h3>
            <table class="lines">
                <thead>
                    <tr>
                        <th class="col-num">N°</th>
                        <th class="col-designation">Désignation de l'activité</th>
                        <th class="col-montant">Montant (FCFA)</th>
                        <th class="col-contrib">Contribution (FCFA)</th>
                        <th class="col-date">Date</th>
                        <th class="col-obs">Observations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sousAxe['lines'] as $line)
                        <tr>
                            <td class="col-num">{{ $line->numero_ligne }}</td>
                            <td class="col-designation">{{ $line->designation }}</td>
                            <td class="col-montant">{{ $line->montant !== null ? number_format((float) $line->montant, 0, ',', ' ') : '—' }}</td>
                            <td class="col-contrib">{{ is_numeric($line->contribution_partenaires) ? number_format((float) $line->contribution_partenaires, 0, ',', ' ') : ($line->contribution_partenaires ?: '—') }}</td>
                            <td class="col-date">{{ optional($line->date)->format('d/m/Y') ?? '—' }}</td>
                            <td class="col-obs">{{ $line->observations ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endforeach

    <table class="totals">
        <tr>
            <td class="label">Montant total</td>
            <td class="value">{{ number_format((float) $totalMontant, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td class="label">Contribution totale des partenaires</td>
            <td class="value">{{ number_format((float) $totalContribution, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    <div class="footer">
        Ministère des Sports, de la Jeunesse et de l'Emploi — Direction du Sport de Haut Niveau — Document généré électroniquement le {{ now()->locale('fr')->translatedFormat('d F Y') }}
    </div>
</body>
</html>
