<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\ResidentDue;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class FinanceReportService
{
    protected string $baseDir = 'private/financial-reports';

    protected string $monthlyDir = 'private/financial-reports/monthly';

    protected string $citizenDuesDir = 'private/financial-reports/citizen-dues';

    protected string $backupsDir = 'private/financial-reports/backups';

    public function __construct(protected AuditService $auditService)
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory($this->monthlyDir);
        $disk->makeDirectory($this->citizenDuesDir);
        $disk->makeDirectory($this->backupsDir);
    }

    /**
     * Generate & Download Laporan Keuangan Bulanan RT
     */
    public function generateMonthlyReport(int $year, int $month, string $format = 'html'): array
    {
        $rtNo = config('rt.rt');
        $rwNo = config('rt.rw');
        $ketuaName = config('rt.officers.ketua');
        $bendaharaName = config('rt.officers.bendahara');
        $disk = Storage::disk('local');
        // Transaksi terkoreksi tetap tampil bersama jurnal pembaliknya agar saldo bersih benar.
        $transactions = FinanceTransaction::query()
            ->countable()
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalIncome = $transactions->where('type', 'income')->sum(fn (FinanceTransaction $t) => $t->amountIdr());
        $totalExpense = $transactions->where('type', 'expense')->sum(fn (FinanceTransaction $t) => $t->amountIdr());
        $balance = $totalIncome - $totalExpense;

        $monthName = date('F', mktime(0, 0, 0, $month, 10));
        $formattedPeriod = "{$monthName} {$year}";

        if ($format === 'csv') {
            $csvContent = "ID,Tanggal,Kategori,Tipe,Jumlah,Keterangan\n";
            foreach ($transactions as $t) {
                $cleanDesc = str_replace(['"', "\n", "\r"], ['""', ' ', ' '], $t->description ?? '');
                $cleanCategory = str_replace(['"', "\n", "\r"], ['""', ' ', ' '], $t->category ?? '');
                $csvContent .= "{$t->id},\"{$t->transaction_date->toDateString()}\",\"{$cleanCategory}\",\"{$t->type}\",{$t->amountIdr()},\"{$cleanDesc}\"\n";
            }
            $fileName = "{$this->monthlyDir}/Laporan_Kas_RT_{$year}_{$month}.csv";
            $disk->put($fileName, $csvContent);

            return [
                'file_path' => $fileName,
                'content' => $csvContent,
                'mime_type' => 'text/csv; charset=UTF-8',
                'download_name' => "Laporan_Kas_RT_{$year}_{$month}.csv",
            ];
        }

        // HTML Printable Format
        $rowsHtml = '';
        foreach ($transactions as $index => $t) {
            $num = $index + 1;
            $typeColor = $t->type === 'income' ? '#059669' : '#DC2626';
            $typeLabel = $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
            $amountFormatted = 'Rp '.number_format($t->amountIdr(), 0, ',', '.');
            $dateFormatted = $t->transaction_date->translatedFormat('d F Y');
            // Kategori & keterangan berasal dari input pengguna: escape agar berkas HTML aman dibuka.
            $category = e($t->category);
            $description = e($t->description);
            $statusNote = $t->status === 'reversed' ? " <em style='color: #DC2626;'>(dikoreksi)</em>" : '';

            $rowsHtml .= "<tr>
                <td style='text-align: center;'>{$num}</td>
                <td>{$dateFormatted}</td>
                <td>{$category}{$statusNote}</td>
                <td style='color: {$typeColor}; font-weight: 600;'>{$typeLabel}</td>
                <td style='text-align: right; font-weight: 600;'>{$amountFormatted}</td>
                <td>{$description}</td>
            </tr>";
        }

        $incomeFormatted = 'Rp '.number_format($totalIncome, 0, ',', '.');
        $expenseFormatted = 'Rp '.number_format($totalExpense, 0, ',', '.');
        $balanceFormatted = 'Rp '.number_format($balance, 0, ',', '.');
        $printedDate = now()->translatedFormat('d F Y H:i');

        $htmlContent = "<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<title>Laporan Keuangan Kas RT - {$formattedPeriod}</title>
<style>
  body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.4; color: #1E293B; margin: 20mm; }
  .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 15px; }
  .header h2 { margin: 0; font-size: 14pt; text-transform: uppercase; }
  .header p { margin: 2px 0; font-size: 10pt; }
  .summary-box { display: flex; justify-content: space-between; margin-bottom: 15px; background: #F8FAFC; border: 1px solid #CBD5E1; padding: 10px 15px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; }
  th, td { border: 1px solid #94A3B8; padding: 6px 8px; }
  th { background: #E2E8F0; font-weight: bold; text-align: center; }
  .signatures { margin-top: 30px; display: flex; justify-content: space-between; page-break-inside: avoid; }
  .sig-block { width: 220px; text-align: center; }
</style>
</head>
<body>
  <div class='header'>
    <h2>RUKUN TETANGGA 01 / RUKUN WARGA 05</h2>
    <p>Kelurahan Contoh, Kecamatan Contoh, Kota Jakarta</p>
    <p style='font-size: 9pt; color: #64748B;'>Subfolder Arsip: storage/app/{$this->monthlyDir}</p>
    <h3 style='margin-top: 8px;'>LAPORAN TRANSPARANSI KAS KEUANGAN BULANAN</h3>
    <p><strong>Periode: {$formattedPeriod}</strong></p>
  </div>

  <table style='margin-bottom: 15px;'>
    <tr>
      <td style='background: #ECFDF5; font-weight: bold;'>Total Kas Masuk (Pemasukan)</td>
      <td style='color: #059669; font-weight: bold; text-align: right;'>{$incomeFormatted}</td>
      <td style='background: #FEF2F2; font-weight: bold;'>Total Kas Keluar (Pengeluaran)</td>
      <td style='color: #DC2626; font-weight: bold; text-align: right;'>{$expenseFormatted}</td>
    </tr>
    <tr>
      <td colspan='2' style='background: #EFF6FF; font-weight: bold; font-size: 11pt;'>Saldo Kas Akhir Periode</td>
      <td colspan='2' style='background: #EFF6FF; font-weight: bold; font-size: 11pt; text-align: right; color: #1E3A8A;'>{$balanceFormatted}</td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th style='width: 35px;'>No</th>
        <th style='width: 100px;'>Tanggal</th>
        <th style='width: 140px;'>Kategori</th>
        <th style='width: 90px;'>Tipe</th>
        <th style='width: 120px;'>Jumlah</th>
        <th>Keterangan / Keperluan</th>
      </tr>
    </thead>
    <tbody>
      {$rowsHtml}
    </tbody>
  </table>

  <div class='signatures'>
    <div class='sig-block'>
      <p>Mengetahui,</p>
      <p style='font-weight: bold;'>Ketua RT {$rtNo} / RW {$rwNo}</p>
      <br><br><br>
      <p style='text-decoration: underline; font-weight: bold;'>( {$ketuaName} )</p>
    </div>
    <div class='sig-block'>
      <p>Dibuat dan Disahkan oleh,</p>
      <p style='font-weight: bold;'>Bendahara RT {$rtNo}</p>
      <br><br><br>
      <p style='text-decoration: underline; font-weight: bold;'>( {$bendaharaName} )</p>
    </div>
  </div>
</body>
</html>";

        $fileName = "{$this->monthlyDir}/Laporan_Kas_RT_{$year}_{$month}.html";
        $disk->put($fileName, $htmlContent);

        return [
            'file_path' => $fileName,
            'content' => $htmlContent,
            'mime_type' => 'text/html; charset=UTF-8',
            'download_name' => "Laporan_Kas_RT_{$year}_{$month}.html",
        ];
    }

    /**
     * Generate & Download Laporan Kas Pembayaran Iuran Warga Per Bulan
     */
    public function generateCitizenDuesReport(int $year, int $month): array
    {
        $rtNo = config('rt.rt');
        $rwNo = config('rt.rw');
        $ketuaName = config('rt.officers.ketua');
        $bendaharaName = config('rt.officers.bendahara');
        $disk = Storage::disk('local');

        // Sumber kebenaran adalah tagihan periode (ResidentDue) beserta pembayarannya,
        // bukan pencocokan teks keterangan transaksi kas.
        $dues = ResidentDue::query()
            ->with(['citizen.familyCard', 'payments' => fn ($q) => $q->where('status', 'PAID')->orderBy('paid_at')])
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->get()
            ->sortBy(fn (ResidentDue $d) => mb_strtolower((string) $d->citizen?->full_name))
            ->values();

        $monthName = date('F', mktime(0, 0, 0, $month, 10));
        $period = "{$monthName} {$year}";

        $rowsHtml = '';
        $totalCollected = 0;
        $paidCount = 0;

        $statusStyles = [
            'PAID' => ['LUNAS', '#ECFDF5', '#059669'],
            'PARTIAL' => ['SEBAGIAN', '#EFF6FF', '#1D4ED8'],
            'OVERDUE' => ['TERLAMBAT', '#FEF2F2', '#DC2626'],
            'UNPAID' => ['BELUM BAYAR', '#FFFBEB', '#D97706'],
        ];

        foreach ($dues as $idx => $due) {
            $num = $idx + 1;
            $paidAmount = $due->getTotalPaidIdr();
            $lastPayment = $due->payments->last();

            [$statusText, $statusBg, $statusColor] = $statusStyles[$due->effectiveStatus()] ?? $statusStyles['UNPAID'];
            $nominalText = 'Rp '.number_format($paidAmount, 0, ',', '.').' / Rp '.number_format((int) $due->amount_due_idr, 0, ',', '.');
            $payDate = $lastPayment?->paid_at ? $lastPayment->paid_at->translatedFormat('d M Y') : '—';

            $totalCollected += $paidAmount;
            if ($due->status === 'PAID') {
                $paidCount++;
            }

            $name = e($due->citizen?->full_name ?? 'Warga #'.$due->citizen_id);
            $address = e($due->citizen?->familyCard?->address ?? '—');

            $rowsHtml .= "<tr>
                <td style='text-align: center;'>{$num}</td>
                <td><strong>{$name}</strong></td>
                <td>{$address}</td>
                <td style='text-align: center; background: {$statusBg}; color: {$statusColor}; font-weight: bold;'>{$statusText}</td>
                <td style='text-align: right;'>{$nominalText}</td>
                <td style='text-align: center;'>{$payDate}</td>
            </tr>";
        }

        $totalCollectedFormatted = 'Rp '.number_format($totalCollected, 0, ',', '.');
        $htmlContent = "<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<title>Kas Pembayaran Iuran Warga - {$period}</title>
<style>
  body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.4; color: #1E293B; margin: 20mm; }
  .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 15px; }
  table { width: 100%; border-collapse: collapse; font-size: 10pt; }
  th, td { border: 1px solid #94A3B8; padding: 6px 8px; }
  th { background: #E2E8F0; font-weight: bold; text-align: center; }
</style>
</head>
<body>
  <div class='header'>
    <h2>REKAPITULASI PEMBAYARAN KAS / IURAN WARGA RT {$rtNo} / RW {$rwNo}</h2>
    <p>Periode: <strong>{$period}</strong> | Arsip Subfolder: <code>{$this->citizenDuesDir}</code></p>
  </div>

  <div style='margin-bottom: 15px; background: #F1F5F9; padding: 10px; border: 1px solid #CBD5E1;'>
    <p>Total Tagihan: <strong>{$dues->count()} Warga</strong> | Warga Telah Lunas: <strong>{$paidCount} Warga</strong> | Total Kas Terhimpun: <strong style='color: #059669;'>{$totalCollectedFormatted}</strong></p>
  </div>

  <table>
    <thead>
      <tr>
        <th style='width: 35px;'>No</th>
        <th>Nama Warga</th>
        <th>Alamat Domisili</th>
        <th style='width: 100px;'>Status Iuran</th>
        <th style='width: 120px;'>Jumlah Bayar</th>
        <th style='width: 110px;'>Tanggal Bayar</th>
      </tr>
    </thead>
    <tbody>
      {$rowsHtml}
    </tbody>
  </table>
</body>
</html>";

        $fileName = "{$this->citizenDuesDir}/Kas_Pembayaran_Warga_{$year}_{$month}.html";
        $disk->put($fileName, $htmlContent);

        return [
            'file_path' => $fileName,
            'content' => $htmlContent,
            'mime_type' => 'text/html; charset=UTF-8',
            'download_name' => "Kas_Pembayaran_Warga_{$year}_{$month}.html",
        ];
    }

    /**
     * Backup Store & Store Data: Creates an Immutable Cryptographically-Hashed Snapshot
     */
    public function createBackupStore(?User $actor = null): array
    {
        $disk = Storage::disk('local');
        $allTransactions = FinanceTransaction::query()->orderBy('id', 'asc')->get();
        $summary = [
            'total_income' => $allTransactions->where('type', 'income')->whereIn('status', FinanceTransaction::COUNTABLE_STATUSES)->sum(fn (FinanceTransaction $t) => $t->amountIdr()),
            'total_expense' => $allTransactions->where('type', 'expense')->whereIn('status', FinanceTransaction::COUNTABLE_STATUSES)->sum(fn (FinanceTransaction $t) => $t->amountIdr()),
            'total_records' => $allTransactions->count(),
            'backup_timestamp' => now()->toIso8601String(),
            'executed_by' => $actor ? ['id' => $actor->id, 'name' => $actor->name, 'role' => $actor->role] : null,
        ];

        $payload = [
            'meta' => [
                'system' => 'Platform Layanan Publik Warga',
                'module' => 'Immutable Financial Ledger Backup Store',
                'subfolder' => $this->backupsDir,
                'created_at' => now()->toIso8601String(),
            ],
            'summary' => $summary,
            'transactions' => $allTransactions->toArray(),
        ];

        $jsonEncoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $checksum = hash('sha256', $jsonEncoded);
        $timestamp = now()->format('Ymd_His');

        $fileName = "{$this->backupsDir}/ledger_backup_{$timestamp}_{$checksum}.json";
        $disk->put($fileName, $jsonEncoded);

        $this->auditService->log(
            action: 'finance.backup_store_created',
            entityType: 'FinanceBackup',
            entityId: (int) time(),
            oldValues: null,
            newValues: [
                'file_path' => $fileName,
                'checksum_sha256' => $checksum,
                'total_records' => $allTransactions->count(),
            ]
        );

        return [
            'backup_file' => $fileName,
            'checksum_sha256' => $checksum,
            'total_records' => $allTransactions->count(),
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * List all available backup stores
     */
    public function listBackups(): array
    {
        $disk = Storage::disk('local');
        $files = $disk->files($this->backupsDir);
        $result = [];

        foreach ($files as $file) {
            $result[] = [
                'file_path' => $file,
                'file_name' => basename($file),
                'size_bytes' => $disk->size($file),
                'last_modified' => date('Y-m-d H:i:s', $disk->lastModified($file)),
            ];
        }

        return $result;
    }
}
