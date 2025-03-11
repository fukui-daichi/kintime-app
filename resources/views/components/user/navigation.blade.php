<!-- モバイル表示時のトグルボタン -->
<button data-drawer-target="default-sidebar" data-drawer-toggle="default-sidebar" aria-controls="default-sidebar" type="button" class="inline-flex items-center p-2 mt-2 ml-3 max-h-svh text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
    <span class="sr-only">メニューを開く</span>
    <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
    </svg>
</button>

<!-- サイドバーナビゲーション -->
<aside id="default-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
    <div class="overflow-y-auto py-5 px-3 h-full bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700 transition-colors duration-300">
        <!-- ロゴ -->
        <div class="flex items-center mb-6">
            <a href="/" class="flex items-center text-2xl font-semibold text-gray-900 dark:text-white">
                <span class="self-center text-xl font-semibold whitespace-nowrap">{{ config('app.name', 'Kintime') }}</span>
            </a>
        </div>

        <!-- ユーザー情報 -->
        <div class="flex items-center p-2 mb-6 text-base font-normal text-gray-900 rounded-lg dark:text-white">
            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white">
                {{ substr(Auth::user()->first_name, 0, 1) }}
            </div>
            <div class="ml-3 text-sm font-medium">
                {{ Auth::user()->last_name }} {{ Auth::user()->first_name }}
            </div>
        </div>

        <!-- メインメニュー -->
        <ul class="space-y-2">
            <li>
                <a href="{{ url('/') }}" class="flex items-center p-2 text-base font-normal {{ request()->is('/') ? 'text-white bg-primary-600' : 'text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg group">
                    <svg aria-hidden="true" class="w-6 h-6 {{ request()->is('/') ? 'text-white' : 'text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white' }} transition duration-75" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                        <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                    </svg>
                    <span class="ml-3">マイページ</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/timecard') }}" class="flex items-center p-2 text-base font-normal {{ request()->is('timecard*') ? 'text-white bg-primary-600' : 'text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg group">
                    <svg aria-hidden="true" class="w-6 h-6 {{ request()->is('timecard*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white' }} transition duration-75" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-3">勤怠一覧</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/requests') }}" class="flex items-center p-2 text-base font-normal {{ request()->is('requests*') ? 'text-white bg-primary-600' : 'text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg group">
                    <svg aria-hidden="true" class="w-6 h-6 {{ request()->is('requests*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white' }} transition duration-75" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm0 2h10v7h-2l-1 2H8l-1-2H5V5z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="flex-1 ml-3 whitespace-nowrap">申請一覧</span>
                </a>
            </li>
        </ul>

        <!-- セカンダリメニュー -->
        <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
            <li>
                <a href="{{ route('profile.edit') }}" class="flex items-center p-2 text-base font-normal {{ request()->routeIs('profile.edit') ? 'text-white bg-primary-600' : 'text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg group">
                    <svg aria-hidden="true" class="w-6 h-6 {{ request()->routeIs('profile.edit') ? 'text-white' : 'text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white' }} transition duration-75" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="flex-1 ml-3 whitespace-nowrap">プロフィール設定</span>
                </a>
            </li>
            <li>
                <form method="POST" action="{{ route('logout') }}" class="flex items-center">
                    @csrf
                    <button type="submit" class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                        <svg aria-hidden="true" class="flex-shrink-0 w-6 h-6 text-gray-400 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm9 4a1 1 0 11-2 0 1 1 0 012 0zm-3 3a1 1 0 100 2h4a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-3">ログアウト</span>
                    </button>
                </form>
            </li>
        </ul>

        <!-- テーマカラー切り替え -->
        <div class="absolute bottom-0 left-0 justify-center p-4 space-x-4 w-full flex bg-white dark:bg-gray-800 z-20 border-r border-gray-200 dark:border-gray-700 transition-colors duration-300">
            <button type="button" onclick="toggleDarkMode()" class="inline-flex justify-center p-2 text-gray-500 rounded cursor-pointer dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-600">
                <!-- ダークモードアイコン（ライトモード時に表示） -->
                <svg id="theme-toggle-dark-icon" class="w-6 h-6 block dark:hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                </svg>
                <!-- ライトモードアイコン（ダークモード時に表示） -->
                <svg id="theme-toggle-light-icon" class="w-6 h-6 hidden dark:block" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </div>
</aside>
