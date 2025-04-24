<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- 姓・名 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="last_name" :value="__('姓')" />
                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name')" required autocomplete="family-name" autofocus />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="first_name" :value="__('名')" />
                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name')" required autocomplete="given-name" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>
        </div>

        <!-- 社員番号 -->
        <div class="mt-4">
            <x-input-label for="employee_number" :value="__('社員番号')" />
            <x-text-input id="employee_number" name="employee_number" type="text" class="mt-1 block w-full" :value="old('employee_number')" required />
            <x-input-error :messages="$errors->get('employee_number')" class="mt-2" />
        </div>

        <!-- 部署 -->
        <div class="mt-4">
            <x-input-label for="department_id" :value="__('部署')" />
            <select id="department_id" name="department_id" class="mt-1 block w-full" required>
                <option value="">選択してください</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
        </div>

        <!-- 雇用形態 -->
        <div class="mt-4">
            <x-input-label for="employment_type" :value="__('雇用形態')" />
            <select id="employment_type" name="employment_type" class="mt-1 block w-full" required>
                <option value="">選択してください</option>
                @foreach($employmentTypes as $type)
                    <option value="{{ $type }}" @selected(old('employment_type') == $type)>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('employment_type')" class="mt-2" />
        </div>

        <!-- 入社日・退社日 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="joined_at" :value="__('入社日')" />
                <x-text-input id="joined_at" name="joined_at" type="date" class="mt-1 block w-full" :value="old('joined_at')" required />
                <x-input-error :messages="$errors->get('joined_at')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="leaved_at" :value="__('退社日（任意）')" />
                <x-text-input id="leaved_at" name="leaved_at" type="date" class="mt-1 block w-full" :value="old('leaved_at')" />
                <x-input-error :messages="$errors->get('leaved_at')" class="mt-2" />
            </div>
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
