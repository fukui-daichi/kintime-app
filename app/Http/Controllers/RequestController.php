<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request\CreateRequest;
use App\Models\Request;
use App\Models\Timecard;
use App\Services\Request\RequestService;
use App\Constants\RequestConstants;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * 申請関連の処理を担当するコントローラー
 */
class RequestController extends Controller
{
    /**
     * @var RequestService
     */
    private $requestService;

    /**
     * コンストラクタ
     *
     * @param RequestService $requestService
     */
    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * ユーザー種別に応じた申請一覧を表示
     * 管理者：すべての申請を表示（ステータスでフィルタリング可能）
     * 一般ユーザー：自分の申請のみ表示
     *
     * @param HttpRequest $request
     * @return View
     */
    public function index(HttpRequest $request): View
    {
        $user = Auth::user();
        $currentStatus = $request->query('status', RequestConstants::DEFAULT_STATUS);

        if ($user->user_type === 'admin') {
            // 管理者の場合
            $result = $this->requestService->getAllRequestList($currentStatus);
        } else {
            // 一般ユーザーの場合
            $result = $this->requestService->getPersonalRequestList($user->id, $currentStatus);
        }

        return view($user->user_type === 'admin' ? 'admin.requests.index' : 'user.requests.index', [
            'requests' => $result['requests'],
            'paginator' => $result['paginator'],
            'statusList' => RequestConstants::STATUS_LIST,
            'currentStatus' => $currentStatus,
        ]);
    }

    /**
     * 申請作成フォームを表示
     *
     * @param Timecard $timecard
     * @return View|RedirectResponse
     */
    public function create(Timecard $timecard)
    {
        // 申請可能か確認
        if (!$this->requestService->canUpdateTimecard($timecard)) {
            return back()->with('error', 'この勤怠データは現在修正申請できません');
        }

        // フォームデータを取得
        $viewData = $this->requestService->getFormData($timecard);

        return view('user.requests.create', $viewData);
    }

    /**
     * 申請を保存
     *
     * @param CreateRequest $request
     * @return RedirectResponse
     */
    public function store(CreateRequest $request): RedirectResponse
    {
        try {
            // 申請データを作成
            $this->requestService->createRequest($request->validatedData());

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
     * @param Request $request
     * @return RedirectResponse
     */
    public function approve(Request $request): RedirectResponse
    {
        try {
            $this->requestService->approveRequest($request);
            return back()->with('success', '申請を承認しました');
        } catch (\Exception $e) {
            Log::error('承認処理エラー: ' . $e->getMessage());
            return back()->with('error', '申請の承認に失敗しました');
        }
    }

    /**
     * 申請を否認
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function reject(Request $request): RedirectResponse
    {
        try {
            $this->requestService->rejectRequest($request);
            return back()->with('success', '申請を否認しました');
        } catch (\Exception $e) {
            Log::error('否認処理エラー: ' . $e->getMessage());
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
        Log::error('申請処理エラー', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return back()
            ->with('error', $message)
            ->withInput();
    }
}
