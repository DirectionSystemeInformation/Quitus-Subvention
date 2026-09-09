<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'pending_federations' => User::where('role', 'federation')->where('status', 'pending')->count(),
            'active_federations' => User::where('role', 'federation')->where('status', 'active')->count(),
            'reports_soumis' => Report::where('status', 'soumis')->count(),
            'reports_valide' => Report::where('status', 'valide')->count(),
            'reports_rejete' => Report::where('status', 'rejete')->count(),
        ];

        $recentPending = User::where('role', 'federation')
            ->where('status', 'pending')
            ->oldest()
            ->take(5)
            ->get();

        $recentReports = Report::with('user')
            ->where('status', 'soumis')
            ->oldest()
            ->take(5)
            ->get();

        $reportsTotal = $stats['reports_soumis'] + $stats['reports_valide'] + $stats['reports_rejete'];

        return view('dshn.dashboard', compact('stats', 'recentPending', 'recentReports', 'reportsTotal'));
    }
}
