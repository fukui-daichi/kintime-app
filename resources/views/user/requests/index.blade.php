<x-user-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">申請一覧</h2>
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
        {{-- ステータスフィルター --}}
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
            <div class="flex flex-wrap space-x-2">
                @foreach ($statusList as $key => $label)
                    <a href="{{ route('requests.index', ['status' => $key]) }}"
                        class="px-4 py-2 mb-2 rounded-md {{ $currentStatus === $key
                            ? 'bg-blue-500 text-white'
                            : 'bg-white text-gray-700 dark:bg-gray-600 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-500' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- 申請一覧テーブル --}}
        <div class="w-full">
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-collapse">
                        {{-- テーブルヘッダー --}}
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0 z-20">
                            <tr>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">申請日時</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">対象日</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">申請種別</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">現在の打刻</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">申請した打刻</th>
                                <th scope="col" class="px-6 py-3 whitespace-nowrap">状態</th>
                            </tr>
                        </thead>
                        {{-- テーブルボディ --}}
                        <tbody>
                            @forelse ($requests as $request)
                                <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $request['created_at'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $request['timecard_date'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $request['request_type'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        @if($request['current_time']['type'] === 'time')
                                            <div>出勤：{{ $request['current_time']['data']['clock_in'] ?? '-' }}</div>
                                            <div>退勤：{{ $request['current_time']['data']['clock_out'] ?? '-' }}</div>
                                            <div>休憩：{{ $request['current_time']['data']['break_time'] ?? '-' }}</div>
                                        @elseif($request['current_time']['type'] === 'vacation')
                                            <div>休暇種別：{{ $request['current_time']['data']['vacation_type'] ?? '-' }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        @if($request['requested_time']['type'] === 'time')
                                            <div>出勤：{{ $request['requested_time']['data']['clock_in'] ?? '-' }}</div>
                                            <div>退勤：{{ $request['requested_time']['data']['clock_out'] ?? '-' }}</div>
                                            <div>休憩：{{ $request['requested_time']['data']['break_time'] ?? '-' }}</div>
                                        @elseif($request['requested_time']['type'] === 'vacation')
                                            <div>休暇種別：{{ $request['requested_time']['data']['vacation_type'] ?? '-' }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-medium rounded-full {{ $request['status']['class'] }}">
                                            {{ $request['status']['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr class="bg-white dark:bg-gray-800">
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                        申請履歴がありません
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ページネーション --}}
        @if(isset($paginator))
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $paginator->appends(['status' => $currentStatus])->links() }}
            </div>
        @endif
    </div>
</x-user-layout>