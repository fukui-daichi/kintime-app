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
                    <form method="POST" action="{{ route("requests.store") }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

                        {{-- 現在の勤怠情報の表示 --}}
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">現在の勤怠情報</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">出勤時刻</p>
                                    <p class="text-base">{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format("H:i") : "-" }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">退勤時刻</p>
                                    <p class="text-base">{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format("H:i") : "-" }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">休憩時間</p>
                                    <p class="text-base">{{ number_format($attendance->break_time / 60, 2) }}時間</p>
                                </div>
                            </div>
                        </div>

                        {{-- 申請種別の選択 --}}
                        <div>
                            <label for="request_type" class="block text-sm font-medium text-gray-700">申請種別</label>
                            <select id="request_type" name="request_type"
                                class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500"
                                onchange="toggleInputFields(this.value)">
                                <option value="">選択してください</option>
                                <option value="time_correction" {{ old("request_type") === "time_correction" ? "selected" : "" }}>
                                    時刻修正
                                </option>
                                <option value="break_time_modification" {{ old("request_type") === "break_time_modification" ? "selected" : "" }}>
                                    休憩時間修正
                                </option>
                            </select>
                            @error("request_type")
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 時刻修正用フィールド --}}
                        <div id="time_correction_fields" class="space-y-4 hidden">
                            <div>
                                <label for="after_clock_in" class="block text-sm font-medium text-gray-700">
                                    修正後出勤時刻
                                </label>
                                <input type="time" id="after_clock_in" name="after_clock_in"
                                    value="{{ old("after_clock_in") }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500">
                                @error("after_clock_in")
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="after_clock_out" class="block text-sm font-medium text-gray-700">
                                    修正後退勤時刻
                                </label>
                                <input type="time" id="after_clock_out" name="after_clock_out"
                                    value="{{ old("after_clock_out") }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500">
                                @error("after_clock_out")
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- 休憩時間修正用フィールド --}}
                        <div id="break_time_fields" class="space-y-4 hidden">
                            <div>
                                <label for="after_break_hours" class="block text-sm font-medium text-gray-700">
                                    修正後休憩時間（時間）
                                </label>
                                <input type="number" id="after_break_hours" name="after_break_hours"
                                    value="{{ old("after_break_hours") }}" step="0.25" min="0" max="5"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500">
                                @error("after_break_hours")
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- 申請理由 --}}
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">申請理由</label>
                            <textarea id="reason" name="reason" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500"
                                placeholder="修正が必要な理由を入力してください">{{ old("reason") }}</textarea>
                            @error("reason")
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ボタン群 --}}
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route("attendance.monthly") }}"
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                                キャンセル
                            </a>
                            <button type="submit"
                                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                                申請する
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- 入力フィールドの表示切り替え用JavaScript --}}
    <script>
        function toggleInputFields(requestType) {
            const timeCorrectionFields = document.getElementById("time_correction_fields");
            const breakTimeFields = document.getElementById("break_time_fields");

            if (requestType === "time_correction") {
                timeCorrectionFields.classList.remove("hidden");
                breakTimeFields.classList.add("hidden");
            } else if (requestType === "break_time_modification") {
                timeCorrectionFields.classList.add("hidden");
                breakTimeFields.classList.remove("hidden");
            } else {
                timeCorrectionFields.classList.add("hidden");
                breakTimeFields.classList.add("hidden");
            }
        }

        // ページ読み込み時に初期値に応じて表示を設定
        document.addEventListener("DOMContentLoaded", function() {
            const requestType = document.getElementById("request_type").value;
            toggleInputFields(requestType);
        });
    </script>
</x-user-layout>
