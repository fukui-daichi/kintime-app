<x-app-layout>
    <x-header :user="$user" />
    <x-user.sidebar />

    <x-main-content>
        <div class="max-w-7xl space-y-16">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                基本情報
                            </h2>
                        </header>

                        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                            @csrf
                        </form>

                        <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" id="profile-update-form">
                            @csrf
                            @method('patch')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="last_name" :value="__('姓')" />
                                    <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
                                    <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                                </div>
                                <div>
                                    <x-input-label for="first_name" :value="__('名')" />
                                    <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $user->first_name)" required autocomplete="given-name" />
                                    <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="employee_number" :value="__('社員番号')" />
                                <x-text-input id="employee_number" name="employee_number" type="text" class="mt-1 block w-full" :value="old('employee_number', $user->employee_number)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('employee_number')" />
                            </div>

                            <div>
                                <x-input-label for="department_id" :value="__('部署')" />
                                <x-select id="department_id" name="department_id">
                                    <option value="">選択してください</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" @selected(old('department_id', $user->department_id) == $department->id)>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </x-select>
                                <x-input-error class="mt-2" :messages="$errors->get('department_id')" />
                            </div>

                            <div>
                                <x-input-label for="employment_type" :value="__('雇用形態')" />
                                <x-select id="employment_type" name="employment_type">
                                    <option value="">選択してください</option>
                                    @foreach($employmentTypes as $type)
                                        <option value="{{ $type }}" @selected(old('employment_type', $user->employment_type) == $type)>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </x-select>
                                <x-input-error class="mt-2" :messages="$errors->get('employment_type')" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="joined_at" :value="__('入社日')" />
                                    <x-text-input id="joined_at" name="joined_at" type="date" class="mt-1 block w-full"
                                        :value="old('joined_at', $user->joined_at ? \Carbon\Carbon::parse($user->joined_at)->format('Y-m-d') : '')"
                                        required />
                                    <x-input-error class="mt-2" :messages="$errors->get('joined_at')" />
                                </div>
                                <div>
                                    <x-input-label for="leaved_at" :value="__('退社日（任意）')" />
                                    <x-text-input id="leaved_at" name="leaved_at" type="date" class="mt-1 block w-full"
                                        :value="old('leaved_at', $user->leaved_at ? \Carbon\Carbon::parse($user->leaved_at)->format('Y-m-d') : '')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('leaved_at')" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('メールアドレス')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                    <div>
                                        <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                                            {{ __('Your email address is unverified.') }}

                                            <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                                                {{ __('Click here to re-send the verification email.') }}
                                            </button>
                                        </p>

                                        @if (session('status') === 'verification-link-sent')
                                            <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                                                {{ __('A new verification link has been sent to your email address.') }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>保存</x-primary-button>

                                @if (session('status') === 'profile-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >保存しました</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                パスワード再設定
                            </h2>
                        </header>

                        <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
                            @csrf
                            @method('put')

                            <div>
                                <x-input-label for="update_password_current_password" :value="__('現在のパスワード')" />
                                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="update_password_password" :value="__('新しいパスワード')" />
                                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="update_password_password_confirmation" :value="__('確認用パスワード')" />
                                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>保存</x-primary-button>

                                @if (session('status') === 'password-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >保存しました</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section class="space-y-6">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                アカウント削除
                            </h2>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                アカウントを削除すると、すべてのデータが永久に削除されます。削除する前に、保持したいデータをダウンロードしてください。
                            </p>
                        </header>

                        <x-danger-button
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                        >アカウントを削除</x-danger-button>

                        <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
                            <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                                @csrf
                                @method('delete')

                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    本当にアカウントを削除しますか？
                                </h2>

                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    アカウントを削除すると、すべてのデータが永久に削除されます。削除を実行するにはパスワードを入力してください。
                                </p>

                                <div class="mt-6">
                                    <x-input-label for="password" value="パスワード" class="sr-only" />

                                    <x-text-input
                                        id="password"
                                        name="password"
                                        type="password"
                                        class="mt-1 block w-3/4"
                                        placeholder="パスワード"
                                    />

                                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <x-secondary-button x-on:click="$dispatch('close')">
                                        キャンセル
                                    </x-secondary-button>

                                    <x-danger-button class="ms-3">
                                        アカウントを削除
                                    </x-danger-button>
                                </div>
                            </form>
                        </x-modal>
                    </section>
                </div>
            </div>
        </div>
    </x-main-content>
</x-app-layout>
