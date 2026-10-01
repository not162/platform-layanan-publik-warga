<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public/citizens can submit (including anonymously)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'kategori' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:80'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'street_name' => ['nullable', 'string', 'max:150'],
            'location_detail' => ['nullable', 'string', 'max:150'],
            'priority' => ['nullable', 'string', 'in:rendah,sedang,tinggi,darurat'],
            'is_anonymous' => ['nullable', 'boolean'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'attachment_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
