<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DestroyChannelRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'confirm_name' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $channel = $this->route('channel');
            $confirmName = trim((string) $this->input('confirm_name'));

            if ($confirmName !== $channel->name) {
                $validator->errors()->add('confirm_name', 'チャンネル名が一致しません');
            }
        });
    }
}
