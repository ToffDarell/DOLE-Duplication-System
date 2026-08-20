<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = [
            'agency_name' => SystemSetting::get('agency_name', 'DOLE Field Office - Bukidnon'),
            'system_title' => SystemSetting::get('system_title', 'DOLE Bukidnon Duplicate Detection System'),
            'duplicate_threshold' => SystemSetting::get('duplicate_threshold', '75'),
            'enable_exact_dob_check' => SystemSetting::get('enable_exact_dob_check', '1'),
            'enable_gov_id_check' => SystemSetting::get('enable_gov_id_check', '1'),
            'default_import_start_row' => SystemSetting::get('default_import_start_row', '16'),
            'current_fiscal_year' => SystemSetting::get('current_fiscal_year', date('Y')),
            'tupad_max_days' => SystemSetting::get('tupad_max_days', '10'),
            'audit_log_retention_days' => SystemSetting::get('audit_log_retention_days', '365'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'agency_name' => ['required', 'string', 'max:255'],
            'system_title' => ['required', 'string', 'max:255'],
            'duplicate_threshold' => ['required', 'integer', 'min:50', 'max:100'],
            'enable_exact_dob_check' => ['nullable', 'boolean'],
            'enable_gov_id_check' => ['nullable', 'boolean'],
            'default_import_start_row' => ['required', 'integer', 'min:1'],
            'current_fiscal_year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'tupad_max_days' => ['required', 'integer', 'min:1', 'max:30'],
            'audit_log_retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
        ]);

        SystemSetting::set('agency_name', $data['agency_name'], 'Official agency department title');
        SystemSetting::set('system_title', $data['system_title'], 'Application brand name');
        SystemSetting::set('duplicate_threshold', (string) $data['duplicate_threshold'], 'Matching score sensitivity percentage');
        SystemSetting::set('enable_exact_dob_check', $request->boolean('enable_exact_dob_check') ? '1' : '0', 'Flag identical name + DOB matches');
        SystemSetting::set('enable_gov_id_check', $request->boolean('enable_gov_id_check') ? '1' : '0', 'Flag duplicate government ID numbers');
        SystemSetting::set('default_import_start_row', (string) $data['default_import_start_row'], 'Default row for Excel/CSV import parsing');
        SystemSetting::set('current_fiscal_year', (string) $data['current_fiscal_year'], 'Active program implementation year');
        SystemSetting::set('tupad_max_days', (string) $data['tupad_max_days'], 'Maximum allowable work days for TUPAD');
        SystemSetting::set('audit_log_retention_days', (string) $data['audit_log_retention_days'], 'Days to retain audit logs');

        AuditLog::log([
            'action' => 'update_settings',
            'model_type' => SystemSetting::class,
            'model_id' => null,
            'description' => 'Updated system settings and duplicate engine parameters',
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'System settings saved successfully.');
    }
}
