<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\FederationActivity;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $federations = collect();
        $reports = collect();
        $activities = collect();

        if ($query !== '') {
            $federations = User::where('role', 'federation')
                ->where(function ($q) use ($query) {
                    $q->where('federation_name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('arrete_numero', 'like', "%{$query}%");
                })
                ->orderBy('federation_name')
                ->limit(20)
                ->get();

            $reports = Report::with('user')
                ->where('status', '!=', 'brouillon')
                ->where(function ($q) use ($query) {
                    $q->where('type', 'like', "%{$query}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('federation_name', 'like', "%{$query}%"));
                })
                ->latest()
                ->limit(20)
                ->get();

            if (auth()->user()->isAdmin()) {
                $activities = FederationActivity::with('user')
                    ->where(function ($q) use ($query) {
                        $q->where('designation', 'like', "%{$query}%")
                            ->orWhereHas('user', fn ($uq) => $uq->where('federation_name', 'like', "%{$query}%"));
                    })
                    ->latest()
                    ->limit(20)
                    ->get();
            }
        }

        return view('dshn.search', compact('query', 'federations', 'reports', 'activities'));
    }
}
