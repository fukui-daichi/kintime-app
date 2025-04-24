<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('上長ダッシュボード') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="mb-4">ようこそ、{{ $user->full_name }}さん</p>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="space-y-4">
                        <a href="#" class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                            承認待ち一覧
                        </a>
                        <a href="#" class="block px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition">
                            部下の勤怠確認
                        </a>
                        <a href="#" class="block px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                            レポート作成
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
