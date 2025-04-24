<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('マイダッシュボード') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="mb-4">ようこそ、{{ $user->full_name }}さん</p>

                    <div class="mt-6 space-y-4">
                        <a href="#" class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                            出勤/退勤
                        </a>
                        <a href="#" class="block px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                            勤怠一覧
                        </a>
                        <a href="#" class="block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                            有給申請
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
