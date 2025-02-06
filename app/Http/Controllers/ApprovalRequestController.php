<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovalRequest\CreateApprovalRequest;
use App\Models\ApprovalRequest;
use App\Models\Attendance;
use App\Http\Requests\ApprovalRequest\StoreApprovalRequest;
use App\Http\Requests\ApprovalRequest\UpdateApprovalRequest;
use App\Http\Requests\ApprovalRequest\RejectApprovalRequest;
use App\Services\ApprovalRequest\ApprovalRequestService;
use App\Constants\ApprovalRequestConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

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
     * ユーザー種別に応じた申請一覧を表示
     * 管理者：すべての申請を表示（ステータスでフィルタリング可能）
     * 一般ユーザー：自分の申請のみ表示
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // 管理者の場合
        if (Auth::user()->user_type === 'admin') {
            // クエリパラメータからステータスを取得（デフォルトは 'pending'）
            $currentStatus = $request->query('status', ApprovalRequestConstants::DEFAULT_STATUS);

            // フィルタリングされた申請一覧を取得
            $requests = $this->approvalRequestService->getFilteredRequests($currentStatus);

            return view('admin.requests.index', [
                'requests' => $requests,
                'statusList' => ApprovalRequestConstants::STATUS_LIST,
                'currentStatus' => $currentStatus,
            ]);
        }

        // 一般ユーザーの場合
        $requests = $this->approvalRequestService->getUserRequests(Auth::id());
        return view('user.requests.index', ['requests' => $requests]);
    }

    /**
     * 申請作成フォームを表示
     *
     * @param Attendance $attendance
     * @return View|RedirectResponse
     */
    public function create(Attendance $attendance)
    {
        // 申請可能か確認
        if (!$this->approvalRequestService->canRequestModification($attendance)) {
            return back()->with('error', 'この勤怠データは現在修正申請できません');
        }

        // フォームデータを取得
        $viewData = $this->approvalRequestService->getFormData($attendance);

        return view('user.requests.create', $viewData);
    }

    /**
     * 申請を保存
     *
     * @param CreateApprovalRequest $request
     * @return RedirectResponse
     */
    public function store(CreateApprovalRequest $request): RedirectResponse
    {
        try {
            // 申請データを作成
            $this->approvalRequestService->createRequest($request->validatedData());

            return redirect()->route('requests.index')
                ->with('success', '申請を送信しました');
        } catch (\Exception $e) {
            Log::error('申請作成エラー: ' . $e->getMessage());

            return back()
                ->with('error', '申請の送信に失敗しました')
                ->withInput();
        }
    }

    /**
     * 申請を承認
     *
     * @param ApprovalRequest $approvalRequest
     * @return RedirectResponse
     */
    public function approve(ApprovalRequest $approvalRequest): RedirectResponse
    {
        try {
            $this->approvalRequestService->approveRequest($approvalRequest);
            return back()->with('success', '申請を承認しました');
        } catch (\Exception $e) {
            Log::error('承認処理でエラーが発生: ' . $e->getMessage());
            return back()->with('error', '申請の承認に失敗しました');
        }
    }

    /**
     * 申請を否認
     *
     * @param ApprovalRequest $approvalRequest
     * @return RedirectResponse
     */
    public function reject(ApprovalRequest $approvalRequest): RedirectResponse
    {
        try {
            $this->approvalRequestService->rejectRequest($approvalRequest);
            return back()->with('success', '申請を否認しました');
        } catch (\Exception $e) {
            return back()->with('error', '申請の否認に失敗しました');
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
