<?php

namespace App\Http\Requests\ApprovalRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateApprovalRequest extends FormRequest
{
    /**
     * リクエストの承認可否を判定
     * 管理者のみ承認可能
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->user_type === 'admin';
    }


    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'comment' => 'nullable|string|max:500',
        ];
    }

    /**
     * バリデーションメッセージの日本語化
     */
    public function messages(): array
    {
        return [
            'comment.string' => 'コメントは文字列で入力してください',
            'comment.max' => 'コメントは500文字以内で入力してください',
        ];
    }

    /**
     * バリデーション済みデータを取得して整形
     */
    public function validatedData(): array
    {
        return $this->validated();
    }
}
