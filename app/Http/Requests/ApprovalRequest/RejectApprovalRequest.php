<?php

namespace App\Http\Requests\ApprovalRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RejectApprovalRequest extends FormRequest
{
    /**
     * リクエストの承認可否を判定
     * 管理者のみ否認可能
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        return $user->user_type === 'admin';
    }

    /**
     * バリデーションルール
     * 否認時はコメント必須
     */
    public function rules(): array
    {
        return [
            'comment' => 'required|string|max:500',
        ];
    }

    /**
     * バリデーションメッセージの日本語化
     */
    public function messages(): array
    {
        return [
            'comment.required' => '否認理由を入力してください',
            'comment.string' => '否認理由は文字列で入力してください',
            'comment.max' => '否認理由は500文字以内で入力してください',
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
