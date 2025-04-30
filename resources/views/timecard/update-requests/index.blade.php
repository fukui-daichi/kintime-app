<x-app-layout>
    <x-header :user="$user" />
    <x-user.sidebar />

    <main class="p-6 md:ml-64 min-h-screen h-auto pt-20 bg-white dark:bg-gray-800">
        <div class="mx-auto max-w-screen-xl">
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
                <div class="mt-4 overflow-x-auto">
                    <div class="min-w-[1080px] max-h-[80vh]">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="sticky top-0 z-10 text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">申請日</th>
                                    <th class="px-4 py-3">修正前</th>
                                    <th class="px-4 py-3">修正後</th>
                                    <th class="px-4 py-3">理由</th>
                                    <th class="px-4 py-3">ステータス</th>
                                    <th class="px-4 py-3">承認者</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="px-4 py-3">{{ $request['created_at'] }}</td>
                                        <td class="px-4 py-3">
                                            @foreach ($request['before'] as $label => $value)
                                                {{ $label }}: {{ $value }}<br>
                                            @endforeach
                                        </td>
                                        <td class="px-4 py-3">
                                            @foreach ($request['after'] as $label => $value)
                                                {{ $label }}: {{ $value }}<br>
                                            @endforeach
                                        </td>
                                        <td class="px-4 py-3">{{ $request['reason'] }}</td>
                                        <td class="px-4 py-3">
                                            <span @class([
                                                'px-2 py-1 rounded-full text-xs',
                                                'bg-yellow-100 text-yellow-800' => $request['status'] === 'pending',
                                                'bg-green-100 text-green-800' => $request['status'] === 'approved',
                                                'bg-red-100 text-red-800' => $request['status'] === 'rejected',
                                            ])>
                                                @switch($request['status'])
                                                    @case('pending') 承認待ち @break
                                                    @case('approved') 承認済み @break
                                                    @case('rejected') 却下 @break
                                                @endswitch
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">{{ $request['approver_name'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-app-layout>
