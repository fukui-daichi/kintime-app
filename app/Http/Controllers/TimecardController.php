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
    public function clockIn(): JsonResponse
    {
        try {
            $timecard = $this->timecardService->clockIn(Auth::user());
            return response()->json($timecard, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 退勤打刻
     */
    public function clockOut(): JsonResponse
    {
        try {
            $timecard = $this->timecardService->clockOut(Auth::user());
            return response()->json($timecard);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 休憩開始
     */
    public function startBreak(): JsonResponse
    {
        try {
            $timecard = $this->timecardService->startBreak(Auth::user());
            return response()->json($timecard);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 休憩終了
     */
    public function endBreak(): JsonResponse
    {
        try {
            $timecard = $this->timecardService->endBreak(Auth::user());
            return response()->json($timecard);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
