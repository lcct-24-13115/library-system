<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $books = Book::when($search, function ($query, $search) {
            return $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('accession_number', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  // RDA fields
                  ->orWhere('isbn', 'like', "%{$search}%")
                  ->orWhere('call_number', 'like', "%{$search}%")
                  ->orWhere('publisher', 'like', "%{$search}%")
                  ->orWhere('subjects', 'like', "%{$search}%")
                  ->orWhere('series_statement', 'like', "%{$search}%");
            });
        })
        ->latest()
        ->paginate(10);

        return view('books.index', compact('books', 'search'));
    }

    public function create()
    {
        return view('books.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge($this->rdaRules(), [
            'accession_number' => 'required|unique:books',
            'title' => 'required',
            'author' => 'required',
            'isbn' => 'nullable',
            'category' => 'nullable',
            'total_copies' => 'required|integer|min:1',
        ]));

        $validated['available_copies'] = $validated['total_copies'];

        Book::create($validated);

        return redirect()->route('books.index')->with('success', 'Book added successfully!');
    }

    /** Full RDA record + borrowing history of one title. */
    public function show(Book $book)
    {
        $loans = $book->loans()->with('patron')->latest('issued_at')->take(10)->get();

        return view('books.show', compact('book', 'loans'));
    }

    public function edit(Book $book)
    {
        return view('books.edit', compact('book'));
    }

    public function update(Request $request, Book $book)
    {
        $validated = $request->validate(array_merge($this->rdaRules(), [
            'accession_number' => 'required|unique:books,accession_number,' . $book->id,
            'title' => 'required',
            'author' => 'required',
            'isbn' => 'nullable',
            'category' => 'nullable',
            'total_copies' => 'required|integer|min:1',
            'available_copies' => 'required|integer|min:0',
        ]));

        $book->update($validated);

        return redirect()->route('books.index')->with('success', 'Book updated successfully!');
    }

    public function destroy(Book $book)
    {
        // Circulation module: keep borrowing history intact.
        if ($book->loans()->exists()) {
            return redirect()->route('books.index')
                ->with('error', 'This book has borrowing records and cannot be deleted.');
        }

        $book->delete();

        return redirect()->route('books.index')->with('success', 'Book deleted successfully!');
    }

    /** Validation rules for the RDA description fields (all optional). */
    private function rdaRules(): array
    {
        return [
            'subtitle'                    => 'nullable|string|max:255',
            'statement_of_responsibility' => 'nullable|string|max:255',
            'edition_statement'           => 'nullable|string|max:255',
            'place_of_publication'        => 'nullable|string|max:255',
            'publisher'                   => 'nullable|string|max:255',
            'year_of_publication'         => 'nullable|string|max:20',
            'series_statement'            => 'nullable|string|max:255',
            'content_type'                => 'nullable|string|max:255',
            'media_type'                  => 'nullable|string|max:255',
            'carrier_type'                => 'nullable|string|max:255',
            'extent'                      => 'nullable|string|max:255',
            'dimensions'                  => 'nullable|string|max:255',
            'language'                    => 'nullable|string|max:255',
            'call_number'                 => 'nullable|string|max:255',
            'subjects'                    => 'nullable|string|max:2000',
            'genre'                       => 'nullable|string|max:255',
            'summary'                     => 'nullable|string|max:5000',
            'notes'                       => 'nullable|string|max:5000',
            'library'                     => 'nullable|string|max:255',
            'location'                    => 'nullable|string|max:255',
            'date_acquired'               => 'nullable|date',
            'dealer_donor'                => 'nullable|string|max:255',
            'price'                       => 'nullable|numeric|min:0|max:99999999',
        ];
    }
}
