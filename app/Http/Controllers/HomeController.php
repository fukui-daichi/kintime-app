<?php

namespace App\Http\Controllers;

use App\Services\Timecard\TimecardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    private $timecardService;

    /**
     * コンストラクタ
     *
     * @param TimecardService $timecardService
     */
    public function __construct(TimecardService $timecardService)
    {
        $this->timecardService = $timecardService;
    }

    /**
     * ユーザー種別に応じたホーム画面を表示
     *
     * @return View|RedirectResponse
     */
    public function index(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();
        $timecardData = $this->timecardService->getDailyTimecardData(Auth::id());

        // 管理者の場合
        if ($user->user_type === 'admin') {
            return view('admin.index');
        }

        // 一般ユーザーの場合
        return view('user.index', $timecardData);
    }
}
