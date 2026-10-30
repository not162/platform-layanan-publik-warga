<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\LetterAttachment;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyajian berkas privat (lampiran surat, foto pengaduan, KTP, nota belanja) setelah otorisasi per pemilik/peran.
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

    public function avatar(int $id): StreamedResponse
    {
        $user = User::query()->findOrFail($id);

        return $this->serve((string) $user->avatar_url, 'avatar-'.$user->id);
    }

    public function purchaseReceipt(Request $request, int $id): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        // Aturan sama dengan PurchaseController (daftar belanja): finance.purchase.read atau finance.manage.
        abort_unless(
            $user->hasPermission('finance.purchase.read') || $user->hasPermission('finance.manage'),
            403,
            'Hanya pengurus keuangan yang boleh membuka nota belanja.'
        );

        $purchase = Purchase::query()->findOrFail($id);
        $path = (string) $purchase->receipt_path;
        $name = 'nota-belanja-'.$purchase->id.($path !== '' ? '.'.pathinfo($path, PATHINFO_EXTENSION) : '');

        // Nota lama tersimpan di disk publik; sajikan tetap lewat jalur berotorisasi ini.
        if ($path !== '' && ! Storage::disk('local')->exists($path) && Storage::disk('public')->exists($path)) {
            return $this->serve($path, $name, 'public');
        }

        return $this->serve($path, $name);
    }

    protected function serve(string $path, string $name, string $disk = 'local'): StreamedResponse
    {
        abort_if($path === '' || ! Storage::disk($disk)->exists($path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk($disk)->response($path, $name, ['Cache-Control' => 'private, no-store']);
    }
}
