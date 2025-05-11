<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\TimecardService;
use App\Services\TimecardUpdateRequestService;
use App\Helpers\DateHelper;

class DashboardController extends Controller
{
    protected $timecardService;
    protected $timecardUpdateRequestService;

    public function __construct(
        TimecardService $timecardService,
        TimecardUpdateRequestService $timecardUpdateRequestService
    ) {
        $this->timecardService = $timecardService;
        $this->timecardUpdateRequestService = $timecardUpdateRequestService;
    }

    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return redirect('/login');
        }

        switch ($user->getUserType()) {
            case 'admin':
                return view('dashboard.admin.index', compact('user'));
            case 'manager':
                return view('dashboard.manager.index', compact('user'));
            default:
                $timecardButtonStatus = $this->timecardService->getTimecardButtonStatus($user->id);
                $todayTimecard = $this->timecardService->getTodayTimecard($user->id);

                $timecardData = $todayTimecard
                    ? $this->timecardService->formatTimecardForDisplay($todayTimecard)
                    : null;

                $pendingRequests = $this->timecardUpdateRequestService
                    ->getPendingRequestsForDashboard($user->id);

                return view('dashboard.user.index', [
                    'user' => $user,
                    'timecardButtonStatus' => $timecardButtonStatus,
                    'timecard' => $todayTimecard
                        ? $this->timecardService->formatTimecardForDisplay($todayTimecard)
                        : null,
                    'currentDate' => DateHelper::getJapaneseDateString(),
                    'pendingRequests' => $pendingRequests
                ]);
        }
    }
}
