<x-user-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">月別勤怠一覧</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- 年月選択部分 --}}
                    <div class="mb-6 flex items-center justify-between">
                        {{-- 前月リンク --}}
                        <a href="{{ route('attendance.monthly', ['year' => $previousMonth->year, 'month' => $previousMonth->month]) }}"
                           class="text-blue-500 hover:text-blue-700">
                            ← {{ $previousMonth->format('Y年n月') }}
                        </a>

                        {{-- 年月選択フォーム --}}
                        <form method="GET" action="{{ route('attendance.monthly') }}" class="flex items-center space-x-2">
                            <select name="year" class="rounded-md border-gray-300" onchange="this.form.submit()">
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" {{ $y == $targetDate->year ? 'selected' : '' }}>
                                        {{ $y }}年
                                    </option>
                                @endforeach
                            </select>
                            <select name="month" class="rounded-md border-gray-300" onchange="this.form.submit()">
                                @foreach ($months as $m)
                                    <option value="{{ $m }}" {{ $m == $targetDate->month ? 'selected' : '' }}>
                                        {{ $m }}月
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        {{-- 翌月リンク --}}
                        <a href="{{ route('attendance.monthly', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
                           class="text-blue-500 hover:text-blue-700">
                            {{ $nextMonth->format('Y年n月') }} →
                        </a>
                    </div>

                    {{-- 勤怠一覧テーブル --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日付</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">出勤時刻</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">退勤時刻</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">実働時間</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($attendances as $data)
                                    <tr class="{{ $data['is_weekend'] ? 'bg-gray-50' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $data['date']->format('n/j') }}
                                            ({{ ['日', '月', '火', '水', '木', '金', '土'][$data['date']->dayOfWeek] }})
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $data['clock_in'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $data['clock_out'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if ($data['work_hours'] !== null)
                                                {{ $data['work_hours'] }}時間{{ $data['work_minutes'] }}分
                                            @else
                                                -
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
