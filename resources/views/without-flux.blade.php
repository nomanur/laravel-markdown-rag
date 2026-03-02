<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - No Flux Chat</title>

    <!-- Tailwind CSS via CDN for Standalone Performance -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Marked.js for Markdown Support -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    @livewireStyles
</head>
<body class="bg-gray-50 h-full antialiased dark:bg-gray-950">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-4xl">
            <livewire:without-flux/>
        </div>
    </div>

    @livewireScripts
</body>
</html>