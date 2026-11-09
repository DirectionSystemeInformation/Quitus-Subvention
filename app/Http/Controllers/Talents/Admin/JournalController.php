<?php

namespace App\Http\Controllers\Talents\Admin;

use App\Http\Controllers\Controller;
use App\Models\Talents\JournalActivite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class JournalController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:journal.voir')];
    }

    public function index(Request $request)
    {
        return view('suivi.admin.journal', [
            'activites' => JournalActivite::with('user')
                ->when($request->query('module'), fn ($q, $v) => $q->where('module', $v))
                ->when($request->query('action'), fn ($q, $v) => $q->where('action', $v))
                ->when($request->query('user_id'), fn ($q, $v) => $q->where('user_id', $v))
                ->when($request->query('du'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($request->query('au'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
                ->latest('created_at')->paginate(30)->withQueryString(),
            'modules' => JournalActivite::distinct()->orderBy('module')->pluck('module'),
            'actions' => JournalActivite::distinct()->orderBy('action')->pluck('action'),
            'utilisateurs' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
