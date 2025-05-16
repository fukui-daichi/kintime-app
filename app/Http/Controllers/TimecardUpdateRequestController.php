<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimecardUpdateRequestRequest;
use App\Models\Timecard;
use App\Services\TimecardUpdateRequestService;
use App\Helpers\TimecardHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimecardUpdateRequestController extends Controller
{
    private TimecardUpdateRequestService $service;

    public function __construct(TimecardUpdateRequestService $service)
    {
        $this->service = $service;
    }

    public function userIndex(Request $request)
    {
        $user = $request->user();
        if (!$user->isUser()) {
            abort(403);
        }

        return view('timecard.update-requests.index',
            $this->service->getUserRequestData($user, $request)
        );
    }

    public function approvalIndex(Request $request)
    {
        $user = $request->user();
        if (!$user->isManager() && !$user->isAdmin()) {
            abort(403);
        }

        return view('timecard.approval-requests.index',
            $this->service->getApprovalRequestData($user)
        );
    }

    public function create(Timecard $timecard)
    {
        $user = Auth::user();
        return view('timecard.update-requests.create',
            $this->service->getCreateFormData($timecard, $user)
        );
    }

    public function store(TimecardUpdateRequestRequest $request)
    {
        $user = Auth::user();
        $this->service->createRequest($request->validated(), $user);

        return redirect()->route('timecard-update-requests.index')
            ->with('success', '打刻修正申請を送信しました');
    }

    public function show($id)
    {
        $request = $this->service->getRepository()->findById($id);
        if (!$request) {
            abort(404);
        }
        return view('timecard.update-requests.show', compact('request'));
    }

    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $result = $this->service->approveRequest($id, $user);

        if (!$result) {
            abort(403);
        }

        return redirect()->back()
            ->with('success', '申請を承認しました');
    }
}
