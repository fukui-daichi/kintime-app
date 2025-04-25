<x-app-layout>
    <div class="flex min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- サイドバー（上長用） -->
        <aside x-data="{ open: true }" class="fixed inset-y-0 left-0 z-30 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col h-screen transition-transform duration-200"
            :class="{ '-translate-x-full': !open, 'translate-x-0': open }">
            <div class="flex items-center h-16 px-4 border-b border-gray-100 dark:border-gray-700">
                <a href="{{ route('dashboard') }}">
                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                </a>
                <button class="ml-auto sm:hidden text-gray-500 dark:text-gray-400" @click="open = !open">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="flex-1 px-2 py-4 space-y-2 overflow-y-auto">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-nav-link>
                <x-nav-link href="#" class="pointer-events-none opacity-50">
                    承認待ち一覧（ダミー）
                </x-nav-link>
                <x-nav-link href="#" class="pointer-events-none opacity-50">
                    部下の勤怠確認（ダミー）
                </x-nav-link>
                <x-nav-link href="#" class="pointer-events-none opacity-50">
                    レポート作成（ダミー）
                </x-nav-link>
                <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                    {{ __('Profile') }}
                </x-nav-link>
            </nav>
            <div class="px-4 py-4 border-t border-gray-100 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div>
                        <div class="font-medium text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-gray-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf
                    <x-primary-button class="w-full" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-primary-button>
                </form>
            </div>
        </aside>
        <!-- メインコンテンツ -->
        <div class="flex-1 ml-64">
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ __('上長ダッシュボード') }}
                    </h2>
                </div>
            </header>
            <main>
                <div class="py-12">
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900 dark:text-gray-100">
                                <p class="mb-4">ようこそ、{{ $user->full_name }}さん</p>
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
            </main>
        </div>
    </div>
</x-app-layout>
