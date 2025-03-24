<x-user-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            有給休暇申請
        </h2>
    </x-slot>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            {{-- 日付表示 --}}
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">対象日</h3>
                <p class="text-gray-900 dark:text-white text-lg font-medium bg-gray-100 dark:bg-gray-700 p-3 rounded-lg inline-block">
                    {{ $displayDate }}
                </p>
            </div>

            {{-- エラーメッセージの表示 --}}
            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 dark:bg-red-900/20 dark:border-red-600 dark:text-red-400 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 申請フォーム --}}
            <form method="POST" action="{{ route('requests.store') }}" class="space-y-6">
                @csrf
                {{-- hidden フィールド --}}
                <input type="hidden" name="target_date" value="{{ $targetDate }}">
                <input type="hidden" name="request_type" value="{{ $defaultRequestType }}">

                {{-- 有給休暇種別 --}}
                <div>
                    <label for="vacation_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">休暇種別</label>
                    <select id="vacation_type"
                            name="vacation_type"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($vacationTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('vacation_type') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 申請理由 --}}
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">申請理由</label>
                    <textarea id="reason"
                            name="reason"
                            rows="4"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="申請理由を入力してください">{{ old('reason') }}</textarea>
                </div>

                {{-- 送信ボタン --}}
                <div class="flex justify-end space-x-4 pt-4">
                    <a href="{{ route('timecard.index') }}"
                    class="inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-500">
                        キャンセル
                    </a>
                    <button type="submit"
                            class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        申請する
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-user-layout>