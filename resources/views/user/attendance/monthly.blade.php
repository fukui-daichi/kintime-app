<x-user-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">月別勤怠一覧</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- 年月選択部分 --}}
                    <div class="mb-6 flex flex-col sm:flex-row items-center justify-between space-y-4 sm:space-y-0">
                        {{-- 前月リンク --}}
                        <a href="{{ route('attendance.monthly', ['year' => $previousMonth->year, 'month' => $previousMonth->month]) }}"
                            class="text-blue-500 hover:text-blue-700">
                            ← {{ $previousMonth->format('Y年n月') }}
                        </a>

                        {{-- 年月選択フォーム --}}
                        <form method="GET" action="{{ route('attendance.monthly') }}" class="flex items-center space-x-2">
                            <select name="year" class="rounded-md border-gray-300" onchange="this.form.submit()">
                                @foreach ($years as $year)
                                    <option value="{{ $year['value'] }}"
                                        {{ $year['value'] == $targetDate->year ? 'selected' : '' }}
                                        {{ $year['disabled'] ? 'disabled' : '' }}>
                                        {{ $year['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="month" class="rounded-md border-gray-300" onchange="this.form.submit()">
                                @foreach ($months as $month)
                                    <option value="{{ $month['value'] }}"
                                        {{ $month['value'] == $targetDate->month ? 'selected' : '' }}
                                        {{ $month['disabled'] ? 'disabled' : '' }}>
                                        {{ $month['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        {{-- 翌月リンク（現在月より未来は非表示） --}}
                        <div class="w-24 text-right">
                            @if ($showNextMonth)
                                <a href="{{ route('attendance.monthly', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
                                    class="text-blue-500 hover:text-blue-700">
                                    {{ $nextMonth->format('Y年n月') }} →
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- 勤怠一覧テーブル --}}
                    <div class="relative overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        {{-- 固定列 --}}
                                        <th class="sticky left-0 z-10 bg-gray-50 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            日付
                                        </th>
                                        {{-- スクロール可能な列 --}}
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px]">出勤時刻</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px]">退勤時刻</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px]">実働時間</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px]">残業時間</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px]">深夜時間</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="attendance-table-body">
                                    @foreach ($attendances as $data)
                                        <tr class="{{ $data['is_weekend'] ? 'bg-gray-50' : '' }}">
                                            {{-- 固定列 --}}
                                            <td class="sticky left-0 z-10 px-6 py-4 whitespace-nowrap text-sm text-gray-900 {{ $data['is_weekend'] ? 'bg-gray-50' : 'bg-white' }}">
                                                {{ $data['date']->format('n/j') }}
                                                ({{ ['日', '月', '火', '水', '木', '金', '土'][$data['date']->dayOfWeek] }})
                                            </td>
                                            {{-- スクロール可能な列 --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $data['clock_in'] ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $data['clock_out'] ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $data['work_time'] ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $data['overtime'] ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $data['night_work_time'] ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if($data['attendance'])
                                                    <a href="{{ route('requests.create', ['attendance' => $data['attendance']->id]) }}"
                                                       class="inline-flex items-center px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-md hover:bg-blue-600">
                                                        申請
                                                    </a>
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
    </div>
</x-user-layout>
