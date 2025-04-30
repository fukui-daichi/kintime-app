<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">打刻修正申請詳細</h1>
    </x-slot>

    <div class="py-6 px-4 max-w-7xl mx-auto">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">基本情報</h2>
                    <dl class="space-y-4">
                        <div class="flex items-start">
                            <dt class="w-1/3 text-sm font-medium text-gray-500 dark:text-gray-400">申請日</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $request->created_at->format('Y-m-d H:i') }}</dd>
                        </div>
                        <div class="flex items-start">
                            <dt class="w-1/3 text-sm font-medium text-gray-500 dark:text-gray-400">修正種別</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">
                                @switch($request->correction_type)
                                    @case('clock_in') 出勤時間 @break
                                    @case('clock_out') 退勤時間 @break
                                    @case('break_start') 休憩開始時間 @break
                                    @case('break_end') 休憩終了時間 @break
                                @endswitch
                            </dd>
                        </div>
                        <div class="flex items-start">
                            <dt class="w-1/3 text-sm font-medium text-gray-500 dark:text-gray-400">ステータス</dt>
                            <dd class="text-sm">
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
                            </dd>
                        </div>
                        @if($request->approver)
                        <div class="flex items-start">
                            <dt class="w-1/3 text-sm font-medium text-gray-500 dark:text-gray-400">承認者</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $request->approver->name }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">時間比較</h2>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"></th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">元の時間</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">修正後時間</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">時間</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $request->original_time->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $request->corrected_time->format('Y-m-d H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">差分</td>
                                <td colspan="2" class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $request->corrected_time->diffForHumans($request->original_time, ['parts' => 2, 'short' => true]) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">修正理由</h2>
                <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $request->reason }}</p>
            </div>
        </div>

        @if($request->status === 'pending' && app(\App\Services\TimecardUpdateRequestService::class)->canApprove(auth()->user(), $request))
        <div class="flex justify-end">
            <form action="{{ route('timecard-update-requests.approve', $request) }}" method="POST">
                @csrf
                <button type="submit" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-500 dark:hover:bg-green-600 focus:outline-none dark:focus:ring-green-800">
                    承認する
                </button>
            </form>
        </div>
        @endif

        <div class="mt-4">
            <a href="{{ route('timecard-update-requests.index') }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                ← 一覧に戻る
            </a>
        </div>
    </div>
</x-app-layout>
