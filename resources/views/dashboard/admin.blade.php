<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('管理者ダッシュボード') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="mb-4">ようこそ、{{ $user->full_name }}さん</p>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="space-y-4">
                        <a href="#" class="block px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                            ユーザー管理
                        </a>
                        <a href="#" class="block px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 transition">
                            勤怠データ管理
                        </a>
                        <a href="#" class="block px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition">
                            システム設定
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
