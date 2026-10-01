<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $letter_number ?? 'Draf Surat Pengantar Pindah' }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; color: #111; margin: 40px; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 25px; }
        .kop h2 { margin: 0; font-size: 16pt; text-transform: uppercase; letter-spacing: 1px; }
        .kop h3 { margin: 2px 0; font-size: 14pt; text-transform: uppercase; font-weight: normal; }
        .kop p { margin: 0; font-size: 10pt; font-style: italic; }
        .judul-surat { text-align: center; margin-bottom: 25px; }
        .judul-surat h4 { margin: 0; font-size: 14pt; text-decoration: underline; text-transform: uppercase; }
        .judul-surat p { margin: 3px 0 0 0; font-size: 11pt; }
        .table-data { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .table-data td { padding: 3px 8px; vertical-align: top; }
        .table-data td.label { width: 32%; }
        .table-data td.separator { width: 3%; text-align: center; }
        .content { text-align: justify; margin-bottom: 15px; }
        .section-title { font-weight: bold; margin-top: 15px; margin-bottom: 5px; text-decoration: underline; }
        .signature-block { width: 100%; margin-top: 35px; }
        .signature-block td { width: 50%; vertical-align: top; text-align: center; }
        .ttd-space { height: 75px; }
        .qr-placeholder { border: 1px dashed #888; display: inline-block; padding: 10px 15px; font-size: 9pt; background: #fafafa; border-radius: 4px; margin-top: 5px; }
        .footer-verification { margin-top: 40px; padding-top: 10px; border-top: 1px dashed #999; font-size: 8.5pt; color: #555; text-align: center; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>

    <div class="kop">
        <h2>Rukun Tetangga 01 / Rukun Warga 05</h2>
        <h3>Kelurahan Sejahtera, Kecamatan Bahagia</h3>
        <p>Sekretariat: Balai Warga RT 01 RW 05, Telp/WhatsApp: 0812-3456-7890</p>
    </div>

    <div class="judul-surat">
        <h4>SURAT PENGANTAR PINDAH DOMISILI</h4>
        <p>Nomor: {{ $letter_number ?? '___/SKP/RT01/___/____' }}</p>
    </div>

    <div class="content">
        <p>Pengurus Rukun Tetangga (RT) 01 RW 05 Kelurahan Sejahtera, dengan ini memberikan surat pengantar permohonan kepindahan warga kepada:</p>
    </div>

    <table class="table-data">
        <tr>
            <td class="label">Nama Kepala Keluarga / Pemohon</td>
            <td class="separator">:</td>
            <td><strong>{{ $citizen['full_name'] ?? '—' }}</strong></td>
        </tr>
        <tr>
            <td class="label">NIK</td>
            <td class="separator">:</td>
            <td>{{ $citizen['nik'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Kartu Keluarga (KK)</td>
            <td class="separator">:</td>
            <td>{{ $citizen['no_kk'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Asal</td>
            <td class="separator">:</td>
            <td>{{ $citizen['address'] ?? 'RT 01 RW 05 Kelurahan Sejahtera' }}</td>
        </tr>
    </table>

    <div class="section-title">Data Alamat Tujuan Pindah:</div>
    <table class="table-data">
        <tr>
            <td class="label">Alamat Tujuan</td>
            <td class="separator">:</td>
            <td>{{ $destination['address'] ?? ($data_tambahan['destination_address'] ?? '—') }}</td>
        </tr>
        <tr>
            <td class="label">Kelurahan / Desa</td>
            <td class="separator">:</td>
            <td>{{ $destination['kelurahan'] ?? ($data_tambahan['destination_kelurahan'] ?? '—') }}</td>
        </tr>
        <tr>
            <td class="label">Kecamatan</td>
            <td class="separator">:</td>
            <td>{{ $destination['kecamatan'] ?? ($data_tambahan['destination_kecamatan'] ?? '—') }}</td>
        </tr>
        <tr>
            <td class="label">Kota / Kabupaten</td>
            <td class="separator">:</td>
            <td>{{ $destination['city'] ?? ($data_tambahan['destination_city'] ?? '—') }}</td>
        </tr>
        <tr>
            <td class="label">Provinsi</td>
            <td class="separator">:</td>
            <td>{{ $destination['province'] ?? ($data_tambahan['destination_province'] ?? '—') }}</td>
        </tr>
        <tr>
            <td class="label">Rencana Tanggal Pindah</td>
            <td class="separator">:</td>
            <td>{{ $destination['moving_date'] ?? ($data_tambahan['moving_date'] ?? '—') }}</td>
        </tr>
        <tr>
            <td class="label">Alasan Kepindahan</td>
            <td class="separator">:</td>
            <td>{{ $destination['reason'] ?? ($data_tambahan['reason'] ?? 'Pindah tempat tinggal') }}</td>
        </tr>
    </table>

    <div class="content">
        <p>Sepanjang pengetahuan dan catatan kami, pemohon tidak memiliki tanggungan kas warga maupun permasalahan sosial lainnya di lingkungan RT 01 RW 05.</p>
        <p>Demikian surat pengantar pindah ini diberikan untuk dipergunakan dalam pengurusan Surat Keterangan Pindah Warga Negara Indonesia (SKPWNI) di tingkat Kelurahan dan Disdukcapil.</p>
    </div>

    <table class="signature-block">
        <tr>
            <td>
                Pemohon,
                <div class="ttd-space"></div>
                <strong>( {{ $citizen['full_name'] ?? 'Pemohon' }} )</strong>
            </td>
            <td>
                Jakarta, {{ $issued_at ?? now()->translatedFormat('d F Y') }}<br>
                Ketua RT 01 RW 05,
                <div class="ttd-space">
                    @if(!empty($verification_token))
                    <div class="qr-placeholder">
                        ✓ Disetujui Secara Digital<br>
                        Token: {{ substr($verification_token, 0, 16) }}...
                    </div>
                    @endif
                </div>
                <strong>( {{ $officer['name'] ?? 'Ketua RT' }} )</strong>
            </td>
        </tr>
    </table>

    <div class="footer-verification">
        Dokumen resmi diterbitkan melalui Portal Layanan Publik Warga RT 01 RW 05.<br>
        Verifikasi keaslian dokumen secara publik: <strong>{{ $verification_url ?? url('/api/v1/public/letter/verify/' . ($verification_token ?? 'TOKEN')) }}</strong>
    </div>

</body>
</html>
