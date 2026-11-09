<?php

namespace App\Http\Requests;

use App\Rules\ChannelNameAvailable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * チャンネルの公開範囲（visibility）は作成後に変更不可（data.md §2-2／Q-02）のため、
 * この FormRequest は visibility を一切扱わない（rules にも、保存先にも現れない）。
 */
class UpdateChannelRequest extends FormRequest
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
                new ChannelNameAvailable($this->user(), $this->route('channel')->id),
            ],
            'description' => ['nullable', 'string', 'max:200'],
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
        ];
    }
}
