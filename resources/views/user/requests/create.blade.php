<x-user-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            勤怠修正申請
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- 現在の勤怠情報 --}}
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">現在の勤怠情報</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="grid grid-cols-4 gap-4">
                                {{-- 日付 --}}
                                <div class="col-span-4">
                                    <span class="text-sm text-gray-500">日付</span>
                                    <p class="text-gray-900 text-lg font-medium">
                                        {{ $currentTimecard['date'] }}
                                    </p>
                                </div>

                                {{-- 勤怠情報 --}}
                                <div>
                                    <span class="text-sm text-gray-500">出勤時刻</span>
                                    <p class="text-gray-900">{{ $currentTimecard['clock_in'] }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">退勤時刻</span>
                                    <p class="text-gray-900">{{ $currentTimecard['clock_out'] }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">休憩時間</span>
                                    <p class="text-gray-900">{{ $currentTimecard['break_time'] }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">実労働時間</span>
                                    <p class="text-gray-900">{{ $currentTimecard['actual_work_time'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- エラーメッセージの表示 --}}
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- 申請フォーム --}}
                    <form method="POST" action="{{ route('requests.store') }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="timecard_id" value="{{ $formData['timecard_id'] }}">

                        {{-- 申請種別の選択 --}}
                        <div>
                            <label for="request_type" class="block text-sm font-medium text-gray-700">申請種別</label>
                            <select id="request_type" name="request_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    onchange="toggleInputFields()">
                                @foreach($requestTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('request_type') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 勤怠修正用フィールド --}}
                        <div id="timecard_fields" class="{{ old('request_type') === 'paid_vacation' ? 'hidden' : '' }}">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                {{-- 出勤時刻 --}}
                                <div>
                                    <label for="after_clock_in" class="block text-sm font-medium text-gray-700">
                                        修正後の出勤時刻
                                        <span class="text-sm text-gray-500">
                                            （現在：{{ $formattedTimecard['clock_in'] }}）
                                        </span>
                                    </label>
                                    <input type="time"
                                        id="after_clock_in"
                                        name="after_clock_in"
                                        value="{{ old('after_clock_in') ?? $formData['clock_in'] }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                {{-- 退勤時刻 --}}
                                <div>
                                    <label for="after_clock_out" class="block text-sm font-medium text-gray-700">
                                        修正後の退勤時刻
                                        <span class="text-sm text-gray-500">
                                            （現在：{{ $formattedTimecard['clock_out'] }}）
                                        </span>
                                    </label>
                                    <input type="time"
                                        id="after_clock_out"
                                        name="after_clock_out"
                                        value="{{ old('after_clock_out') ?? $formData['clock_out'] }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                {{-- 休憩時間 --}}
                                <div>
                                    <label for="after_break_time" class="block text-sm font-medium text-gray-700">
                                        修正後の休憩時間
                                        <span class="text-sm text-gray-500">
                                            （現在：{{ $formattedTimecard['break_time'] }}）
                                        </span>
                                    </label>
                                    <input type="time"
                                        id="after_break_time"
                                        name="after_break_time"
                                        value="{{ old('after_break_time') ?? $formData['break_time'] }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        {{-- 有給休暇申請用フィールド --}}
                        <div id="vacation_fields" class="{{ old('request_type') !== 'paid_vacation' ? 'hidden' : '' }}">
                            <div>
                                <label for="vacation_type" class="block text-sm font-medium text-gray-700">休暇種別</label>
                                <select id="vacation_type"
                                        name="vacation_type"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($vacationTypes as $value => $label)
                                        <option value="{{ $value }}" {{ old('vacation_type') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- 申請理由 --}}
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">申請理由</label>
                            <textarea id="reason"
                                    name="reason"
                                    rows="3"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="修正理由を入力してください">{{ old('reason') }}</textarea>
                        </div>

                        {{-- 送信ボタン --}}
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('timecard.index') }}"
                            class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                キャンセル
                            </a>
                            <button type="submit"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                申請する
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- 入力フィールドの切り替え用JavaScript --}}
    <script>
        function toggleInputFields() {
            const requestType = document.getElementById('request_type').value;
            const timecardFields = document.getElementById('timecard_fields');
            const vacationFields = document.getElementById('vacation_fields');

            if (requestType === 'timecard') {
                timecardFields.classList.remove('hidden');
                vacationFields.classList.add('hidden');
            } else {
                timecardFields.classList.add('hidden');
                vacationFields.classList.remove('hidden');
            }
        }

        // 初期表示時にも実行
        document.addEventListener('DOMContentLoaded', function() {
            toggleInputFields();
        });
    </script>
</x-user-layout>
