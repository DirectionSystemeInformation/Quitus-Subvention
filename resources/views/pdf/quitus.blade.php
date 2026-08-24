<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Quitus {{ $federation->federation_name }} {{ $campaign->annee_n1 }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1c1c1e;
            font-size: 13px;
            line-height: 1.5;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-table .logo-cell {
            width: 70px;
        }
        .header-table .logo-cell img {
            width: 60px;
        }
        .header-table .ministry-cell {
            font-size: 11px;
            text-align: center;
        }
        .header-table .ministry-cell strong {
            font-size: 12px;
        }
        .divider {
            border-top: 2px solid #1DAA5E;
            margin: 20px 0 30px;
        }
        h1 {
            text-align: center;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .subtitle {
            text-align: center;
            font-size: 12px;
            color: #6e6e6e;
            margin-bottom: 30px;
        }
        .reference {
            text-align: right;
            font-size: 11px;
            color: #6e6e6e;
            margin-bottom: 20px;
        }
        .body-text {
            text-align: justify;
            margin-bottom: 20px;
        }
        table.details {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
        }
        table.details td {
            border: 1px solid #ccc;
            padding: 10px 14px;
        }
        table.details td.label {
            width: 40%;
            font-weight: bold;
            background: #f5f5f7;
        }
        .amount {
            font-size: 16px;
            font-weight: bold;
            color: #1DAA5E;
        }
        .signature {
            margin-top: 60px;
            width: 100%;
        }
        .signature td {
            width: 50%;
            text-align: center;
            font-size: 12px;
        }
        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 10px;
            color: #6e6e6e;
            border-top: 1px solid #ccc;
            padding-top: 10px;
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

    <p class="reference">Référence : {{ $allocation->quitus_reference }}</p>

    <h1>Quitus de déblocage de subvention</h1>
    <p class="subtitle">Fédérations sportives et de loisirs — Campagne {{ $campaign->annee_n1 }}</p>

    <p class="body-text">
        La Direction du Sport de Haut Niveau atteste que la <strong>{{ $federation->federation_name }}</strong>
        a satisfait aux conditions requises pour le déblocage de sa subvention au titre de la campagne
        {{ $campaign->annee_n1 }}, à l'issue du processus d'instruction, de pondération, d'arbitrage et de
        validation prévu par la procédure en vigueur.
    </p>

    <table class="details">
        <tr>
            <td class="label">Fédération</td>
            <td>{{ $federation->federation_name }}</td>
        </tr>
        <tr>
            <td class="label">Responsable</td>
            <td>{{ $federation->name }}</td>
        </tr>
        <tr>
            <td class="label">Catégorie</td>
            <td>{{ $allocation->categorie_ajustee ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Montant du déblocage</td>
            <td class="amount">{{ $allocation->montant_final !== null ? number_format((float) $allocation->montant_final, 0, ',', ' ').' FCFA' : '—' }}</td>
        </tr>
        <tr>
            <td class="label">Date de délivrance</td>
            <td>{{ optional($allocation->quitus_delivered_at)->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table class="signature">
        <tr>
            <td></td>
            <td>
                Fait à Ouagadougou, le {{ optional($allocation->quitus_delivered_at)->format('d/m/Y') }}<br><br><br>
                Le Directeur du Sport de Haut Niveau
            </td>
        </tr>
    </table>

    <div class="footer">
        Ministère des Sports, de la Jeunesse et de l'Emploi — Direction du Sport de Haut Niveau — Document généré électroniquement
    </div>
</body>
</html>
