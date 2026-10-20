<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

/** Public catalog (OPAC). Read only: search and availability, nothing about patrons or loans. */
class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $library = trim((string) $request->query('library', ''));

        $books = Book::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . addcslashes($q, '%_\\') . '%';

                $query->where(function ($w) use ($like) {
                    foreach (['title', 'subtitle', 'author', 'isbn', 'call_number', 'subjects', 'category', 'publisher'] as $col) {
                        $w->orWhere($col, 'like', $like);
                    }
                });
            })
            ->when($library !== '', fn ($query) => $query->where('library', $library))
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $libraries = Book::query()->whereNotNull('library')->where('library', '!=', '')
            ->distinct()->orderBy('library')->pluck('library');

        return view('catalog.index', compact('books', 'q', 'library', 'libraries'));
    }
}
