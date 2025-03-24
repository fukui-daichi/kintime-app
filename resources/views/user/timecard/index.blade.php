<x-user-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">勤怠一覧</h2>
    </x-slot>

    {{-- フラッシュメッセージ --}}
    @if (session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 dark:bg-green-800 dark:border-green-700 dark:text-green-100 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 dark:bg-red-800 dark:border-red-700 dark:text-red-100 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-clip">
        {{-- 年月選択フォーム --}}
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ $targetDate->format('Y年m月') }}の勤怠記録
                    </h3>
                </div>
                <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                    <form action="{{ route('timecard.index') }}" method="GET" class="flex items-center space-x-2">
                        <div class="flex space-x-2">
                            <select name="year" class="rounded-lg text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                @foreach($years as $yearOption)
                                    <option value="{{ $yearOption['value'] }}"
                                        {{ $targetDate->year == $yearOption['value'] ? 'selected' : '' }}
                                        {{ $yearOption['disabled'] ? 'disabled' : '' }}>
                                        {{ $yearOption['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="month" class="rounded-lg text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                @foreach($months as $monthOption)
                                    <option value="{{ $monthOption['value'] }}"
                                        {{ $targetDate->month == $monthOption['value'] ? 'selected' : '' }}
                                        {{ $monthOption['disabled'] ? 'disabled' : '' }}>
                                        {{ $monthOption['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:focus:ring-blue-800">表示</button>
                        </div>
                    </form>
                    <div class="flex space-x-2">
                        <a href="{{ route('timecard.index', ['year' => $previousMonth->year, 'month' => $previousMonth->month]) }}"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-600">
                            <span>前月</span>
                        </a>
                        @if($showNextMonth)
                            <a href="{{ route('timecard.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-600">
                                <span>翌月</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- 勤怠テーブル --}}
        <div class="w-full">
            <div class="overflow-x-auto">
                <div class="inline-block min-w-[1000px] w-full">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-collapse">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0 z-20">
                            <tr>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">日付/曜日</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">出勤</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">退勤</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">休憩</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">勤務時間</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">残業</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">深夜</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">状態</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($timecards as $day)
                                <tr class="{{ $day['is_sunday'] ? 'bg-red-50 dark:bg-red-900/20' : ($day['is_saturday'] ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-white dark:bg-gray-800') }}">
                                    <th scope="row" class="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-white">
                                        <div>{{ $day['date']->format('m/d') }}</div>
                                        <div class="{{ $day['is_sunday'] ? 'text-red-500 dark:text-red-400' : ($day['is_saturday'] ? 'text-blue-500 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400') }}">
                                            {{ ['日', '月', '火', '水', '木', '金', '土'][$day['date']->dayOfWeek] }}
                                        </div>
                                    </th>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $day['clock_in'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $day['clock_out'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $day['break_time'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $day['work_time'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $day['overtime'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $day['night_work_time'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($day['status_badge'])
                                            <span class="px-2.5 py-0.5 inline-flex text-xs font-medium rounded-full {{ $day['status_badge']['class'] }}">
                                                {{ $day['status_badge']['text'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($day['can_request'])
                                            @if($day['request_type'] === 'timecard')
                                                <a href="{{ route('requests.timecard.create', ['timecard' => $day['timecard']->id]) }}"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                                    修正申請
                                                </a>
                                            @elseif($day['request_type'] === 'vacation')
                                                <a href="{{ route('requests.paid_vacation.create', ['date' => $day['date']->format('Y-m-d')]) }}"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                                    有給申請
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 月次サマリー --}}
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">出勤日数</h4>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ count(array_filter($timecards->toArray(), function($day) { return $day['timecard'] && !$day['is_weekend']; })) }} 日</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">総労働時間</h4>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        <!-- 実際のデータから計算するロジックが必要 -->
                        -
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">総残業時間</h4>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        <!-- 実際のデータから計算するロジックが必要 -->
                        -
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">申請中の勤怠修正</h4>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        <!-- 実際のデータから計算するロジックが必要 -->
                        0 件
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-user-layout>