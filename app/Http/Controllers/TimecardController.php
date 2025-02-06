<?php

namespace App\Http\Controllers;

use App\Services\Timecard\TimecardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TimecardController extends Controller
{
    // サービスクラスのプロパティを定義
    private $timecardService;

    public function __construct(TimecardService $timecardService)
    {
        // TimecardServiceのインスタンスを受け取り、プロパティに保存
        $this->timecardService = $timecardService;
    }

    // 勤怠画面表示用メソッド
    public function index()
    {
        $timecardData = $this->timecardService->getDailyTimecardData(Auth::id());
        return view('user.index', $timecardData);
    }

    // 出勤打刻処理メソッド
    public function clockIn()
    {
        // サービスクラスの出勤処理を実行
        $result = $this->timecardService->clockIn(Auth::id());

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
        $result = $this->timecardService->clockOut(Auth::id());

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
        return view('user.timecard.monthly',
            $this->timecardService->getMonthlyTimecardData(
                Auth::id(),
                $request->query('year'),
                $request->query('month')
            )
        );
    }
}
