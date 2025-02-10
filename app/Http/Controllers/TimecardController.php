<?php

namespace App\Http\Controllers;

use App\Exceptions\Timecard\TimecardException;
use App\Exceptions\Timecard\DuplicateClockInException;
use App\Exceptions\Timecard\ClockInNotFoundException;
use App\Exceptions\Timecard\InvalidWorkTimeException;
use App\Services\Timecard\TimecardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

    /**
     * 出勤打刻処理
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clockIn()
    {
        try {
            $this->timecardService->clockIn(Auth::id());
            return back()->with('success', '出勤を記録しました。');
        } catch (DuplicateClockInException $e) {
            // 重複打刻の場合
            return back()->with('error', $e->getMessage());
        } catch (TimecardException $e) {
            // その他の既知のエラー
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            // 予期せぬエラー
            Log::error('Unexpected error in clockIn', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'システムエラーが発生しました。');
        }
    }

    /**
     * 退勤打刻処理
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clockOut()
    {
        try {
            $this->timecardService->clockOut(Auth::id());
            return back()->with('success', '退勤を記録しました。');
        } catch (ClockInNotFoundException $e) {
            // 出勤記録が存在しない場合
            return back()->with('error', $e->getMessage());
        } catch (InvalidWorkTimeException $e) {
            // 勤務時間の計算エラー
            return back()->with('error', $e->getMessage());
        } catch (TimecardException $e) {
            // その他の既知のエラー
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            // 予期せぬエラー
            Log::error('Unexpected error in clockOut', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'システムエラーが発生しました。');
        }
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
