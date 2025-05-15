<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Timecard;
use App\Services\TimecardService;
use Illuminate\Support\Facades\Auth;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;

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

    /**
     * 勤怠一覧表示（月次・ページネーションなし・深夜残業時間含む）
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        switch ($user->getUserType()) {
            case 'admin':
                abort(404);
            case 'manager':
                return view('timecard.manager.index', $this->timecardService->getTimecardData($user, request()));
            default:
                return view('timecard.user.index', $this->timecardService->getTimecardData($user, request()));
        }
    }

    /**
     * タイムカード編集フォーム表示
     */
    public function edit(Timecard $timecard)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->getUserType() !== 'manager') {
            abort(403, 'このページにアクセスする権限がありません');
        }

        return view('timecard.edit',
            $this->timecardService->getTimecardEditData($timecard)
        );
    }

    /**
     * タイムカード更新処理
     */
    public function update(Request $request, Timecard $timecard)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->getUserType() !== 'manager') {
            abort(403, 'このページにアクセスする権限がありません');
        }

        $validated = $request->validate([
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
            'break_start' => 'required|date_format:H:i',
            'break_end' => 'required|date_format:H:i'
        ]);

        $this->timecardService->updateTimecard($timecard, $validated);

        return redirect()
            ->route('timecard.index', [
                'year' => $timecard->date->year,
                'month' => $timecard->date->month
            ])
            ->with('status', '勤怠情報を更新しました');
    }
}
