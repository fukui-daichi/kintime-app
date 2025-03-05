<x-user-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">月別勤怠一覧</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- フラッシュメッセージ --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    {{-- 年月選択フォーム --}}
                    <div class="flex mb-6 items-center">
                        <div class="mr-6">
                            <form action="{{ route('timecard.index') }}" method="GET" class="flex items-center space-x-2">
                                <select name="year" class="rounded-md border-gray-300">
                                    @foreach($years as $yearOption)
                                        <option value="{{ $yearOption['value'] }}"
                                            {{ $targetDate->year == $yearOption['value'] ? 'selected' : '' }}
                                            {{ $yearOption['disabled'] ? 'disabled' : '' }}>
                                            {{ $yearOption['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <select name="month" class="rounded-md border-gray-300">
                                    @foreach($months as $monthOption)
                                        <option value="{{ $monthOption['value'] }}"
                                            {{ $targetDate->month == $monthOption['value'] ? 'selected' : '' }}
                                            {{ $monthOption['disabled'] ? 'disabled' : '' }}>
                                            {{ $monthOption['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="bg-blue-500 text-white p-2 rounded">表示</button>
                            </form>
                        </div>

                        <div class="flex space-x-4">
                            <a href="{{ route('timecard.index', ['year' => $previousMonth->year, 'month' => $previousMonth->month]) }}"
                               class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">
                                前月
                            </a>
                            @if($showNextMonth)
                                <a href="{{ route('timecard.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
                                   class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">
                                    翌月
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- 勤怠テーブル --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日付</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">曜日</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">出勤</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">退勤</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">休憩</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">勤務時間</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">残業</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">深夜</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状態</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($timecards as $day)
                                    <tr class="{{ $day['is_weekend'] ? 'bg-red-50' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $day['date']->format('m/d') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $day['is_weekend'] ? 'text-red-500' : 'text-gray-500' }}">
                                            {{ ['日', '月', '火', '水', '木', '金', '土'][$day['date']->dayOfWeek] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $day['clock_in'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $day['clock_out'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $day['break_time'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $day['work_time'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $day['overtime'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $day['night_work_time'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($day['status_badge'])
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $day['status_badge']['class'] }}">
                                                    {{ $day['status_badge']['text'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($day['can_request'])
                                                @if($day['request_type'] === 'timecard')
                                                    <a href="{{ route('requests.timecard.create', ['timecard' => $day['timecard']->id]) }}"
                                                       class="text-blue-600 hover:text-blue-900">
                                                        修正申請
                                                    </a>
                                                @elseif($day['request_type'] === 'vacation')
                                                    <a href="{{ route('requests.paid_vacation.create', ['date' => $day['date']->format('Y-m-d')]) }}"
                                                       class="text-blue-600 hover:text-blue-900">
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
        </div>
    </div>
</x-user-layout>
