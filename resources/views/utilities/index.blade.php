@extends('layouts.library')

@section('title', 'Utilities - SALRC Library')
@section('width', 'max-w-5xl')

@section('content')
    <div class="mb-6">
        <h2 class="text-3xl font-extrabold text-slate-900">Utilities</h2>
        <p class="text-slate-500 text-sm">Admin only. Manage user accounts and library settings.</p>
    </div>

    <div class="bg-teal-700 text-white text-center font-bold rounded-lg px-4 py-2 mb-4">
        Click any of the tabs that you want to execute
    </div>

    @include('utilities._tabs', ['tabs' => $tabs, 'tab' => $tab])

    <div class="bg-white rounded-b-xl rounded-tr-xl shadow-sm border border-slate-200 p-6" id="utilities-panel" role="tabpanel">
        @include('utilities._' . $tab)
    </div>
@endsection
