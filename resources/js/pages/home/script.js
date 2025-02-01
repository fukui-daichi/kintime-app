// 時計の更新を行う関数
function updateClock() {
    const clockElement = document.getElementById('current-time');
    if (!clockElement) return;

    // 現在時刻を取得して表示形式に整形
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    clockElement.textContent = `${hours}:${minutes}:${seconds}`;
}

// 1秒ごとに時計を更新
setInterval(updateClock, 1000);
updateClock(); // 初回実行

// フォーム送信時の二重送信防止
document.addEventListener('DOMContentLoaded', function() {
    const clockForms = document.querySelectorAll('.clock-form');

    clockForms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
            }
        });
    });
});

console.log("test");
