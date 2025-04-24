<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\RegistrationService;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, RegistrationService $registrationService): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'departments' => $registrationService->getDepartments(),
            'employmentTypes' => $registrationService->getEmploymentTypes()
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request)
    {
        try {
            // リクエスト情報をログに出力
            Log::info('ProfileController@update request', [
                'ajax' => $request->ajax(),
                'wantsJson' => $request->wantsJson(),
                'headers' => $request->headers->all(),
                'contentType' => $request->getContentType(),
                'isJson' => $request->isJson()
            ]);

            // JSONリクエストを明示的に処理
            if ($request->isJson()) {
                $data = $request->json()->all();
                $request->merge($data);
            }

            // リクエストデータ確認
            Log::debug('Profile update request data:', $request->all());
            file_put_contents(storage_path('logs/profile_debug.log'),
                print_r($request->all(), true), FILE_APPEND);

            $user = $request->user();
            $changes = $request->validated();
            Log::debug('Validated changes:', $changes);

            $user->fill($changes);
            Log::debug('User dirty attributes:', $user->getDirty());

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $saved = $user->save();
            Log::debug('Save result:', [
                'success' => $saved,
                'changes' => $user->getChanges(),
                'user_id' => $user->id
            ]);

            // 保存後のデータ確認
            $updatedUser = User::find($user->id);
            Log::debug('Updated user data:', $updatedUser->toArray());

            return Redirect::route('profile.edit')->with('status', 'profile-updated');

        } catch (\Exception $e) {
            Log::error('Profile update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'プロファイル更新に失敗しました']);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
