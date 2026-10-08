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
            return $query->where('title', 'like', "%{$search}%")
                         ->orWhere('author', 'like', "%{$search}%")
                         ->orWhere('accession_number', 'like', "%{$search}%")
                         ->orWhere('category', 'like', "%{$search}%");
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
        $validated = $request->validate([
            'accession_number' => 'required|unique:books',
            'title' => 'required',
            'author' => 'required',
            'isbn' => 'nullable',
            'category' => 'nullable',
            'total_copies' => 'required|integer|min:1',
        ]);

        $validated['available_copies'] = $validated['total_copies'];

        Book::create($validated);

        return redirect()->route('books.index')->with('success', 'Book added successfully!');
    }

    public function edit(Book $book)
    {
        return view('books.edit', compact('book'));
    }

    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'accession_number' => 'required|unique:books,accession_number,' . $book->id,
            'title' => 'required',
            'author' => 'required',
            'isbn' => 'nullable',
            'category' => 'nullable',
            'total_copies' => 'required|integer|min:1',
            'available_copies' => 'required|integer|min:0',
        ]);

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
}