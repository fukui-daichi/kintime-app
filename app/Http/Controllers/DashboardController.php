<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\TimecardService;
use App\Services\TimecardUpdateRequestService;

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

        $view = match ($user->getUserType()) {
            'admin' => 'dashboard.admin.index',
            'manager' => 'dashboard.manager.index',
            default => 'dashboard.user.index'
        };

        return view($view, $this->timecardService->getDashboardData($user, request()));
    }
}
