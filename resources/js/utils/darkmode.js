/**
 * ダークモード管理モジュール
 * サイト全体でダークモードの状態を管理します
 */

// テーマ切り替え関数
export function toggleDarkMode() {
    const htmlElement = document.documentElement;

    if (htmlElement.classList.contains('dark')) {
        htmlElement.classList.remove('dark');
        localStorage.setItem('color-theme', 'light');
    } else {
        htmlElement.classList.add('dark');
        localStorage.setItem('color-theme', 'dark');
    }
}

// 現在のテーマを取得する関数
export function getCurrentTheme() {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}
