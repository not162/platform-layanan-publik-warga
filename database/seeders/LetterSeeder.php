<?php

namespace Database\Seeders;

use App\Models\LetterType;
use Illuminate\Database\Seeder;

class LetterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'kode_surat' => 'SK-UMUM',
                'nama_surat' => 'Surat Keterangan Umum',
                'template_key' => 'surat-keterangan',
                'template_version' => 1,
                'format_penomoran' => 'SK/{nomor}/RT01/{bulan}/{tahun}',
                'syarat_dokumen' => [
                    'Scan KTP Pemohon',
                    'Scan Kartu Keluarga (KK)',
                ],
                'form_schema' => [
                    'fields' => [
                        [
                            'name' => 'purpose',
                            'label' => 'Keperluan Surat',
                            'type' => 'textarea',
                            'required' => true,
                            'placeholder' => 'Contoh: Pengurusan izin usaha mikro di kelurahan',
                            'max_length' => 500,
                        ],
                        [
                            'name' => 'description',
                            'label' => 'Keterangan Tambahan',
                            'type' => 'textarea',
                            'required' => false,
                            'placeholder' => 'Jelaskan rincian pendukung jika ada',
                            'max_length' => 1000,
                        ],
                    ],
                ],
                'approval_flow' => ['sekretaris_verify', 'ketua_rt_approve'],
                'estimated_process_hours' => 24,
                'is_active' => true,
            ],
            [
                'kode_surat' => 'SK-KEMATIAN',
                'nama_surat' => 'Surat Keterangan Kematian',
                'template_key' => 'surat-kematian',
                'template_version' => 1,
                'format_penomoran' => 'SKK/{nomor}/RT01/{bulan}/{tahun}',
                'syarat_dokumen' => [
                    'Scan KTP Pelapor',
                    'Scan Kartu Keluarga Jenazah',
                    'Surat Kematian Rumah Sakit/Puskesmas (bila ada)',
                ],
                'form_schema' => [
                    'fields' => [
                        ['name' => 'deceased_name', 'label' => 'Nama Almarhum / Almarhumah', 'type' => 'text', 'required' => true],
                        ['name' => 'deceased_nik', 'label' => 'NIK Almarhum / Almarhumah', 'type' => 'text', 'required' => true],
                        ['name' => 'death_date', 'label' => 'Tanggal Meninggal', 'type' => 'date', 'required' => true],
                        ['name' => 'death_time', 'label' => 'Waktu Meninggal', 'type' => 'time', 'required' => false],
                        ['name' => 'death_place', 'label' => 'Tempat Meninggal', 'type' => 'text', 'required' => true],
                        ['name' => 'cause_of_death', 'label' => 'Penyebab Meninggal', 'type' => 'text', 'required' => false],
                        ['name' => 'reporter_relationship', 'label' => 'Hubungan Pelapor dengan Jenazah', 'type' => 'text', 'required' => true],
                        ['name' => 'notes', 'label' => 'Catatan Tambahan', 'type' => 'textarea', 'required' => false],
                    ],
                ],
                'approval_flow' => ['sekretaris_verify', 'ketua_rt_approve'],
                'estimated_process_hours' => 12,
                'is_active' => true,
            ],
            [
                'kode_surat' => 'SK-PINDAH',
                'nama_surat' => 'Surat Pengantar Pindah Domisili',
                'template_key' => 'surat-pindah',
                'template_version' => 1,
                'format_penomoran' => 'SKP/{nomor}/RT01/{bulan}/{tahun}',
                'syarat_dokumen' => [
                    'Scan KTP Kepala Keluarga',
                    'Scan Kartu Keluarga Asli',
                    'Bukti Lunas Iuran Kas Warga',
                ],
                'form_schema' => [
                    'fields' => [
                        ['name' => 'destination_address', 'label' => 'Alamat Lengkap Tujuan Pindah', 'type' => 'textarea', 'required' => true],
                        ['name' => 'destination_kelurahan', 'label' => 'Kelurahan / Desa Tujuan', 'type' => 'text', 'required' => true],
                        ['name' => 'destination_kecamatan', 'label' => 'Kecamatan Tujuan', 'type' => 'text', 'required' => true],
                        ['name' => 'destination_city', 'label' => 'Kota / Kabupaten Tujuan', 'type' => 'text', 'required' => true],
                        ['name' => 'destination_province', 'label' => 'Provinsi Tujuan', 'type' => 'text', 'required' => true],
                        ['name' => 'moving_date', 'label' => 'Rencana Tanggal Pindah', 'type' => 'date', 'required' => true],
                        ['name' => 'reason', 'label' => 'Alasan Kepindahan', 'type' => 'text', 'required' => true],
                    ],
                ],
                'approval_flow' => ['sekretaris_verify', 'ketua_rt_approve'],
                'estimated_process_hours' => 48,
                'is_active' => true,
            ],
            [
                'kode_surat' => 'SKTM',
                'nama_surat' => 'Surat Keterangan Tidak Mampu (SKTM)',
                'template_key' => 'surat-keterangan-tidak-mampu',
                'template_version' => 1,
                'format_penomoran' => 'SKTM/{nomor}/RT01/{bulan}/{tahun}',
                'syarat_dokumen' => [
                    'Scan KTP Pemohon',
                    'Scan Kartu Keluarga (KK)',
                    'Foto Rumah Tempat Tinggal',
                    'Surat Pernyataan Penghasilan',
                ],
                'form_schema' => [
                    'fields' => [
                        ['name' => 'purpose', 'label' => 'Keperluan Pengajuan SKTM (Beasiswa/Kesehatan/dll)', 'type' => 'text', 'required' => true],
                        ['name' => 'occupation', 'label' => 'Pekerjaan Saat Ini', 'type' => 'text', 'required' => false],
                        ['name' => 'monthly_income', 'label' => 'Estimasi Rata-rata Penghasilan Bulanan (Rp)', 'type' => 'number', 'required' => false],
                        ['name' => 'dependents', 'label' => 'Jumlah Tanggungan Keluarga', 'type' => 'number', 'required' => true],
                        ['name' => 'additional_information', 'label' => 'Kondisi Ekonomi / Keterangan Tambahan', 'type' => 'textarea', 'required' => false],
                    ],
                ],
                'approval_flow' => ['sekretaris_verify', 'ketua_rt_approve'],
                'estimated_process_hours' => 24,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            LetterType::query()->updateOrCreate(
                ['kode_surat' => $type['kode_surat']],
                $type
            );
        }
    }
}
