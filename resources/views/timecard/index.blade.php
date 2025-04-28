<x-app-layout>
    <x-header :user="$user" />
    <x-user.sidebar />

    <main class="p-4 md:ml-64 h-auto pt-20 bg-white dark:bg-gray-800">
        <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                    <div class="w-full md:w-1/2">
                        <form method="get" action="{{ route('timecard.index') }}" class="flex items-center">
                            <label for="month" class="sr-only">月選択</label>
                            <input type="month" id="month" name="month"
                                value="{{ sprintf('%04d-%02d', $year, $month) }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                onchange="this.form.submit()">
                        </form>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">日付</th>
                                <th class="px-4 py-3">出勤時間</th>
                                <th class="px-4 py-3">退勤時間</th>
                                <th class="px-4 py-3">休憩時間</th>
                                <th class="px-4 py-3">実働時間</th>
                                <th class="px-4 py-3">残業時間</th>
                                <th class="px-4 py-3">深夜残業時間</th>
                                <th class="px-4 py-3">状態</th>
                                <th class="px-4 py-3">申請</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($timecards as $timecard)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3">{{ $timecard['date'] }}</td>
                                    <td class="px-4 py-3">{{ $timecard['clock_in'] }}</td>
                                    <td class="px-4 py-3">{{ $timecard['clock_out'] }}</td>
                                    <td class="px-4 py-3">{{ $timecard['break_time'] }}</td>
                                    <td class="px-4 py-3">{{ $timecard['work_time'] }}</td>
                                    <td class="px-4 py-3">{{ $timecard['overtime'] }}</td>
                                    <td class="px-4 py-3">{{ $timecard['night_work'] }}</td>
                                    <td class="px-4 py-3">{{ $timecard['status'] }}</td>
                                    <td class="px-4 py-3">
                                        <button type="button" class="px-2 py-1 text-xs rounded bg-blue-500 text-white opacity-70 cursor-not-allowed" disabled>
                                            申請
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-3 text-center">勤怠記録がありません</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</x-app-layout>
