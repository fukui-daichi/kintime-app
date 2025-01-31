<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private $attendanceController;

    public function __construct(AttendanceController $attendanceController)
    {
        $this->attendanceController = $attendanceController;
    }

    /**
     * ユーザー種別に応じたホーム画面を表示
     */
    public function index()
    {
        // 未ログインの場合はログイン画面へリダイレクト
        if (!Auth::check()) {
            return redirect('login');
        }

        // ユーザー種別に応じて表示を分岐
        return Auth::user()->user_type === 'admin'
            ? view('admin.index')
            : $this->attendanceController->index();
    }
}
