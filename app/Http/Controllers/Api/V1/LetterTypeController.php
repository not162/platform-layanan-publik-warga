<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LetterType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LetterTypeController extends Controller
{
    /**
     * Public / Warga: List active letter types.
     */
    public function index(): JsonResponse
    {
        $types = LetterType::query()
            ->where('is_active', true)
            ->get([
                'id',
                'kode_surat',
                'nama_surat',
                'template_key',
                'syarat_dokumen',
                'estimated_process_hours',
                'is_active',
            ]);

        return response()->json([
            'data' => $types,
            'message' => 'Katalog layanan surat berhasil dimuat.',
        ]);
    }

    /**
     * Public / Warga: Show detail of a letter type by code or id.
     */
    public function show(string $code): JsonResponse
    {
        $type = LetterType::query()
            ->where('kode_surat', $code)
            ->orWhere('id', $code)
            ->first();

        if (! $type) {
            return response()->json([
                'data' => null,
                'message' => 'Layanan surat tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $type,
            'message' => 'Detail layanan surat berhasil dimuat.',
        ]);
    }

    /**
     * Warga / Frontend: Dynamic form schema for letter generation.
     */
    public function formSchema(string $code): JsonResponse
    {
        $type = LetterType::query()
            ->where('kode_surat', $code)
            ->orWhere('id', $code)
            ->first();

        if (! $type) {
            return response()->json([
                'data' => null,
                'message' => 'Jenis surat tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => [
                'kode_surat' => $type->kode_surat,
                'nama_surat' => $type->nama_surat,
                'template_key' => $type->template_key,
                'template_version' => $type->template_version,
                'form_schema' => $type->form_schema ?? ['fields' => []],
                'syarat_dokumen' => $type->syarat_dokumen ?? [],
                'estimated_process_hours' => $type->estimated_process_hours,
            ],
            'message' => 'Skema formulir surat berhasil dimuat.',
        ]);
    }

    /**
     * Admin: List all letter types.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $types = LetterType::query()->latest()->get();

        return response()->json([
            'data' => $types,
            'message' => 'Daftar master jenis surat berhasil dimuat.',
        ]);
    }

    /**
     * Admin: Create new letter type.
     */
    public function adminStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_surat' => ['required', 'string', 'max:30', 'unique:jenis_surat,kode_surat'],
            'nama_surat' => ['required', 'string', 'max:150'],
            'template_key' => ['nullable', 'string', 'max:100'],
            'format_penomoran' => ['nullable', 'string', 'max:100'],
            'syarat_dokumen' => ['nullable', 'array'],
            'form_schema' => ['nullable', 'array'],
            'approval_flow' => ['nullable', 'array'],
            'estimated_process_hours' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $letterType = LetterType::create($validated);

        return response()->json([
            'data' => $letterType,
            'message' => 'Master jenis surat berhasil dibuat.',
        ], Response::HTTP_CREATED);
    }

    /**
     * Admin: Update letter type.
     */
    public function adminUpdate(Request $request, string $id): JsonResponse
    {
        $letterType = LetterType::query()->findOrFail($id);

        $validated = $request->validate([
            'kode_surat' => ['nullable', 'string', 'max:30', 'unique:jenis_surat,kode_surat,'.$letterType->id],
            'nama_surat' => ['nullable', 'string', 'max:150'],
            'template_key' => ['nullable', 'string', 'max:100'],
            'template_version' => ['nullable', 'integer', 'min:1'],
            'format_penomoran' => ['nullable', 'string', 'max:100'],
            'syarat_dokumen' => ['nullable', 'array'],
            'form_schema' => ['nullable', 'array'],
            'approval_flow' => ['nullable', 'array'],
            'estimated_process_hours' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $letterType->update($validated);

        return response()->json([
            'data' => $letterType,
            'message' => 'Master jenis surat berhasil diperbarui.',
        ]);
    }
}
