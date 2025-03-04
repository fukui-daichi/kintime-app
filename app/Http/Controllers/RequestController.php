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

        $formData = $this->requestService->getFormData($timecard);
        return view('user.requests.create', $formData);
    }

    /**
     * 勤怠修正申請フォームを表示
     *
     * @param Timecard $timecard 勤怠データ
     * @return View|RedirectResponse
     */
    public function createTimecardModification(Timecard $timecard): View|RedirectResponse
    {
        if (!$this->requestService->canUpdateTimecard($timecard)) {
            return back()->with('error', 'この勤怠データは現在修正申請できません');
        }

        $formData = $this->requestService->getTimecardModificationFormData($timecard);
        return view('user.requests.timecard-modification', $formData);
    }

    /**
     * 有給休暇申請フォームを表示
     *
     * @param HttpRequest $request リクエストデータ
     * @return View|RedirectResponse
     */
    public function createPaidVacation(HttpRequest $request): View|RedirectResponse
    {
        $targetDate = $request->input('date');

        // 過去日付の場合はエラー（過去日付は勤怠データ修正のみ）
        if ($this->requestService->isPastDate($targetDate)) {
            return back()->with('error', '過去日付には有給休暇申請はできません');
        }

        $formData = $this->requestService->getVacationRequestFormData($targetDate);
        return view('user.requests.paid-vacation-request', $formData);
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
            // デバッグ情報をログに記録
            Log::debug('申請フォーム送信開始', [
                'formData' => $request->all(),
                'request_type' => $request->input('request_type'),
                'target_date' => $request->input('target_date'),
                'vacation_type' => $request->input('vacation_type'),
            ]);

            $validatedData = $request->validatedData();

            Log::debug('バリデーション済みデータ', [
                'validatedData' => $validatedData
            ]);

            $this->requestService->createRequest($validatedData);

            Log::debug('申請フォーム送信完了');

            return redirect()
                ->route('requests.index')
                ->with('success', '申請を送信しました');
        } catch (\Exception $e) {
            Log::error('申請の送信に失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'formData' => $request->all()
            ]);

            return back()
                ->with('error', '申請の送信に失敗しました: ' . $e->getMessage())
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
            return back()->with('error', '申請の否認に失敗しました');
        }
    }
}
