<?php

namespace App\Services;

use App\Models\Citizen;
use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $disk = Storage::disk('local');
        $transactions = FinanceTransaction::query()
            ->where('status', 'published')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->orderBy('transaction_date', 'asc')
            ->get();

        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        $monthName = date('F', mktime(0, 0, 0, $month, 10));
        $formattedPeriod = "{$monthName} {$year}";

        if ($format === 'csv') {
            $csvContent = "ID,Tanggal,Kategori,Tipe,Jumlah,Keterangan\n";
            foreach ($transactions as $t) {
                $cleanDesc = str_replace(['"', "\n", "\r"], ['""', ' ', ' '], $t->description ?? '');
                $csvContent .= "{$t->id},\"{$t->transaction_date->toDateString()}\",\"{$t->category}\",\"{$t->type}\",{$t->amount},\"{$cleanDesc}\"\n";
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
            $amountFormatted = 'Rp '.number_format($t->amount, 0, ',', '.');
            $dateFormatted = $t->transaction_date->translatedFormat('d F Y');

            $rowsHtml .= "<tr>
                <td style='text-align: center;'>{$num}</td>
                <td>{$dateFormatted}</td>
                <td>{$t->category}</td>
                <td style='color: {$typeColor}; font-weight: 600;'>{$typeLabel}</td>
                <td style='text-align: right; font-weight: 600;'>{$amountFormatted}</td>
                <td>{$t->description}</td>
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
      <p style='font-weight: bold;'>Ketua RT 01</p>
      <br><br><br>
      <p style='text-decoration: underline; font-weight: bold;'>( Bpk. Ketua RT )</p>
    </div>
    <div class='sig-block'>
      <p>Dibuat dan Disahkan oleh,</p>
      <p style='font-weight: bold;'>Bendahara RT 01</p>
      <br><br><br>
      <p style='text-decoration: underline; font-weight: bold;'>( Bendahara RT )</p>
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
        $disk = Storage::disk('local');
        $citizens = Citizen::query()->with('familyCard')->get();
        $duesTransactions = FinanceTransaction::query()
            ->where('status', 'published')
            ->where('type', 'income')
            ->where(function ($q) {
                $q->where('category', 'like', '%Iuran%')
                    ->orWhere('category', 'like', '%Warga%');
            })
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->get();

        $monthName = date('F', mktime(0, 0, 0, $month, 10));
        $period = "{$monthName} {$year}";

        $rowsHtml = '';
        $totalCollected = 0;
        $paidCount = 0;

        foreach ($citizens as $idx => $citizen) {
            $num = $idx + 1;
            $paid = $duesTransactions->first(function ($t) use ($citizen) {
                return Str::contains($t->description ?? '', $citizen->full_name) || Str::contains($t->description ?? '', (string) $citizen->id);
            });

            $statusText = $paid ? 'LUNAS' : 'BELUM BAYAR';
            $statusBg = $paid ? '#ECFDF5' : '#FFFBEB';
            $statusColor = $paid ? '#059669' : '#D97706';
            $nominalText = $paid ? 'Rp '.number_format($paid->amount, 0, ',', '.') : 'Rp 0';
            $payDate = $paid ? $paid->transaction_date->translatedFormat('d M Y') : '—';

            if ($paid) {
                $totalCollected += $paid->amount;
                $paidCount++;
            }

            $rowsHtml .= "<tr>
                <td style='text-align: center;'>{$num}</td>
                <td><strong>{$citizen->full_name}</strong></td>
                <td>{$citizen->address}</td>
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
    <h2>REKAPITULASI PEMBAYARAN KAS / IURAN WARGA RT 01</h2>
    <p>Periode: <strong>{$period}</strong> | Arsip Subfolder: <code>{$this->citizenDuesDir}</code></p>
  </div>

  <div style='margin-bottom: 15px; background: #F1F5F9; padding: 10px; border: 1px solid #CBD5E1;'>
    <p>Total Warga: <strong>{$citizens->count()} Warga</strong> | Warga Telah Bayar: <strong>{$paidCount} Warga</strong> | Total Kas Terhimpun: <strong style='color: #059669;'>{$totalCollectedFormatted}</strong></p>
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
            'total_income' => $allTransactions->where('type', 'income')->where('status', 'published')->sum('amount'),
            'total_expense' => $allTransactions->where('type', 'expense')->where('status', 'published')->sum('amount'),
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
