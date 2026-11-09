<?php

namespace App\Http\Requests\Auth;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class LoginRequest extends FortifyLoginRequest
{
    /**
     * 空のメール・パスワードも通常の認証失敗として扱う（screens.md §4：
     * 認証失敗は項目ごとに分けず1文だけ表示する）ため、項目ごとの必須チェックはしない。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
