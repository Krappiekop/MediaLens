<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MediaLens')</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>



<body class="flex min-h-screen flex-col bg-white text-gray-900">
    <header class="border-b border-gray-200 px-4 py-4">
        <a href="{{ route('gebeurtenissen.index') }}" class="text-2xl font-semibold">MediaLens</a>
    </header>

    <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-6">
        @yield('content')
    </main>

    <footer class="border-t border-gray-200 px-4 py-4 text-sm text-gray-600">
        MediaLens
    </footer>
</body>

</html>