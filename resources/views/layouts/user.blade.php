<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Kintime') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            // ローカルストレージまたはシステム設定からテーマを取得して適用
            if (localStorage.getItem('color-theme') === 'dark' ||
            (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
        <div class="min-h-screen flex">
            <!-- サイドナビゲーション -->
            <x-user.navigation />

            <!-- メインコンテンツエリア -->
            <div class="flex-1 ml-0 sm:ml-64 overflow-hidden">
            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow sm:relative fixed top-0 left-0 right-0 z-30 w-full">
                    <div class="py-6 px-4 sm:px-6 lg:px-8 flex items-center">
                        <button data-drawer-target="default-sidebar" data-drawer-toggle="default-sidebar" aria-controls="default-sidebar" type="button" class="sm:hidden inline-flex items-center p-2 mr-3 text-sm text-gray-700 rounded-lg bg-gray-100 dark:bg-gray-700 dark:text-gray-300 shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="sr-only">メニューを開く</span>
                            <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                            </svg>
                        </button>
                        {{ $header }}
                    </div>
                </header>

                <!-- ヘッダーの高さ分のスペーサー（モバイル表示時のみ） -->
                <div class="sm:hidden h-[70px]"></div>
            @endisset

                <!-- Page Content -->
                <main class="px-6 py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
