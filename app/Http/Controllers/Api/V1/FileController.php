<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\LetterAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyajian berkas privat (lampiran surat, foto pengaduan, KTP) setelah otorisasi per pemilik/peran.
 */
class FileController extends Controller
{
    public function letterAttachment(Request $request, int $id): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $attachment = LetterAttachment::query()->with('letter')->findOrFail($id);
        $letter = $attachment->letter;

        $isOwner = $user->citizen && $user->citizen->id === $letter->citizen_id;
        $isStaff = $user->hasPermission('letter.read') || $user->hasPermission('letter.verify') || $user->hasPermission('letter.approve');
        abort_unless($isOwner || $isStaff, 403, 'Anda tidak berwenang membuka lampiran ini.');

        return $this->serve($attachment->file_path, $attachment->judul_lampiran ?? basename($attachment->file_path));
    }

    public function complaintPhoto(Request $request, int $id): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $complaint = Complaint::query()->findOrFail($id);

        abort_unless($complaint->user_id === $user->id || $user->hasPermission('complaint.read') || $user->hasPermission('complaint.manage'), 403, 'Anda tidak berwenang membuka foto ini.');

        return $this->serve((string) $complaint->attachment_path, 'pengaduan-'.$complaint->ticket_number);
    }

    public function ktp(Request $request, int $id): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $citizen = Citizen::query()->findOrFail($id);

        $isOwner = $user->citizen && $user->citizen->id === $citizen->id;
        abort_unless($isOwner || $user->hasPermission('citizen.manage'), 403, 'Hanya pengurus kependudukan yang boleh membuka berkas KTP.');

        return $this->serve((string) $citizen->ktp_file_path, 'ktp-'.$citizen->id);
    }

    protected function serve(string $path, string $name): StreamedResponse
    {
        abort_if($path === '' || ! Storage::disk('local')->exists($path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk('local')->response($path, $name, ['Cache-Control' => 'private, no-store']);
    }
}
