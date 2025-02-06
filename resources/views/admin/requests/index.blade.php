<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            申請一覧（管理者）
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- ステータスフィルター --}}
            <div class="mb-6 flex space-x-4">
                @foreach ($statusList as $key => $label)
                    <a href="{{ route('requests.index', ['status' => $key]) }}"
                        class="px-4 py-2 rounded-md {{ $currentStatus === $key
                            ? 'bg-blue-500 text-white'
                            : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- フラッシュメッセージ --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        申請日時
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        申請者
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        対象日
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        申請種別
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        現在の打刻
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        申請した打刻
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        状態
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($requests as $request)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $request['created_at'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $request['user']['name'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $request['timecard_date'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $request['request_type'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            @if($request['current_time']['type'] === 'time')
                                                <div>出勤：{{ $request['current_time']['data']['clock_in'] }}</div>
                                                <div>退勤：{{ $request['current_time']['data']['clock_out'] }}</div>
                                            @else
                                                <div>休憩時間：{{ $request['current_time']['data']['break_time'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            @if($request['requested_time']['type'] === 'time')
                                                <div>出勤：{{ $request['requested_time']['data']['clock_in'] }}</div>
                                                <div>退勤：{{ $request['requested_time']['data']['clock_out'] }}</div>
                                            @else
                                                <div>休憩時間：{{ $request['requested_time']['data']['break_time'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $request['status']['class'] ?? '' }}">
                                                {{ $request['status']['label'] ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if(($request['status']['label'] ?? '') === '承認待ち')
                                                <div class="flex space-x-2">
                                                    <form action="{{ route('requests.approve', ['approvalRequest' => $request['id']]) }}"
                                                          method="POST"
                                                          class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                            onclick="return confirm('この申請を承認してよろしいですか？')"
                                                            class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
                                                            承認
                                                        </button>
                                                    </form>

                                                    <form action="{{ route('requests.reject', ['approvalRequest' => $request['id']]) }}"
                                                          method="POST"
                                                          class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                            onclick="return confirm('この申請を否認してよろしいですか？')"
                                                            class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                                                            否認
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            申請がありません
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- ページネーション --}}
                    <div class="mt-4">
                        {{ $paginator->appends(['status' => $currentStatus])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
