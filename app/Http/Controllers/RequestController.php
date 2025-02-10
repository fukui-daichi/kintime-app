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

class RequestController extends Controller
{
    private $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * 申請一覧を表示
     *
     * @param HttpRequest $request
     * @return View
     */
    public function index(HttpRequest $request): View
    {
        $user = Auth::user();
        $currentStatus = $request->query('status', RequestConstants::DEFAULT_STATUS);

        if ($user->user_type === 'admin') {
            $result = $this->requestService->getAllRequestList($currentStatus);
            $view = 'admin.requests.index';
        } else {
            $result = $this->requestService->getPersonalRequestList($user->id, $currentStatus);
            $view = 'user.requests.index';
        }

        return view($view, [
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
    public function create(Timecard $timecard): View|RedirectResponse
    {
        if (!$this->requestService->canUpdateTimecard($timecard)) {
            return back()->with('error', 'この勤怠データは現在修正申請できません');
        }

        return view('user.requests.create', $this->requestService->getFormData($timecard));
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
            $this->requestService->createRequest($request->validatedData());
            return redirect()
                ->route('requests.index')
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
}
