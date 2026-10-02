<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFinanceTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $action = $this->input('action');
        if ($action === 'publish') {
            return $user->hasPermission('finance.transaction.publish') || $user->hasPermission('finance.manage');
        }

        if ($action === 'reverse') {
            return $user->hasPermission('finance.transaction.reverse') || $user->hasPermission('finance.manage');
        }

        return $user->hasPermission('finance.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:publish,reverse'],
            'reason' => ['required_if:action,reverse', 'nullable', 'string'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }
}
