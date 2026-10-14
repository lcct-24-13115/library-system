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
            <a href="{{ route('patrons.index') }}" class="hover:text-indigo-200">Patrons</a>
            <a href="{{ route('loans.create') }}" class="hover:text-indigo-200">Issue Book</a>
            <a href="{{ route('loans.return.form') }}" class="hover:text-indigo-200">Return Book</a>
            <a href="{{ route('loans.index') }}" class="hover:text-indigo-200">Loans</a>
            <a href="{{ route('reports.index') }}" class="hover:text-indigo-200">Reports</a>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-10 px-4">
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

                @include('books._form')

                <div class="pt-4 flex justify-end space-x-3">
                    <a href="{{ route('books.index') }}" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-sm hover:bg-slate-50 font-medium">Cancel</a>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">Save Book</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>