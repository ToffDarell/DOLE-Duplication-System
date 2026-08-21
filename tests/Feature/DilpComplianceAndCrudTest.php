<?php

use App\Models\AuditLog;
use App\Models\DilpGroup;
use App\Models\DilpProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Validator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Encoder', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('dilp gatekeeper prevents creating a new project for a group with pending liquidation', function () {
    $group = DilpGroup::create([
        'group_name' => 'Bukidnon Farmers Cooperative',
        'co_partner_name' => 'LGU Malaybalay',
    ]);

    DilpProject::create([
        'dilp_group_id' => $group->id,
        'project_name' => 'Past Rice Mill Project',
        'liquidation_status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)->post(route('dilp.projects.store'), [
        'dilp_group_id' => $group->id,
        'project_name' => 'New Solar Dryer Project',
        'liquidation_status' => 'pending',
    ]);

    $response->assertSessionHasErrors('dilp_group_id');
    expect(DilpProject::where('project_name', 'New Solar Dryer Project')->exists())->toBeFalse();
});

test('dilp gatekeeper prevents creating a new project for a group with partial liquidation', function () {
    $group = DilpGroup::create([
        'group_name' => 'Valencia Weavers Guild',
        'co_partner_name' => 'DTI Bukidnon',
    ]);

    DilpProject::create([
        'dilp_group_id' => $group->id,
        'project_name' => 'Weaving Equipment Grant',
        'liquidation_status' => 'partial',
    ]);

    $response = $this->actingAs($this->admin)->post(route('dilp.projects.store'), [
        'dilp_group_id' => $group->id,
        'project_name' => 'Yarn Raw Materials Grant',
        'liquidation_status' => 'pending',
    ]);

    $response->assertSessionHasErrors('dilp_group_id');
    expect(DilpProject::where('project_name', 'Yarn Raw Materials Grant')->exists())->toBeFalse();
});

test('dilp gatekeeper allows creating a new project if the group past project is fully liquidated', function () {
    $group = DilpGroup::create([
        'group_name' => 'Maramag Organic Producers',
        'co_partner_name' => 'DA Bukidnon',
    ]);

    DilpProject::create([
        'dilp_group_id' => $group->id,
        'project_name' => 'Greenhouse Grant 2025',
        'liquidation_status' => 'liquidated',
    ]);

    $response = $this->actingAs($this->admin)->post(route('dilp.projects.store'), [
        'dilp_group_id' => $group->id,
        'project_name' => 'Hydroponics Expansion 2026',
        'liquidation_status' => 'pending',
    ]);

    $response->assertRedirect(route('dilp.projects.index'));
    $response->assertSessionHas('success');
    expect(DilpProject::where('project_name', 'Hydroponics Expansion 2026')->exists())->toBeTrue();
});

test('dilp project can be deleted with audit logging', function () {
    $project = DilpProject::create([
        'project_name' => 'Temporary Test Project',
        'liquidation_status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)->delete(route('dilp.projects.destroy', $project));

    $response->assertRedirect(route('dilp.projects.index'));
    $response->assertSessionHas('success');
    expect(DilpProject::find($project->id))->toBeNull();

    $log = AuditLog::where('action', 'DILP_PROJECT_DELETED')
        ->where('model_id', $project->id)
        ->first();
    expect($log)->not->toBeNull()
        ->and($log->description)->toContain("Deleted DILP Project ID: {$project->id}");
});

test('dilp group deletion is blocked if associated projects exist', function () {
    $group = DilpGroup::create([
        'group_name' => 'Active Project Association',
    ]);

    DilpProject::create([
        'dilp_group_id' => $group->id,
        'project_name' => 'Active Swine Raising Grant',
        'liquidation_status' => 'liquidated',
    ]);

    $response = $this->actingAs($this->admin)->delete(route('dilp.groups.destroy', $group));

    $response->assertRedirect(route('dilp.groups.index'));
    $response->assertSessionHasErrors('error');
    expect(DilpGroup::find($group->id))->not->toBeNull();
});

test('dilp group without projects can be successfully deleted with audit logging', function () {
    $group = DilpGroup::create([
        'group_name' => 'Empty Inactive Group',
    ]);

    $response = $this->actingAs($this->admin)->delete(route('dilp.groups.destroy', $group));

    $response->assertRedirect(route('dilp.groups.index'));
    $response->assertSessionHas('success');
    expect(DilpGroup::find($group->id))->toBeNull();

    $log = AuditLog::where('action', 'DILP_GROUP_DELETED')
        ->where('model_id', $group->id)
        ->first();
    expect($log)->not->toBeNull()
        ->and($log->description)->toContain("Deleted DILP Group ID: {$group->id}");
});
