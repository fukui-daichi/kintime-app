<x-user-layout>
    {{-- フラッシュメッセージ --}}
    @if (session('success'))
        <x-common.flash-message
            type="success"
            :message="session('success')"
            class="mb-4"
        />
    @endif

    @if (session('error'))
        <x-common.flash-message
            type="error"
            :message="session('error')"
            class="mb-4"
        />
    @endif

    {{-- 上部グリッド - 概要情報 --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- 現在日時カード --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">日付</h3>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ now()->format('Y年m月d日') }}</p>
            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-2" id="currentTime">
                {{ now()->format('H:i:s') }}
            </p>
        </div>

        {{-- 勤務状況カード --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">勤務状況</h3>
            @if ($timecard)
                @if ($timecardData['clockInTime'] && !$timecardData['clockOutTime'])
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">勤務中</p>
                @elseif ($timecardData['clockInTime'] && $timecardData['clockOutTime'])
                    <p class="text-lg font-bold text-gray-600 dark:text-gray-400">退勤済み</p>
                @else
                    <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">未出勤</p>
                @endif
            @else
                <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">未出勤</p>
            @endif
            <div class="mt-2">
                @if ($timecard && $timecardData['workTime'])
                    <p class="text-sm">今日の勤務時間: <span class="font-medium">{{ $timecardData['workTime'] }}</span></p>
                @endif
            </div>
        </div>

        {{-- 打刻ボタンカード --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm sm:col-span-2">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">勤怠打刻</h3>
            <div class="flex justify-center space-x-6 mt-2">
                <form action="{{ route('timecard.clockIn') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 rounded-lg {{ !$canClockIn ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ !$canClockIn ? 'disabled' : '' }}>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        出勤
                    </button>
                </form>
                <form action="{{ route('timecard.clockOut') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 rounded-lg {{ !$canClockOut ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ !$canClockOut ? 'disabled' : '' }}>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        退勤
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- 本日の勤怠状況 (メインカード) --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm mb-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">本日の勤怠状況</h3>

        @if ($timecard)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">出勤時刻</h4>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $timecardData['clockInTime'] ?? '未打刻' }}
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">退勤時刻</h4>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $timecardData['clockOutTime'] ?? '未打刻' }}
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">実労働時間</h4>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $timecardData['workTime'] ?? '計算中...' }}
                    </p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">残業時間</h4>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $timecardData['overtime'] ?? '0:00' }}
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">深夜勤務時間</h4>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $timecardData['nightWorkTime'] ?? '0:00' }}
                    </p>
                </div>
            </div>
        @else
            <div class="bg-gray-50 dark:bg-gray-700 p-8 rounded-lg text-center">
                <p class="text-gray-500 dark:text-gray-400">本日の勤怠記録はまだありません</p>
                <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">出勤ボタンを押して勤務を開始してください</p>
            </div>
        @endif
    </div>

    {{-- その他 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- 今週の勤務時間 --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">今週の勤務状況</h3>
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg text-center">
                <p class="text-gray-500 dark:text-gray-400">この機能は近日公開予定です</p>
            </div>
        </div>

        {{-- 申請中の勤怠 --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">申請中の勤怠</h3>
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg text-center">
                <p class="text-gray-500 dark:text-gray-400">申請中の勤怠はありません</p>
            </div>
        </div>
    </div>

    {{-- 現在時刻を更新するためのJavaScript --}}
    <script>
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('ja-JP');
            document.getElementById('currentTime').textContent = timeString;
        }

        // 1秒ごとに時刻を更新
        setInterval(updateTime, 1000);
    </script>
</x-user-layout>
