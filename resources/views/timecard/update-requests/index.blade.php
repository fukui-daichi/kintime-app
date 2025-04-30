<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">打刻修正申請一覧</h1>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" class="mb-4">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="py-6 px-4 max-w-7xl mx-auto">
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">申請日</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">修正種別</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ステータス</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($requests as $request)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $request->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                @switch($request->correction_type)
                                    @case('clock_in') 出勤 @break
                                    @case('clock_out') 退勤 @break
                                    @case('break_start') 休憩開始 @break
                                    @case('break_end') 休憩終了 @break
                                @endswitch
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span @class([
                                    'px-2 py-1 rounded-full text-xs',
                                    'bg-yellow-100 text-yellow-800' => $request->status === 'pending',
                                    'bg-green-100 text-green-800' => $request->status === 'approved',
                                    'bg-red-100 text-red-800' => $request->status === 'rejected',
                                ])>
                                    @switch($request->status)
                                        @case('pending') 承認待ち @break
                                        @case('approved') 承認済み @break
                                        @case('rejected') 却下 @break
                                    @endswitch
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <a href="{{ route('timecard-update-requests.show', $request) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
