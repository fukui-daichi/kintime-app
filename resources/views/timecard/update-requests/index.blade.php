<x-app-layout>
    <x-header :user="$user" />
    <x-user.sidebar />

    <main class="p-6 md:ml-64 min-h-screen h-auto pt-20 bg-white dark:bg-gray-800">
        <div class="mx-auto max-w-screen-xl">
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg">
                <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4">
                    <div class="w-full md:w-1/2">
                        <form method="get" action="{{ route('timecard-update-requests.index') }}" class="flex items-center gap-x-2">
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
                    @if(count($requests) > 0)
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
                    @else
                        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                            {{ $year }}年{{ $month }}月の勤怠修正申請はありません
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>
</x-app-layout>
