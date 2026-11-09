<?php

namespace App\Http\Requests;

use App\Rules\ChannelNameAvailable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreChannelRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                new ChannelNameAvailable($this->user()),
            ],
            'description' => ['nullable', 'string', 'max:200'],
            'visibility' => ['required', 'in:public,private'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'チャンネル名を入力してください',
            'name.max' => 'チャンネル名は50文字以内で入力してください',
            'description.max' => '説明は200文字以内で入力してください',
            'visibility.required' => '公開範囲を選択してください',
            'visibility.in' => '公開範囲を選択してください',
        ];
    }
}
