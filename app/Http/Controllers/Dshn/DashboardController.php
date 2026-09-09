<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\FederationActivity;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $isAdmin = Auth::user()->isAdmin();

        $stats = [
            'pending_federations' => User::where('role', 'federation')->where('status', 'pending')->count(),
            'active_federations' => User::where('role', 'federation')->where('status', 'active')->count(),
            'reports_soumis' => Report::where('status', 'soumis')->count(),
            'reports_valide' => Report::where('status', 'valide')->count(),
            'reports_rejete' => Report::where('status', 'rejete')->count(),
        ];

        if ($isAdmin) {
            $stats['activities_soumis'] = FederationActivity::where('status', 'soumis')->count();
            $stats['activities_valide'] = FederationActivity::where('status', 'valide')->count();
            $stats['activities_rejete'] = FederationActivity::where('status', 'rejete')->count();
        }

        $reportsTotal = $stats['reports_soumis'] + $stats['reports_valide'] + $stats['reports_rejete'];
        $activitiesTotal = $isAdmin
            ? $stats['activities_soumis'] + $stats['activities_valide'] + $stats['activities_rejete']
            : 0;

        // Everything currently waiting on a DSHN/admin decision, merged into one
        // queue and sorted oldest-first so the agent tackles the longest-waiting
        // items first regardless of type.
        $actionItems = collect();

        User::where('role', 'federation')->where('status', 'pending')->oldest()->take(10)->get()
            ->each(function ($federation) use ($actionItems) {
                $actionItems->push([
                    'type' => 'Fédération',
                    'federation' => $federation->federation_name,
                    'since' => $federation->created_at,
                    'status' => 'pending',
                    'url' => role_route('federations.show', $federation),
                ]);
            });

        Report::with('user')->where('status', 'soumis')->oldest()->take(10)->get()
            ->each(function ($report) use ($actionItems) {
                $actionItems->push([
                    'type' => 'Rapport',
                    'federation' => $report->user->federation_name,
                    'since' => $report->created_at,
                    'status' => 'soumis',
                    'url' => route('activity-form.show', $report),
                ]);
            });

        if ($isAdmin) {
            FederationActivity::with('user')->where('status', 'soumis')->oldest('submitted_at')->take(10)->get()
                ->each(function ($activity) use ($actionItems) {
                    $actionItems->push([
                        'type' => 'Activité',
                        'federation' => $activity->user->federation_name,
                        'since' => $activity->submitted_at ?? $activity->created_at,
                        'status' => 'soumis',
                        'url' => route('dgf.activities.show', $activity),
                    ]);
                });
        }

        $actionItems = $actionItems->sortBy('since')->take(8)->values();

        return view('dshn.dashboard', compact('stats', 'reportsTotal', 'activitiesTotal', 'actionItems', 'isAdmin'));
    }
}
