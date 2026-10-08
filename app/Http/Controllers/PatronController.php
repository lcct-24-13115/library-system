<?php

namespace App\Http\Controllers;

use App\Models\Patron;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatronController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $patrons = Patron::withCount(['loans as active_loans_count' => fn ($q) => $q->whereNull('returned_at')])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('id_number', 'like', "%{$search}%")
                      ->orWhere('patron_type', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('patrons.index', compact('patrons', 'search'));
    }

    public function create()
    {
        return view('patrons.create');
    }

    public function store(Request $request)
    {
        Patron::create($this->validated($request));

        return redirect()->route('patrons.index')->with('success', 'Patron added successfully!');
    }

    public function edit(Patron $patron)
    {
        return view('patrons.edit', compact('patron'));
    }

    public function update(Request $request, Patron $patron)
    {
        $patron->update($this->validated($request, $patron));

        return redirect()->route('patrons.index')->with('success', 'Patron updated successfully!');
    }

    public function destroy(Patron $patron)
    {
        // Keep borrowing history intact: patrons with loan records can't be deleted.
        if ($patron->loans()->exists()) {
            return back()->withErrors([
                'delete' => "{$patron->name} has borrowing records and can't be deleted. Set the patron to Inactive instead.",
            ]);
        }

        $patron->delete();

        return redirect()->route('patrons.index')->with('success', 'Patron deleted successfully!');
    }

    private function validated(Request $request, ?Patron $patron = null): array
    {
        return $request->validate([
            'id_number'   => ['required', 'string', 'max:255', Rule::unique('patrons', 'id_number')->ignore($patron?->id)],
            'name'        => ['required', 'string', 'max:255'],
            'patron_type' => ['required', Rule::in(Patron::TYPES)],
            'email'       => ['nullable', 'email', 'max:255'],
            'contact_no'  => ['nullable', 'string', 'max:50'],
            'status'      => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
