<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\Complaint;
use App\Models\EmergencyContact;
use App\Models\FinanceTransaction;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\RoundSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class PortalContentSeeder extends Seeder
{
    /**
     * Konten awal beranda + contoh transaksi/surat agar dashboard tidak kosong (lokal/testing saja).
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $announcements = [
            ['Kerja Bakti Bulanan', 'kegiatan', 'Kerja bakti rutin pembersihan lingkungan RT 04. Seluruh warga diharapkan berpartisipasi. Peralatan disediakan pengurus.', true],
            ['Jadwal Ronda Malam Diperbarui', 'info', 'Jadwal ronda malam telah diperbarui. Silakan cek jadwal ronda atau hubungi Seksi Keamanan.', false],
            ['Laporan Keuangan Tersedia', 'keuangan', 'Laporan kas bulan berjalan sudah tersedia. Transparansi penuh untuk warga RT 04.', false],
        ];
        foreach ($announcements as [$title, $category, $content, $pinned]) {
            Announcement::query()->firstOrCreate(['title' => $title], [
                'category' => $category, 'content' => $content, 'is_pinned' => $pinned, 'is_published' => true, 'published_at' => now(), 'version' => 1,
            ]);
        }

        CommunityEvent::query()->firstOrCreate(['title' => 'Musyawarah Warga RT 04'], [
            'description' => 'Pembahasan iuran, keamanan lingkungan, dan rencana kegiatan bulan depan.',
            'location' => 'Balai Warga Gg. Bugis', 'event_date' => now()->addDays(7)->toDateString(), 'start_time' => '19:30', 'end_time' => '21:30',
            'is_published' => true, 'version' => 1,
        ]);

        foreach ([['Polisi (Polsek Kemayoran)', 'Keamanan', '110'], ['Pemadam Kebakaran', 'Darurat', '113'], ['Ambulans', 'Kesehatan', '119'], ['WhatsApp Ketua RT / Admin', 'Pengurus', '+62 878-7200-4448']] as $i => [$name, $cat, $phone]) {
            EmergencyContact::query()->firstOrCreate(['name' => $name], ['category' => $cat, 'phone_number' => $phone, 'order_index' => $i, 'is_active' => true]);
        }

        RoundSchedule::query()->firstOrCreate(['day_of_week' => 'Sabtu', 'shift_name' => 'Malam 22.00–04.00'], [
            'officer_names' => ['Pak Andi', 'Pak Rudi', 'Pak Dedi'], 'pos_location' => 'Pos Ronda Gg. Bugis', 'is_active' => true,
        ]);

        $bendahara = User::query()->where('username', 'bendahara')->first();
        if ($bendahara && FinanceTransaction::query()->doesntExist()) {
            $rows = [
                ['income', 'Iuran Warga', 2500000, 'Iuran kebersihan & keamanan bulan ini', 'published'],
                ['expense', 'Kebersihan', 450000, 'Upah petugas kebersihan', 'published'],
                ['expense', 'Keamanan', 600000, 'Honor petugas ronda', 'published'],
                ['income', 'Donasi', 300000, 'Donasi warga untuk kegiatan 17 Agustus', 'draft'],
            ];
            foreach ($rows as $n => [$type, $cat, $amount, $desc, $status]) {
                FinanceTransaction::query()->create([
                    'transaction_number' => sprintf('TXN-%s-%06d', now()->format('Ym'), 100001 + $n),
                    'type' => $type, 'source' => 'other', 'category' => $cat, 'amount' => $amount, 'amount_idr' => $amount,
                    'description' => $desc, 'transaction_date' => now()->subDays(4 - $n)->toDateString(), 'status' => $status,
                    'published_at' => $status === 'published' ? now() : null, 'created_by' => $bendahara->id, 'version' => 1,
                ]);
            }
        }

        $warga = User::query()->where('username', 'warga')->with('citizen')->first();
        $type = LetterType::query()->first();
        if ($warga?->citizen && $type && Letter::query()->doesntExist()) {
            Letter::query()->create([
                'citizen_id' => $warga->citizen->id, 'jenis_surat_id' => $type->id, 'type' => $type->nama_surat,
                'ticket_number' => 'LTR-'.now()->format('Ymd').'-0001', 'keperluan' => 'Pengurusan izin usaha mikro di kelurahan',
                'status' => 'submitted', 'version' => 2,
            ]);
        }
        if ($warga && Complaint::query()->doesntExist()) {
            Complaint::query()->create([
                'ticket_number' => 'CMP-'.now()->format('Ymd').'-0001', 'citizen_id' => $warga->citizen?->id, 'user_id' => $warga->id,
                'kategori' => 'Fasilitas Umum', 'title' => 'Lampu jalan Gg. Bugis mati', 'description' => 'Lampu jalan di depan No. 90 mati sejak 3 hari lalu, area gelap saat malam.',
                'lokasi' => 'Gg. Bugis depan No. 90', 'status' => 'submitted', 'priority' => 'tinggi', 'version' => 1,
            ]);
        }
    }
}
