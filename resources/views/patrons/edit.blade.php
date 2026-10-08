@extends('layouts.library')

@section('title', 'Edit Patron - SALRC Library')
@section('width', 'max-w-3xl')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-extrabold text-slate-900">Edit Patron</h2>
        <a href="{{ route('patrons.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">&larr; Back to Patrons</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form action="{{ route('patrons.update', $patron) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            @include('patrons._form', ['patron' => $patron])
            <div class="pt-4 flex justify-end space-x-3">
                <a href="{{ route('patrons.index') }}" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-sm hover:bg-slate-50 font-medium">Cancel</a>
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">Update Patron</button>
            </div>
        </form>
    </div>
@endsection
