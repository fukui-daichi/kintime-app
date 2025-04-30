<x-app-layout>
    <x-header :user="$user" />
    <x-user.sidebar />

    <main class="p-6 md:ml-64 min-h-screen pt-20 bg-white dark:bg-gray-800">
        <div class="max-w-lg mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg p-8">
            <h2 class="text-xl font-bold mb-6">勤怠修正申請</h2>
            <form method="POST" action="{{ route('timecard-update-requests.store') }}">
                @csrf
                <input type="hidden" name="timecard_id" value="{{ $timecard->id }}">
                <input type="hidden" name="date" value="{{ $formData['date_iso'] }}">
                <div class="mb-4">
                    <label class="block mb-1 font-semibold">日付</label>
                    <input type="text" value="{{ $formData['date_formatted'] }}" readonly class="form-input bg-gray-100 dark:bg-gray-700 w-full" />
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold" for="clock_in">出勤時間 <span class="text-red-500">*</span></label>
                    <input type="time" id="clock_in" name="clock_in" value="{{ old('clock_in', $formData['clock_in']) }}" required class="form-input w-full" />
                    @error('clock_in') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold" for="clock_out">退勤時間 <span class="text-red-500">*</span></label>
                    <input type="time" id="clock_out" name="clock_out" value="{{ old('clock_out', $formData['clock_out']) }}" required class="form-input w-full" />
                    @error('clock_out') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold" for="break_start">休憩開始 <span class="text-red-500">*</span></label>
                    <input type="time" id="break_start" name="break_start" value="{{ old('break_start', $formData['break_start']) }}" required class="form-input w-full" />
                    @error('break_start') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold" for="break_end">休憩終了 <span class="text-red-500">*</span></label>
                    <input type="time" id="break_end" name="break_end" value="{{ old('break_end', $formData['break_end']) }}" required class="form-input w-full" />
                    @error('break_end') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold" for="reason">申請理由 <span class="text-red-500">*</span></label>
                    <textarea id="reason" name="reason" required class="form-textarea w-full" rows="3">{{ old('reason') }}</textarea>
                    @error('reason') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 w-full">申請する</button>
            </form>
        </div>
    </main>
</x-app-layout>
