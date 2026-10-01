<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Admin, Secretary, Security, RT Head can manage complaints
        return auth()->check() && in_array(auth()->user()->role, [
            UserRole::Admin,
            UserRole::Secretary,
            UserRole::Security,
            UserRole::RtHead,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:submitted,in_progress,resolved,rejected'],
            'version' => ['required', 'integer', 'min:1'], // Optimistic locking
        ];
    }
}
