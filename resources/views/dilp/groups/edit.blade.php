@extends('layouts.app')
@section('title', 'Edit DILP Group')
@section('page-title', 'Edit DILP Group')

@section('content')
<div class="mx-auto max-w-lg pt-4">
    <div class="rounded-2xl border border-gray-200/60 bg-white p-6 shadow-sm">
        <form action="{{ route('dilp.groups.update', $group) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Group / Cooperative Name *</label>
                <input type="text" name="group_name" value="{{ old('group_name', $group->group_name) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Co-Partner Name</label>
                <input type="text" name="co_partner_name" value="{{ old('co_partner_name', $group->co_partner_name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Co-Partner Contact</label>
                <input type="text" name="co_partner_contact" value="{{ old('co_partner_contact', $group->co_partner_contact) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('dilp.groups.index') }}" class="rounded-lg border px-4 py-2 text-sm">Cancel</a>
                <button type="submit" class="rounded-lg bg-dole-blue px-5 py-2 text-sm font-semibold text-white">Update Group</button>
            </div>
        </form>
    </div>
</div>
@endsection
