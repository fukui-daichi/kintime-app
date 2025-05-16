<x-app-layout>
    <!-- Header -->
    <x-header :user="$user" />

    <!-- Sidebar -->
    <x-manager.sidebar />

    <!-- MainContent -->
    <x-main-content>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
          <!-- 現在時刻表示 -->
          <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 bg-white dark:bg-gray-800">
            <h3 class="text-lg font-semibold mb-2 dark:text-white">現在日時</h3>
            <div id="current-date"
                 data-initial-date="{{ $currentDate }}"
                 class="text-lg dark:text-white mb-1">
            </div>
            <div id="current-time" class="text-2xl font-bold dark:text-white"></div>
          </div>

          <!-- 勤怠状態とボタン -->
          <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 bg-white dark:bg-gray-800">
            <h3 class="text-lg font-semibold mb-2 dark:text-white">勤怠状態</h3>
            <div class="mb-2 dark:text-white">状態: <span class="font-bold dark:text-white">退勤中</span></div>
            <div class="flex space-x-2">
              <x-timecard.clock-in-form
                  :disabled="$timecardButtonStatus['clockIn']['disabled']"
                  :label="$timecardButtonStatus['clockIn']['label']" />
              <x-timecard.clock-out-form
                  :disabled="$timecardButtonStatus['clockOut']['disabled']"
                  :label="$timecardButtonStatus['clockOut']['label']" />
            </div>
          </div>

          <!-- 休憩状態とボタン -->
          <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 bg-white dark:bg-gray-800">
            <h3 class="text-lg font-semibold mb-2 dark:text-white">休憩状態</h3>
            <div class="mb-2 dark:text-white">状態: <span class="font-bold dark:text-white">休憩中でない</span></div>
            <div class="flex space-x-2">
              <x-timecard.break-start-form
                  :disabled="$timecardButtonStatus['breakStart']['disabled']"
                  :label="$timecardButtonStatus['breakStart']['label']" />
              <x-timecard.break-end-form
                  :disabled="$timecardButtonStatus['breakEnd']['disabled']"
                  :label="$timecardButtonStatus['breakEnd']['label']" />
            </div>
          </div>
        </div>
        <!-- 勤怠記録 -->
        <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 mb-4 bg-white dark:bg-gray-800">
          <h3 class="text-lg font-semibold mb-4 dark:text-white">本日の勤怠記録</h3>
          <div class="grid grid-cols-3 gap-2">
            <div>
              <div class="text-sm text-gray-500 dark:text-gray-400">出勤時間</div>
              <div class="text-2xl font-bold dark:text-white">{{ $timecard['clock_in'] ?? '--:--' }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500 dark:text-gray-400">退勤時間</div>
              <div class="text-2xl font-bold dark:text-white">{{ $timecard['clock_out'] ?? '--:--' }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500 dark:text-gray-400">休憩時間</div>
              <div class="text-2xl font-bold dark:text-white">{{ $timecard['break_time'] ?? '00:00' }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500 dark:text-gray-400">実働時間</div>
              <div class="text-2xl font-bold dark:text-white">{{ $timecard['work_time'] ?? '00:00' }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500 dark:text-gray-400">残業時間</div>
              <div class="text-2xl font-bold dark:text-white">{{ $timecard['overtime'] ?? '00:00' }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500 dark:text-gray-400">深夜時間</div>
              <div class="text-2xl font-bold dark:text-white">{{ $timecard['night_work'] ?? '00:00' }}</div>
            </div>
          </div>
        </div>

        <!-- 申請セクション -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <!-- 部署メンバーの申請 -->
          <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 bg-white dark:bg-gray-800">
              <h3 class="text-lg font-semibold mb-2 dark:text-white">部署メンバーの申請</h3>
              <div class="space-y-2">
                @forelse($departmentMemberRequests as $request)
                  <div class="flex justify-between items-center">
                    <div>
                      <div class="font-medium dark:text-white">{{ $request['user_name'] }} - {{ $request['created_at'] }}</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">{{ $request['status'] }}</div>
                    </div>
                    <div class="text-right dark:text-white">
                      <div class="text-sm">{{ Str::limit($request['reason'], 30) }}</div>
                    </div>
                  </div>
                @empty
                  <div class="text-gray-500 dark:text-gray-400">部署メンバーからの申請はありません</div>
                @endforelse
                <div class="text-right">
                  <x-primary-button
                      tag="a"
                      href="{{ route('timecard-update-requests.index') }}"
                      class="text-sm">
                      一覧を見る
                  </x-primary-button>
                </div>
              </div>
            </div>

          <!-- 有給休暇申請 -->
          <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 bg-white dark:bg-gray-800">
            <h3 class="text-lg font-semibold mb-2 dark:text-white">有給休暇申請</h3>
            <div class="text-gray-500 dark:text-gray-400">現在、有給休暇申請はありません</div>
          </div>
        </div>
    </x-main-content>

    <script>
        const updateCurrentDateTime = () => {
            const now = new Date();
            const dateElement = document.getElementById('current-date');
            const timeElement = document.getElementById('current-time');

            // 日付が変わった場合のみ更新
            if (dateElement.textContent !== dateElement.dataset.initialDate) {
                dateElement.textContent = dateElement.dataset.initialDate;
            }

            // 時刻は常に更新
            timeElement.textContent = now.toLocaleTimeString('ja-JP', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
        };

        // 初期表示と1秒ごとの更新
        updateCurrentDateTime();
        setInterval(updateCurrentDateTime, 1000);
    </script>
</x-app-layout>
