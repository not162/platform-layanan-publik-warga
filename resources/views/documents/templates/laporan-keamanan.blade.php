<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $ticket_number ?? 'Laporan Keamanan Lingkungan' }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; color: #111; margin: 40px; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 25px; }
        .kop h2 { margin: 0; font-size: 16pt; text-transform: uppercase; letter-spacing: 1px; }
        .kop h3 { margin: 2px 0; font-size: 14pt; text-transform: uppercase; font-weight: normal; }
        .kop p { margin: 0; font-size: 10pt; font-style: italic; }
        .judul-surat { text-align: center; margin-bottom: 25px; }
        .judul-surat h4 { margin: 0; font-size: 14pt; text-decoration: underline; text-transform: uppercase; }
        .judul-surat p { margin: 3px 0 0 0; font-size: 11pt; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 8px; border: 1px solid #333; font-size: 9pt; text-transform: uppercase; border-radius: 3px; }
        .badge-emergency { background: #ffebee; border-color: #c62828; color: #c62828; font-weight: bold; }
        .table-data { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .table-data td { padding: 4px 8px; vertical-align: top; }
        .table-data td.label { width: 30%; }
        .table-data td.separator { width: 3%; text-align: center; }
        .box-laporan { border: 1px solid #777; padding: 12px 16px; margin: 15px 0; background: #fafafa; border-radius: 4px; }
        .signature-block { width: 100%; margin-top: 40px; }
        .signature-block td { width: 50%; vertical-align: top; text-align: center; }
        .ttd-space { height: 75px; }
        .footer-notice { margin-top: 35px; padding-top: 10px; border-top: 1px dashed #999; font-size: 8.5pt; color: #666; text-align: center; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>

    <div class="kop">
        <h2>Seksi Keamanan & Ketertiban Warga</h2>
        <h3>Rukun Tetangga 01 / Rukun Warga 05</h3>
        <p>Sekretariat Pos Ronda & Balai Warga RT 01 RW 05, Kontak Darurat: 112 / 110</p>
    </div>

    <div class="judul-surat">
        <h4>BERITA ACARA LAPORAN KEAMANAN & KETERTIBAN</h4>
        <p>Nomor Tiket: {{ $ticket_number ?? 'SEC-___-____' }}</p>
    </div>

    <table class="table-data">
        <tr>
            <td class="label">Tanggal & Waktu Kejadian</td>
            <td class="separator">:</td>
            <td><strong>{{ $incident_at ?? now()->translatedFormat('d F Y, H:i') }} WIB</strong></td>
        </tr>
        <tr>
            <td class="label">Kategori Insiden</td>
            <td class="separator">:</td>
            <td>{{ strtoupper($category ?? 'Umum') }}</td>
        </tr>
        <tr>
            <td class="label">Tingkat Kegentingan (Severity)</td>
            <td class="separator">:</td>
            <td>
                <span class="badge {{ ($severity ?? '') === 'emergency' ? 'badge-emergency' : '' }}">
                    {{ strtoupper($severity ?? 'MEDIUM') }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="label">Lokasi Tempat Kejadian (TKP)</td>
            <td class="separator">:</td>
            <td>{{ $location ?? 'Lingkungan RT 01' }}</td>
        </tr>
        <tr>
            <td class="label">Status Penanganan</td>
            <td class="separator">:</td>
            <td><strong>{{ strtoupper($status ?? 'SUBMITTED') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Identitas Pelapor</td>
            <td class="separator">:</td>
            <td>
                @if(!empty($is_anonymous))
                    <em>(Dirahasiakan / Anonim atas permintaan pelapor)</em>
                @else
                    {{ $reporter_name ?? 'Warga RT 01' }}
                @endif
            </td>
        </tr>
    </table>

    <div class="box-laporan">
        <strong>Judul Laporan:</strong> {{ $title ?? '—' }}<br><br>
        <strong>Uraian Kejadian:</strong><br>
        <p style="text-align: justify; margin-top: 5px;">{{ $description ?? '—' }}</p>
    </div>

    @if(!empty($resolution))
    <div class="box-laporan" style="background: #f1f8e9; border-color: #558b2f;">
        <strong>Tindakan / Hasil Resolusi Petugas Keamanan:</strong><br>
        <p style="text-align: justify; margin-top: 5px;">{{ $resolution }}</p>
        <small>Diselesaikan pada: {{ $resolved_at ?? '-' }}</small>
    </div>
    @endif

    <table class="signature-block">
        <tr>
            <td>
                Petugas Jaga / Pemeriksa,
                <div class="ttd-space"></div>
                <strong>( {{ $officer_name ?? 'Petugas Keamanan RT' }} )</strong>
            </td>
            <td>
                Mengetahui,<br>
                Ketua RT 01 RW 05,
                <div class="ttd-space"></div>
                <strong>( {{ $ketua_rt_name ?? 'Ketua RT' }} )</strong>
            </td>
        </tr>
    </table>

    <div class="footer-notice">
        <strong>Pemberitahuan:</strong> Dokumen laporan keamanan lingkungan internal ini tidak menggantikan laporan resmi pihak Kepolisian Negara Republik Indonesia (Polri) atau Layanan Darurat 112 / 110. Dalam situasi darurat kriminalitas berat atau kebakaran, segera hubungi instansi darurat resmi.
    </div>

</body>
</html>
