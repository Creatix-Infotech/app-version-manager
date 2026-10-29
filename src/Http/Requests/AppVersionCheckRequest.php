<?php

namespace Creatix\AppVersionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppVersionCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string'],
            'build' => ['required', 'integer', 'min:0'],
        ];
    }
}

