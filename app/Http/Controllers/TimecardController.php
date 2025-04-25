<?php

namespace App\Http\Controllers;

use App\Services\TimecardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TimecardController extends Controller
{
    protected TimecardService $timecardService;

    public function __construct(TimecardService $timecardService)
    {
        $this->timecardService = $timecardService;
    }

    /**
     * 出勤打刻
     */
    public function clockIn()
    {
        try {
            $this->timecardService->clockIn(Auth::user());
            return back()->with('status', '出勤打刻が完了しました');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * 退勤打刻
     */
    public function clockOut()
    {
        try {
            $this->timecardService->clockOut(Auth::user());
            return back()->with('status', '退勤打刻が完了しました');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * 休憩開始
     */
    public function startBreak()
    {
        try {
            $this->timecardService->startBreak(Auth::user());
            return back()->with('status', '休憩開始打刻が完了しました');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * 休憩終了
     */
    public function endBreak()
    {
        try {
            $this->timecardService->endBreak(Auth::user());
            return back()->with('status', '休憩終了打刻が完了しました');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
