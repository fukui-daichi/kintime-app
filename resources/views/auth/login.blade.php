<x-guest-layout>
    <h1 class="mb-6 text-center text-3xl font-bold text-white">Kintime</h1>
    <h2 class="mb-8 text-center text-2xl font-bold text-white">ログイン</h2>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- メールアドレス -->
        <div class="mb-4">
            <label for="email" class="block mb-2 text-sm font-medium text-white">メールアドレス</label>
            <input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 placeholder-gray-400"
                placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- パスワード -->
        <div class="mb-4">
            <label for="password" class="block mb-2 text-sm font-medium text-white">パスワード</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 placeholder-gray-400"
                placeholder="********" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- ログイン情報を記憶 -->
        <div class="flex items-center mb-6">
            <input id="remember_me" type="checkbox" name="remember"
                class="w-4 h-4 text-blue-600 bg-gray-700 border-gray-600 rounded focus:ring-blue-500 focus:ring-2">
            <label for="remember_me" class="ml-2 text-sm font-medium text-gray-300">ログイン情報を記憶</label>
        </div>

        <!-- ログインボタン -->
        <button type="submit"
            class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-base px-5 py-2.5 text-center">
            ログイン
        </button>
    </form>
</x-guest-layout>
