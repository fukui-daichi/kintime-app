<x-user-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">勤怠管理</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- フラッシュメッセージ --}}
                    @if (session('success'))
                        <x-common.flash-message
                            type="success"
                            :message="session('success')"
                        />
                    @endif

                    @if (session('error'))
                        <x-common.flash-message
                            type="error"
                            :message="session('error')"
                        />
                    @endif
                    <div class="text-center">
                        {{-- 現在日時の表示 --}}
                        <div class="text-2xl mb-4">{{ now()->format('Y年m月d日') }}</div>
                        <div class="text-xl mb-8" id="currentTime">
                            {{ now()->format('H:i:s') }}
                        </div>

                        {{-- 打刻ボタン --}}
                        <div class="space-x-4">
                            <form action="{{ route('attendance.clockIn') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded
                                        {{ !$canClockIn ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    {{ !$canClockIn ? 'disabled' : '' }}>
                                    出勤
                                </button>
                            </form>

                            <form action="{{ route('attendance.clockOut') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded
                                        {{ !$canClockOut ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    {{ !$canClockOut ? 'disabled' : '' }}>
                                    退勤
                                </button>
                            </form>
                        </div>

                        {{-- 本日の勤怠状況 --}}
                        @if ($attendance)
                            <div class="mt-8 text-left max-w-xl mx-auto">
                                <h3 class="text-lg font-semibold mb-4">本日の勤怠状況</h3>
                                <div class="space-y-2">
                                    <p>出勤時刻：{{ $attendanceData['clockInTime'] }}</p>
                                    <p>退勤時刻：{{ $attendanceData['clockOutTime'] }}</p>
                                    @if ($attendanceData['workTime'] !== null)
                                        <p>実働時間：{{ $attendanceData['workTime'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
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
