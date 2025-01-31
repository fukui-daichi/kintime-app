<?php

namespace App\Http\Controllers;

use App\Services\Attendance\AttendanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // サービスクラスのプロパティを定義
    private $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        // AttendanceServiceのインスタンスを受け取り、プロパティに保存
        $this->attendanceService = $attendanceService;
    }

    // 勤怠画面表示用メソッド
    public function index()
    {
        $attendanceData = $this->attendanceService->getDailyAttendanceData(Auth::id());
        return view('user.index', $attendanceData);
    }

    // 出勤打刻処理メソッド
    public function clockIn()
    {
        // サービスクラスの出勤処理を実行
        $result = $this->attendanceService->clockIn(Auth::id());

        // 処理結果に応じてフラッシュメッセージを設定し、前のページに戻る
        return back()->with(
            $result['success'] ? 'success' : 'error', // 成功/失敗に応じてメッセージの種類を切り替え
            $result['message'] // メッセージ内容
        );
    }

    // 退勤打刻処理メソッド
    public function clockOut()
    {
        // サービスクラスの退勤処理を実行
        $result = $this->attendanceService->clockOut(Auth::id());

        // 処理結果に応じてフラッシュメッセージを設定し、前のページに戻る
        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    /**
     * 月別勤怠一覧画面の表示
     */
    public function monthly(Request $request)
    {
        // サービスから必要なデータを取得してビューに渡すだけにする
        return view('user.attendance.monthly',
            $this->attendanceService->getMonthlyAttendanceData(
                Auth::id(),
                $request->query('year'),
                $request->query('month')
            )
        );
    }
}
