<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class FederationController extends Controller
{
    public function index()
    {
        $federations = User::where('role', 'federation')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->get();

        return view('dshn.federations', compact('federations'));
    }

    public function validate_(User $federation)
    {
        abort_unless($federation->role === 'federation', 404);

        $federation->update(['status' => 'active']);

        return back()->with('status', "Le compte de {$federation->federation_name} a été validé.");
    }

    public function reject(User $federation)
    {
        abort_unless($federation->role === 'federation', 404);

        $federation->update(['status' => 'rejected']);

        return back()->with('status', "Le compte de {$federation->federation_name} a été rejeté.");
    }
}
