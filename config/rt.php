<?php

/*
|--------------------------------------------------------------------------
| Identitas Lingkungan & Pejabat Penandatangan
|--------------------------------------------------------------------------
| Dipakai pada kop surat (HTML/PDF/DOCX), laporan, dan tampilan. Ubah lewat .env
| atau langsung di sini; tidak perlu mengubah template surat.
*/

return [
    'rt' => env('RT_NUMBER', '04'),
    'rw' => env('RW_NUMBER', '09'),
    'kelurahan' => env('RT_KELURAHAN', 'Kemayoran'),
    'kecamatan' => env('RT_KECAMATAN', 'Kemayoran'),
    'kota' => env('RT_KOTA', 'Jakarta Pusat'),
    'provinsi' => env('RT_PROVINSI', 'DKI Jakarta'),
    'tempat_surat' => env('RT_TEMPAT_SURAT', 'Jakarta'),

    'secretariat' => [
        'address' => env('RT_ADDRESS', 'Gg. Bugis No. 95, RT 04 / RW 09, Kemayoran, Jakarta Pusat 10620'),
        'phone' => env('RT_PHONE', '+62 878-7200-4448'),
        'email' => env('RT_EMAIL', 'swandrax@gmail.com'),
    ],

    'officers' => [
        'ketua' => env('RT_KETUA', 'Raden Erry Adu Sundaru'),
        'sekretaris' => env('RT_SEKRETARIS', 'Melly'),
        'bendahara' => env('RT_BENDAHARA', 'Retno Fellyanti'),
    ],
];
