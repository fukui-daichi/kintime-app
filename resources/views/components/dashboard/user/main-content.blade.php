<main class="p-4 md:ml-64 h-auto pt-20 bg-white dark:bg-gray-800">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
      <!-- 現在時刻表示 -->
      <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 bg-white dark:bg-gray-800">
        <h3 class="text-lg font-semibold mb-2 dark:text-white">現在時刻</h3>
        <div id="current-time" class="text-2xl font-bold dark:text-white">00:00:00</div>
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
    <!-- 勤務時間サマリー -->
    <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 mb-4 bg-white dark:bg-gray-800">
      <h3 class="text-lg font-semibold mb-4 dark:text-white">今日の勤務時間</h3>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <div class="text-sm text-gray-500 dark:text-gray-400">出勤時間</div>
          <div class="text-xl font-bold dark:text-white">--:--</div>
        </div>
        <div>
          <div class="text-sm text-gray-500 dark:text-gray-400">退勤時間</div>
          <div class="text-xl font-bold dark:text-white">--:--</div>
        </div>
        <div>
          <div class="text-sm text-gray-500 dark:text-gray-400">休憩時間</div>
          <div class="text-xl font-bold dark:text-white">00:00</div>
        </div>
        <div>
          <div class="text-sm text-gray-500 dark:text-gray-400">実働時間</div>
          <div class="text-xl font-bold dark:text-white">00:00</div>
        </div>
      </div>
    </div>

    <!-- 打刻記録 -->
    <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 mb-4 bg-white dark:bg-gray-800">
      <h3 class="text-lg font-semibold mb-4 dark:text-white">直近の打刻記録</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b">
              <th class="text-left py-2 dark:text-white">日付</th>
              <th class="text-left py-2 dark:text-white">種類</th>
              <th class="text-left py-2 dark:text-white">時間</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b">
              <td class="py-2 dark:text-white">--/--</td>
              <td class="py-2 dark:text-white">--</td>
              <td class="py-2 dark:text-white">--:--</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
      <!-- 月間勤務時間 -->
      <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 bg-white dark:bg-gray-800">
        <div class="text-sm text-gray-500 dark:text-gray-400">今月の勤務時間</div>
        <div class="text-xl font-bold dark:text-white">00:00</div>
      </div>

      <!-- 残業時間 -->
      <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 bg-white dark:bg-gray-800">
        <div class="text-sm text-gray-500 dark:text-gray-400">今月の残業時間</div>
        <div class="text-xl font-bold dark:text-white">00:00</div>
      </div>

      <!-- 有給残日数 -->
      <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 bg-white dark:bg-gray-800">
        <div class="text-sm text-gray-500 dark:text-gray-400">有給残日数</div>
        <div class="text-xl font-bold dark:text-white">0日</div>
      </div>

      <!-- 遅刻回数 -->
      <div class="border border-gray-200 rounded-lg dark:border-gray-600 p-4 bg-white dark:bg-gray-800">
        <div class="text-sm text-gray-500 dark:text-gray-400">今月の遅刻回数</div>
        <div class="text-xl font-bold dark:text-white">0回</div>
      </div>
    </div>
</main>

  @push('scripts')
  <script>
    function updateCurrentTime() {
      const now = new Date();
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
    }

    // 初期表示
    updateCurrentTime();

    // 1秒ごとに更新
    setInterval(updateCurrentTime, 1000);
  </script>
  @endpush
