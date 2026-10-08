<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $action = $request->query('action');

        $logs = ActivityLog::with('user')
            ->when($action && isset(ActivityLog::ACTIONS[$action]), fn ($q) => $q->where('action', $action))
            ->latest('created_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity-log.index', [
            'logs'    => $logs,
            'action'  => $action,
            'actions' => ActivityLog::ACTIONS,
        ]);
    }
}
