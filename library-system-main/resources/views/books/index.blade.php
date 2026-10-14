<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SALRC Book Catalog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">

    <nav class="bg-indigo-900 text-white px-6 py-4 flex justify-between items-center shadow-md">
        <h1 class="text-xl font-bold tracking-wide">📚 SALRC Library System</h1>
        <div class="space-x-4 text-sm font-medium">
            <a href="/dashboard" class="hover:text-indigo-200">Dashboard</a>
            <a href="/books" class="underline underline-offset-4 decoration-2 decoration-indigo-400">Books Catalog</a>
            <a href="{{ route('patrons.index') }}" class="hover:text-indigo-200">Patrons</a>
            <a href="{{ route('loans.create') }}" class="hover:text-indigo-200">Issue Book</a>
            <a href="{{ route('loans.return.form') }}" class="hover:text-indigo-200">Return Book</a>
            <a href="{{ route('loans.index') }}" class="hover:text-indigo-200">Loans</a>
            <a href="{{ route('reports.index') }}" class="hover:text-indigo-200">Reports</a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto py-10 px-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900">Book Catalog</h2>
                <p class="text-slate-500 text-sm">Manage and track library book inventory</p>
            </div>
            <a href="{{ route('books.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-lg shadow-md transition text-center">
                + Add New Book
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-100 border border-emerald-400 text-emerald-800 rounded-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-rose-100 border border-rose-400 text-rose-700 rounded-lg shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Search Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
            <form action="{{ route('books.index') }}" method="GET" class="flex gap-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search by Title, Author, Subject, Call No., Publisher, ISBN or Accession No..." 
                    class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-indigo-500"
                >
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
                    Search
                </button>
                @if(request('search'))
                    <a href="{{ route('books.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-4 py-2 rounded-lg text-sm flex items-center transition">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Catalog Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th class="p-4">Accession No.</th>
                        <th class="p-4">Title</th>
                        <th class="p-4">Author</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Copies (Avail / Total)</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($books as $book)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4 font-bold text-indigo-900">{{ $book->accession_number }}</td>
                            <td class="p-4 font-medium text-slate-800">
                                <a href="{{ route('books.show', $book->id) }}" class="hover:text-indigo-700 hover:underline">{{ $book->title }}</a>
                                @if($book->subtitle)<span class="text-slate-500 font-normal">: {{ $book->subtitle }}</span>@endif
                                @if($book->call_number || $book->media_type)
                                    <div class="text-xs text-slate-500 font-normal mt-0.5">
                                        {{ $book->call_number }}@if($book->call_number && $book->media_type) &middot; @endif{{ $book->media_type ? ucfirst($book->media_type) : '' }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 text-slate-600">{{ $book->author }}</td>
                            <td class="p-4">
                                <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full text-xs font-semibold">
                                    {{ $book->category ?? 'General' }}
                                </span>
                            </td>
                            <td class="p-4 font-semibold text-slate-700">{{ $book->available_copies }} / {{ $book->total_copies }}</td>
                            <td class="p-4 text-center space-x-2">
                                <a href="{{ route('books.show', $book->id) }}" class="inline-block bg-slate-700 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-slate-800 shadow-sm transition">
                                    View
                                </a>
                                <a href="{{ route('books.edit', $book->id) }}" class="inline-block bg-amber-500 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-amber-600 shadow-sm transition">
                                    Edit
                                </a>
                                <form action="{{ route('books.destroy', $book->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-rose-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-rose-700 shadow-sm transition">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-slate-400">
                                <p class="text-lg font-medium">No books found.</p>
                                <p class="text-sm mt-1">Try adjusting your search criteria or add a new book.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $books->appends(['search' => request('search')])->links() }}
        </div>
    </div>

</body>
</html>