<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book - SALRC Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">

    <nav class="bg-indigo-900 text-white px-6 py-4 flex justify-between items-center shadow-md">
        <h1 class="text-xl font-bold tracking-wide">📚 SALRC Library System</h1>
        <div class="space-x-4 text-sm font-medium">
            <a href="/dashboard" class="hover:text-indigo-200">Dashboard</a>
            <a href="{{ route('books.index') }}" class="hover:text-indigo-200">Books Catalog</a>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto py-10 px-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-extrabold text-slate-900">Add New Book Record</h2>
            <a href="{{ route('books.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                &larr; Back to Catalog
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-rose-100 border border-rose-400 text-rose-700 rounded-lg text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <form action="{{ route('books.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Accession Number</label>
                    <input type="text" name="accession_number" required placeholder="e.g. ACC-2026-001" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Book Title</label>
                    <input type="text" name="title" required placeholder="e.g. Introduction to Information Technology" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Author</label>
                        <input type="text" name="author" required placeholder="Author name" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">ISBN (Optional)</label>
                        <input type="text" name="isbn" placeholder="978-3-16-148410-0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Category</label>
                        <input type="text" name="category" placeholder="e.g. Technology, Science, Fiction" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Total Copies</label>
                        <input type="number" name="total_copies" min="1" value="1" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="pt-4 flex justify-end space-x-3">
                    <a href="{{ route('books.index') }}" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-sm hover:bg-slate-50 font-medium">Cancel</a>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">Save Book</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>