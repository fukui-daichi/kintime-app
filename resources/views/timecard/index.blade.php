<x-app-layout>
    <x-header :user="$user" />
    <x-user.sidebar />

    <main class="p-6 md:ml-64 min-h-screen h-auto pt-20 bg-white dark:bg-gray-800">
        <div class="mx-auto max-w-screen-xl">
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
                <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4">
                    <div class="w-full md:w-1/2">
                        <form method="get" action="{{ route('timecard.index') }}" class="flex items-center space-x-2">
                            <label for="year" class="sr-only">年選択</label>
                            <select name="year" id="year"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-primary-500 focus:border-primary-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                onchange="this.form.submit()">
                                @foreach ($yearOptions as $y)
                                    <option value="{{ $y }}" @if($y == $year) selected @endif>{{ $y }}</option>
                                @endforeach
                            </select>
                            <label for="month" class="sr-only">月選択</label>
                            <select name="month" id="month"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-primary-500 focus:border-primary-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                onchange="this.form.submit()">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @if($m == $month) selected @endif>{{ $m }}</option>
                                @endfor
                            </select>
                        </form>
                    </div>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <div class="min-w-[1080px] max-h-[80vh]">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="sticky top-0 z-10 text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
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
                                    <tr class="border-b dark:border-gray-700 {{ $timecard['day_class'] }}">
                                        <td class="px-4 py-3">{{ $timecard['date'] }}</td>
                                        <td class="px-4 py-3">{{ $timecard['clock_in'] }}</td>
                                        <td class="px-4 py-3">{{ $timecard['clock_out'] }}</td>
                                        <td class="px-4 py-3">{{ $timecard['break_time'] }}</td>
                                        <td class="px-4 py-3">{{ $timecard['work_time'] }}</td>
                                        <td class="px-4 py-3">{{ $timecard['overtime'] }}</td>
                                        <td class="px-4 py-3">{{ $timecard['night_work'] }}</td>
                                        <td class="px-4 py-3">{{ $timecard['status'] }}</td>
                                        <td class="px-4 py-3">
                                            @if(isset($timecard['id']))
                                                @if($timecard['can_apply'])
                                                    <a href="{{ route('timecard-update-requests.create', ['timecard' => $timecard['id']]) }}"
                                                        class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700 transition">
                                                        申請
                                                    </a>
                                                {{-- @else
                                                    <button type="button" class="px-4 py-2 text-sm rounded bg-gray-400 text-white opacity-70 cursor-not-allowed" disabled>
                                                        申請不可
                                                    </button> --}}
                                                @endif
                                            {{-- @else
                                                <button type="button" class="px-4 py-2 text-sm rounded bg-gray-400 text-white opacity-70 cursor-not-allowed" disabled>
                                                    申請不可
                                                </button> --}}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-3 text-center">勤怠記録がありません</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="sticky bottom-0 z-10 bg-gray-100 dark:bg-gray-700 font-bold">
                                <tr>
                                    <td class="px-4 py-3">合計</td>
                                    <td class="px-4 py-3">{{ $totals['days_worked'] }}日</td>
                                    <td class="px-4 py-3">-</td>
                                    <td class="px-4 py-3">-</td>
                                    <td class="px-4 py-3">{{ $totals['total_work'] }}</td>
                                    <td class="px-4 py-3">{{ $totals['total_overtime'] }}</td>
                                    <td class="px-4 py-3">{{ $totals['total_night'] }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-app-layout>
