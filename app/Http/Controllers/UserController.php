<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->latest()->paginate(15);
        $roles = Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'employee_id' => ['nullable', 'string', 'unique:users,employee_id'],
            'contact_number' => ['nullable', 'string'],
            'role' => ['required', 'exists:roles,name'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'employee_id' => $data['employee_id'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'must_reset_password' => true, // Enforce reset on first login
        ]);

        $user->assignRole($data['role']);

        AuditLog::log([
            'action' => 'create',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => "Created user {$user->name} with role {$data['role']}",
        ]);

        return redirect()->route('users.index')->with('success', "User {$user->name} created successfully.");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $defaultPassword = 'Password123!';
        $user->update([
            'password' => Hash::make($defaultPassword),
            'must_reset_password' => true,
        ]);

        AuditLog::log([
            'action' => 'update',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => "Admin triggered password reset for user {$user->name}",
        ]);

        return redirect()->route('users.index')
            ->with('success', "Password for {$user->name} reset to: {$defaultPassword}. User will be forced to change it on next login.");
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';

        AuditLog::log([
            'action' => 'update',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => "User {$user->name} was {$status}",
        ]);

        return redirect()->route('users.index')->with('success', "User {$user->name} has been {$status}.");
    }
}
