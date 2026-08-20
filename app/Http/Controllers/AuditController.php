<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user');

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $logs = $query->latest('created_at')->paginate(25)->withQueryString();
        $users = User::all();
        $actions = AuditLog::distinct()->pluck('action');

        return view('audit.index', compact('logs', 'users', 'actions'));
    }
}
