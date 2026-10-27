<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $letter_number ?? 'Draf Surat Keterangan' }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; color: #111; margin: 40px; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 25px; }
        .kop h2 { margin: 0; font-size: 16pt; text-transform: uppercase; letter-spacing: 1px; }
        .kop h3 { margin: 2px 0; font-size: 14pt; text-transform: uppercase; font-weight: normal; }
        .kop p { margin: 0; font-size: 10pt; font-style: italic; }
        .judul-surat { text-align: center; margin-bottom: 25px; }
        .judul-surat h4 { margin: 0; font-size: 14pt; text-decoration: underline; text-transform: uppercase; }
        .judul-surat p { margin: 3px 0 0 0; font-size: 11pt; }
        .table-data { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table-data td { padding: 4px 8px; vertical-align: top; }
        .table-data td.label { width: 30%; }
        .table-data td.separator { width: 3%; text-align: center; }
        .content { text-align: justify; margin-bottom: 15px; }
        .signature-block { width: 100%; margin-top: 40px; }
        .signature-block td { width: 50%; vertical-align: top; text-align: center; }
        .ttd-space { height: 75px; }
        .qr-placeholder { border: 1px dashed #888; display: inline-block; padding: 10px 15px; font-size: 9pt; background: #fafafa; border-radius: 4px; margin-top: 5px; }
        .footer-verification { margin-top: 40px; padding-top: 10px; border-top: 1px dashed #999; font-size: 8.5pt; color: #555; text-align: center; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>

    @include('documents.partials.kop')

    <div class="judul-surat">
        <h4>SURAT KETERANGAN</h4>
        <p>Nomor: {{ $letter_number ?? '___/SK/RT'.config('rt.rt').'/___/____' }}</p>
    </div>

    <div class="content">
        <p>Yang bertanda tangan di bawah ini, Pengurus Rukun Tetangga (RT) {{ config('rt.rt') }} RW {{ config('rt.rw') }} Kelurahan {{ config('rt.kelurahan') }}, Kecamatan {{ config('rt.kecamatan') }}, dengan ini menerangkan bahwa:</p>
    </div>

    <table class="table-data">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="separator">:</td>
            <td><strong>{{ $citizen['full_name'] ?? '—' }}</strong></td>
        </tr>
        <tr>
            <td class="label">NIK</td>
            <td class="separator">:</td>
            <td>{{ $citizen['nik'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Tempat / Tgl Lahir</td>
            <td class="separator">:</td>
            <td>{{ $citizen['place_of_birth'] ?? '—' }}, {{ $citizen['date_of_birth'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Kelamin</td>
            <td class="separator">:</td>
            <td>{{ $citizen['gender'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Agama / Pekerjaan</td>
            <td class="separator">:</td>
            <td>{{ $citizen['religion'] ?? '—' }} / {{ $citizen['occupation'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat KTP / Domisili</td>
            <td class="separator">:</td>
            <td>{{ $citizen['address'] ?? '—' }}</td>
        </tr>
    </table>

    <div class="content">
        <p>Orang tersebut di atas adalah benar-benar warga yang berdomisili di lingkungan RT {{ config('rt.rt') }} RW {{ config('rt.rw') }} dan tercatat berkelakuan baik di lingkungan masyarakat.</p>
        <p>Surat keterangan ini diberikan atas permohonan yang bersangkutan untuk keperluan: <strong>{{ $purpose ?? 'Pengurusan administrasi' }}</strong>.</p>
        @if(!empty($description))
        <p>Keterangan pendukung: {{ $description }}</p>
        @endif
        <p>Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.</p>
    </div>

    <table class="signature-block">
        <tr>
            <td>
                <br>
                Pemohon,
                <div class="ttd-space"></div>
                <strong>( {{ $citizen['full_name'] ?? 'Warga' }} )</strong>
            </td>
            <td>
                {{ config('rt.tempat_surat') }}, {{ $issued_at ?? now()->translatedFormat('d F Y') }}<br>
                Ketua RT {{ config('rt.rt') }} RW {{ config('rt.rw') }},
                <div class="ttd-space">
                    @if(!empty($verification_token))
                    <div class="qr-placeholder">
                        ✓ Disetujui Secara Digital<br>
                        Token: {{ substr($verification_token, 0, 16) }}...<br>Diverifikasi: Sekretaris {{ $verifier_name ?? config('rt.officers.sekretaris') }}
                    </div>
                    @endif
                </div>
                <strong>( {{ $officer['name'] ?? config('rt.officers.ketua') }} )</strong>
            </td>
        </tr>
    </table>

    <div class="footer-verification">
        Dokumen ini diterbitkan secara sah melalui Portal Layanan Publik Warga RT {{ config('rt.rt') }} RW {{ config('rt.rw') }}.<br>
        Verifikasi keaslian dokumen dapat diakses secara publik melalui tautan:<br>
        <strong>{{ $verification_url ?? url('/api/v1/public/letter/verify/' . ($verification_token ?? 'TOKEN')) }}</strong>
    </div>

</body>
</html>
