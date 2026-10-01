<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('complaint.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:submitted,reviewed,processing,resolved,closed,rejected,in_progress'],
            'admin_response' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'in:rendah,sedang,tinggi,darurat'],
            'version' => ['required', 'integer', 'min:1'], // Optimistic locking
        ];
    }
}
