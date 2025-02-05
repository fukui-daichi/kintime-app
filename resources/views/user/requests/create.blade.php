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
                                {{-- 1行目: 日付 --}}
                                <div class="col-span-4">
                                    <span class="text-sm text-gray-500">日付</span>
                                    <p class="text-gray-900 text-lg font-medium">{{ $formattedAttendance['date'] }}</p>
                                </div>

                                {{-- 2行目: 時刻情報 --}}
                                <div>
                                    <span class="text-sm text-gray-500">出勤時刻</span>
                                    <p class="text-gray-900">{{ $formattedAttendance['clock_in'] }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">退勤時刻</span>
                                    <p class="text-gray-900">{{ $formattedAttendance['clock_out'] }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">休憩時間</span>
                                    <p class="text-gray-900">{{ $formattedAttendance['break_time'] }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">実労働時間</span>
                                    <p class="text-gray-900">{{ $formattedAttendance['actual_work_time'] }}</p>
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

                    <form method="POST" action="{{ route('requests.store') }}" class="space-y-6">
                        @csrf
                        {{-- 勤怠IDを隠しフィールドとして送信 --}}
                        <input type="hidden" name="attendance_id" value="{{ $formattedAttendance['id'] }}">

                        {{-- 申請種別の選択 --}}
                        <div>
                            <label for="request_type" class="block text-sm font-medium text-gray-700">申請種別</label>
                            <select id="request_type" name="request_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    onchange="toggleInputFields()">
                                <option value="time_correction">時刻修正</option>
                                <option value="break_time_modification">休憩時間修正</option>
                            </select>
                        </div>

                        {{-- 時刻修正用フィールド --}}
                        <div id="time_correction_fields">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                {{-- 出勤時刻 --}}
                                <div>
                                    <label for="after_clock_in" class="block text-sm font-medium text-gray-700">
                                        修正後の出勤時刻
                                    </label>
                                    <input type="time" id="after_clock_in" name="after_clock_in"
                                           value="{{ old('after_clock_in') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                {{-- 退勤時刻 --}}
                                <div>
                                    <label for="after_clock_out" class="block text-sm font-medium text-gray-700">
                                        修正後の退勤時刻
                                    </label>
                                    <input type="time" id="after_clock_out" name="after_clock_out"
                                           value="{{ old('after_clock_out') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        {{-- 休憩時間修正用フィールド --}}
                        <div id="break_time_fields" class="hidden">
                            <div>
                                <label for="after_break_time" class="block text-sm font-medium text-gray-700">
                                    修正後の休憩時間
                                </label>
                                <input type="time"
                                       id="after_break_time"
                                       name="after_break_time"
                                       value="{{ old('after_break_time', '01:00') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        {{-- 申請理由 --}}
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">申請理由</label>
                            <textarea id="reason" name="reason" rows="3" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('reason') }}</textarea>
                        </div>

                        {{-- 送信ボタン --}}
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('attendance.monthly') }}"
                               class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                キャンセル
                            </a>
                            <button type="submit"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
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
            const timeCorrectionFields = document.getElementById('time_correction_fields');
            const breakTimeFields = document.getElementById('break_time_fields');

            if (requestType === 'time_correction') {
                timeCorrectionFields.classList.remove('hidden');
                breakTimeFields.classList.add('hidden');
            } else {
                timeCorrectionFields.classList.add('hidden');
                breakTimeFields.classList.remove('hidden');
            }
        }
    </script>
</x-user-layout>
