<x-app-layout>
    <x-header :user="$user" />
    <x-manager.sidebar />

    <x-main-content>
        <div class="max-w-screen-xl mx-auto p-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-6 text-gray-800 dark:text-gray-200">
                    {{ $timecard->date }} の勤怠編集
                </h2>

                <form method="POST" action="{{ route('timecard.update', $timecard) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 出勤時間 -->
                        <div>
                            <x-input-label for="clock_in" value="出勤時間" />
                            <x-text-input id="clock_in" name="clock_in" type="time"
                                value="{{ old('clock_in', $timecard->clock_in) }}"
                                class="mt-1 block w-full" />
                        </div>

                        <!-- 退勤時間 -->
                        <div>
                            <x-input-label for="clock_out" value="退勤時間" />
                            <x-text-input id="clock_out" name="clock_out" type="time"
                                value="{{ old('clock_out', $timecard->clock_out) }}"
                                class="mt-1 block w-full" />
                        </div>

                        <!-- 休憩時間 -->
                        <div>
                            <x-input-label for="break_time" value="休憩時間（分）" />
                            <x-text-input id="break_time" name="break_time" type="number"
                                value="{{ old('break_time', $timecard->break_time) }}"
                                class="mt-1 block w-full" />
                        </div>

                    </div>

                    <div class="flex justify-end gap-4 mt-8">
                        <x-secondary-button tag="a"
                            href="{{ route('timecard.index', ['year' => $year, 'month' => $month]) }}">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            更新
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </x-main-content>
</x-app-layout>
