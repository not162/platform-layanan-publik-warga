<?php

namespace App\Http\Requests\Citizen;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCitizenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'family_card_id' => ['sometimes', 'exists:family_cards,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'nik' => ['sometimes', 'string', 'size:16'],
            'full_name' => ['sometimes', 'string', 'max:255'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'religion' => ['nullable', 'string', 'max:30'],
            'blood_type' => ['nullable', 'string', 'max:5'],
            'version' => ['required', 'integer', 'min:1'], // Optimistic locking requirement
        ];
    }
}
