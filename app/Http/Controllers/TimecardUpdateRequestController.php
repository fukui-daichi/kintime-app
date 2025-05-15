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

    public function index(Request $request)
    {
        return view('timecard.update-requests.index',
            $this->service->getRequestData(Auth::user(), $request)
        );
    }

    public function create(Timecard $timecard)
    {
        return view('timecard.update-requests.create',
            $this->service->getCreateFormData($timecard)
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
        $request = $this->service->findRequest($id);
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
