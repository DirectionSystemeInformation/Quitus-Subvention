<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::with('causer')->latest()->paginate(50);

        return view('dshn.activity-log', compact('logs'));
    }
}
