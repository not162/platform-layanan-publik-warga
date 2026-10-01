<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLetterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For admin/secretary verification/approval
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:draft,submitted,verified,approved,completed,rejected'],
            'ticket_number' => ['sometimes', 'string', 'max:255'],
            'letter_number' => ['sometimes', 'string', 'max:255'],
            'version' => ['required', 'integer', 'min:1'], // Optimistic locking
        ];
    }
}
