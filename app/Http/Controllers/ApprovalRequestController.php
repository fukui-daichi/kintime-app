<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Attendance;
use App\Http\Requests\ApprovalRequest\StoreApprovalRequest;
use App\Http\Requests\ApprovalRequest\UpdateApprovalRequest;
use App\Http\Requests\ApprovalRequest\RejectApprovalRequest;
use App\Services\ApprovalRequest\ApprovalRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * 申請関連の処理を担当するコントローラー
 */
class ApprovalRequestController extends Controller
{
    /**
     * @var ApprovalRequestService
     */
    private $approvalRequestService;

    /**
     * コンストラクタ
     *
     * @param ApprovalRequestService $approvalRequestService
     */
    public function __construct(ApprovalRequestService $approvalRequestService)
    {
        $this->approvalRequestService = $approvalRequestService;
    }

    /**
     * 申請一覧を表示（一般ユーザー用）
     *
     * @return View
     */
    public function userIndex(): View
    {
        $requests = $this->approvalRequestService->getUserRequests(Auth::id());
        return view('user.requests.index', compact('requests'));
    }

    /**
     * 承認待ち一覧を表示（管理者用）
     *
     * @param Request $request
     * @return View
     */
    public function adminIndex(Request $request): View
    {
        $currentStatus = $request->query('status', 'pending');
        $requests = $this->approvalRequestService->getFilteredRequests($currentStatus);

        $statusList = [
            'all' => 'すべて',
            'pending' => '承認待ち',
            'approved' => '承認済み',
            'rejected' => '否認'
        ];

        return view('admin.requests.index', compact('requests', 'statusList', 'currentStatus'));
    }

    /**
     * 申請作成フォームを表示
     *
     * @param Attendance $attendance
     * @return View|RedirectResponse
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
     *
     * @param StoreApprovalRequest $request
     * @return RedirectResponse
     */
    public function store(StoreApprovalRequest $request): RedirectResponse
    {
        try {
            $this->approvalRequestService->createRequest($request->validatedData());
            return redirect()->route('requests.index')
                ->with('success', '申請を送信しました');
        } catch (\Exception $e) {
            return $this->handleRequestError($e, '申請の送信に失敗しました');
        }
    }

    /**
     * 申請を承認
     *
     * @param UpdateApprovalRequest $request
     * @param ApprovalRequest $approvalRequest
     * @return RedirectResponse
     */
    public function approve(UpdateApprovalRequest $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        try {
            $this->approvalRequestService->approveRequest(
                $approvalRequest,
                $request->validated()['comment'] ?? null
            );
            return back()->with('success', '申請を承認しました');
        } catch (\Exception $e) {
            return $this->handleRequestError($e, '申請の承認に失敗しました');
        }
    }

    /**
     * 申請を否認
     *
     * @param RejectApprovalRequest $request
     * @param ApprovalRequest $approvalRequest
     * @return RedirectResponse
     */
    public function reject(RejectApprovalRequest $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        try {
            $this->approvalRequestService->rejectRequest(
                $approvalRequest,
                $request->validated()['comment']
            );
            return back()->with('success', '申請を否認しました');
        } catch (\Exception $e) {
            return $this->handleRequestError($e, '申請の否認に失敗しました');
        }
    }

    /**
     * エラー処理の共通メソッド
     *
     * @param \Exception $e 発生した例外
     * @param string $message エラーメッセージ
     * @return RedirectResponse
     */
    private function handleRequestError(\Exception $e, string $message): RedirectResponse
    {
        return back()
            ->with('error', $message)
            ->withInput();
    }
}
