<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Attendance;
use App\Models\User;
use App\Services\ApprovalRequest\ApprovalRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalRequestController extends Controller
{
    private $approvalRequestService;

    public function __construct(ApprovalRequestService $approvalRequestService)
    {
        $this->approvalRequestService = $approvalRequestService;
    }

    /**
     * 申請一覧を表示（一般ユーザー用）
     */
    public function userIndex()
    {
        $requests = $this->approvalRequestService->getUserRequests(Auth::id());

        return view('user.requests.index', compact('requests'));
    }

    /**
     * 承認待ち一覧を表示（管理者用）
     */
    public function adminIndex()
    {
        $requests = $this->approvalRequestService->getPendingRequests();

        return view('admin.requests.index', compact('requests'));
    }

    /**
     * 申請作成フォームを表示
     */
    public function create(Attendance $attendance)
    {
        // 修正可能か確認
        if (!$this->approvalRequestService->canRequestModification($attendance)) {
            return back()->with('error', 'この勤怠データは現在修正申請できません');
        }

        return view('requests.create', compact('attendance'));
    }

    /**
     * 申請を保存
     */
    public function store(Request $request)
    {
        // バリデーション
        $validated = $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'request_type' => 'required|in:time_correction,break_time_modification',
            'after_clock_in' => 'required_if:request_type,time_correction|date_format:H:i',
            'after_clock_out' => 'required_if:request_type,time_correction|date_format:H:i|after:after_clock_in',
            'after_break_hours' => 'required_if:request_type,break_time_modification|numeric|between:0,5',
            'reason' => 'required|string|max:500',
        ]);

        try {
            // 申請データの準備
            $requestData = array_merge($validated, [
                'user_id' => Auth::id(),
                'approver_id' => User::where('user_type', 'admin')->first()->id, // 仮実装：最初の管理者を承認者に
                'status' => 'pending',
            ]);

            // 申請を作成
            $this->approvalRequestService->createRequest($requestData);

            return redirect()->route('requests.index')
                ->with('success', '申請を送信しました');

        } catch (\Exception $e) {
            return back()
                ->with('error', '申請の送信に失敗しました')
                ->withInput();
        }
    }

    /**
     * 申請を承認（管理者用）
     */
    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        $validated = $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        try {
            $this->approvalRequestService->approveRequest($approvalRequest, $validated['comment'] ?? null);

            return back()->with('success', '申請を承認しました');

        } catch (\Exception $e) {
            return back()->with('error', '申請の承認に失敗しました');
        }
    }

    /**
     * 申請を否認（管理者用）
     */
    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:500', // 否認理由は必須
        ]);

        try {
            $this->approvalRequestService->rejectRequest($approvalRequest, $validated['comment']);

            return back()->with('success', '申請を否認しました');

        } catch (\Exception $e) {
            return back()->with('error', '申請の否認に失敗しました');
        }
    }
}
