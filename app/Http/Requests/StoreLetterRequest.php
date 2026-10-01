<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLetterRequest extends FormRequest
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
            'type' => ['nullable', 'string', 'max:100'],
            'letter_type_code' => ['nullable', 'string', 'exists:jenis_surat,kode_surat'],
            'jenis_surat_id' => ['nullable', 'integer', 'exists:jenis_surat,id'],
            'purpose' => ['nullable', 'string', 'max:500'],
            'keperluan' => ['nullable', 'string', 'max:500'],
            'data_tambahan' => ['nullable', 'array'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'attachment_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('type') && ! $this->filled('letter_type_code') && ! $this->filled('jenis_surat_id')) {
                $validator->errors()->add('type', 'Tipe surat atau jenis_surat_id / letter_type_code wajib diisi.');
            }
        });
    }
}
