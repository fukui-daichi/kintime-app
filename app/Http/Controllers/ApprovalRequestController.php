<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Attendance;
use App\Http\Requests\ApprovalRequest\StoreApprovalRequest;
use App\Http\Requests\ApprovalRequest\UpdateApprovalRequest;
use App\Http\Requests\ApprovalRequest\RejectApprovalRequest;
use App\Services\ApprovalRequest\ApprovalRequestService;
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
        if (!$this->approvalRequestService->canRequestModification($attendance)) {
            return back()->with('error', 'この勤怠データは現在修正申請できません');
        }

        return view('requests.create', compact('attendance'));
    }

    /**
     * 申請を保存
     */
    public function store(StoreApprovalRequest $request)
    {
        try {
            $this->approvalRequestService->createRequest($request->validatedData());
            return redirect()->route('requests.index')
                ->with('success', '申請を送信しました');
        } catch (\Exception $e) {
            return back()
                ->with('error', '申請の送信に失敗しました')
                ->withInput();
        }
    }

    /**
     * 申請を承認
     */
    public function approve(UpdateApprovalRequest $request, ApprovalRequest $approvalRequest)
    {
        try {
            $this->approvalRequestService->approveRequest(
                $approvalRequest,
                $request->validated()['comment'] ?? null
            );
            return back()->with('success', '申請を承認しました');
        } catch (\Exception $e) {
            return back()->with('error', '申請の承認に失敗しました');
        }
    }

    /**
     * 申請を否認
     */
    public function reject(RejectApprovalRequest $request, ApprovalRequest $approvalRequest)
    {
        try {
            $this->approvalRequestService->rejectRequest(
                $approvalRequest,
                $request->validated()['comment']
            );
            return back()->with('success', '申請を否認しました');
        } catch (\Exception $e) {
            return back()->with('error', '申請の否認に失敗しました');
        }
    }
}
