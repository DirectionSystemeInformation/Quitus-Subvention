<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\CanvasAxe;
use App\Models\CanvasSousAxe;
use Illuminate\Http\Request;

class CanevasController extends Controller
{
    public function index()
    {
        $axes = CanvasAxe::with('sousAxes')->orderBy('sort_order')->get();

        return view('dshn.canevas', compact('axes'));
    }

    public function storeAxe(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'label' => ['required', 'string', 'max:255'],
        ]);

        CanvasAxe::create([
            'code' => $data['code'],
            'label' => $data['label'],
            'sort_order' => CanvasAxe::max('sort_order') + 1,
        ]);

        return back()->with('status', 'Axe ajouté.');
    }

    public function updateAxe(Request $request, CanvasAxe $axe)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'label' => ['required', 'string', 'max:255'],
        ]);

        $axe->update($data);

        return back()->with('status', 'Axe mis à jour.');
    }

    public function destroyAxe(CanvasAxe $axe)
    {
        $axe->delete();

        return back()->with('status', 'Axe supprimé. Les rapports déjà soumis conservent leurs données.');
    }

    public function storeSousAxe(Request $request, CanvasAxe $axe)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'label' => ['required', 'string', 'max:255'],
        ]);

        $axe->sousAxes()->create([
            'code' => $data['code'],
            'label' => $data['label'],
            'lignes_count' => 1,
            'sort_order' => $axe->sousAxes()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'Sous-axe ajouté.');
    }

    public function updateSousAxe(Request $request, CanvasSousAxe $sousAxe)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'label' => ['required', 'string', 'max:255'],
        ]);

        $sousAxe->update($data);

        return back()->with('status', 'Sous-axe mis à jour.');
    }

    public function destroySousAxe(CanvasSousAxe $sousAxe)
    {
        $sousAxe->delete();

        return back()->with('status', 'Sous-axe supprimé. Les rapports déjà soumis conservent leurs données.');
    }
}
