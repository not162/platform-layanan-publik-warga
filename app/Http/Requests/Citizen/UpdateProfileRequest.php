<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:25', 'regex:/^\+?[0-9][0-9\s().-]{6,23}$/'],
            'email' => [
                'nullable',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'occupation' => ['nullable', 'string', 'max:120'],
        ];
    }
}
