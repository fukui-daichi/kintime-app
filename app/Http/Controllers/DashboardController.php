<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return redirect('/login');
        }

        switch ($user->getUserType()) {
            case 'admin':
                return view('dashboard.admin', compact('user'));
            case 'manager':
                return view('dashboard.manager', compact('user'));
            default:
                return view('dashboard.user', compact('user'));
        }
    }
}
