<?php

namespace App\Enums;

enum Permission: string
{
    // Finance Transactions
    case FINANCE_TRANSACTION_READ = 'finance.transaction.read';
    case FINANCE_TRANSACTION_CREATE = 'finance.transaction.create';
    case FINANCE_TRANSACTION_PUBLISH = 'finance.transaction.publish';
    case FINANCE_TRANSACTION_REVERSE = 'finance.transaction.reverse';

    // Resident Dues
    case FINANCE_DUES_READ = 'finance.dues.read';
    case FINANCE_DUES_MANAGE = 'finance.dues.manage';

    // Due Payments
    case FINANCE_PAYMENT_READ = 'finance.payment.read';
    case FINANCE_PAYMENT_MANAGE = 'finance.payment.manage';

    // Purchases & Transparency
    case FINANCE_PURCHASE_READ = 'finance.purchase.read';
    case FINANCE_PURCHASE_MANAGE = 'finance.purchase.manage';

    // Financial Reports
    case FINANCE_REPORT_READ = 'finance.report.read';
    case FINANCE_REPORT_GENERATE = 'finance.report.generate';
    case FINANCE_REPORT_PUBLISH = 'finance.report.publish';

    // Auditing
    case FINANCE_AUDIT_READ = 'finance.audit.read';
    case DOWNLOAD_AUDIT_READ = 'download.audit.read';

    // Citizens Administration
    case CITIZEN_READ = 'citizen.read';
    case CITIZEN_MANAGE = 'citizen.manage';

    // Letters Administration
    case LETTER_READ = 'letter.read';
    case LETTER_CREATE = 'letter.create';
    case LETTER_VERIFY = 'letter.verify';
    case LETTER_APPROVE = 'letter.approve';
    case LETTER_REJECT = 'letter.reject';
    case LETTER_COMPLETE = 'letter.complete';
    case LETTER_TEMPLATE_MANAGE = 'letter.template.manage';

    // Complaints
    case COMPLAINT_READ = 'complaint.read';
    case COMPLAINT_MANAGE = 'complaint.manage';

    // Security & Environment
    case SECURITY_READ = 'security.read';
    case SECURITY_MANAGE = 'security.manage';

    // Announcements & Community Events
    case ANNOUNCEMENT_MANAGE = 'announcement.manage';
    case EVENT_MANAGE = 'event.manage';
    case ROUND_SCHEDULE_MANAGE = 'round_schedule.manage';
    case EMERGENCY_MANAGE = 'emergency.manage';

    public function label(): string
    {
        return match ($this) {
            self::FINANCE_TRANSACTION_READ => 'Lihat Transaksi Kas RT',
            self::FINANCE_TRANSACTION_CREATE => 'Buat Draft Transaksi Kas',
            self::FINANCE_TRANSACTION_PUBLISH => 'Publikasikan Transaksi Kas',
            self::FINANCE_TRANSACTION_REVERSE => 'Batalkan Transaksi Kas (Reversal)',
            self::FINANCE_DUES_READ => 'Lihat Data Iuran Warga',
            self::FINANCE_DUES_MANAGE => 'Kelola Tagihan Iuran Warga',
            self::FINANCE_PAYMENT_READ => 'Lihat Pembayaran Iuran',
            self::FINANCE_PAYMENT_MANAGE => 'Verifikasi Pembayaran Iuran',
            self::FINANCE_PURCHASE_READ => 'Lihat Data Pembelian RT',
            self::FINANCE_PURCHASE_MANAGE => 'Kelola Pembelian & Nota Barang RT',
            self::FINANCE_REPORT_READ => 'Lihat Laporan Keuangan Triwulan',
            self::FINANCE_REPORT_GENERATE => 'Kalkulasi Laporan Keuangan Triwulan',
            self::FINANCE_REPORT_PUBLISH => 'Sahkan Laporan Keuangan Triwulan',
            self::FINANCE_AUDIT_READ => 'Lihat Audit Trail Keuangan',
            self::DOWNLOAD_AUDIT_READ => 'Lihat Log Audit Pengunduhan Berkas',
            self::CITIZEN_READ => 'Lihat Data Kependudukan Warga',
            self::CITIZEN_MANAGE => 'Kelola Data Kependudukan Warga',
            self::LETTER_READ => 'Lihat Permohonan Surat Pengantar',
            self::LETTER_CREATE => 'Buat Permohonan Surat',
            self::LETTER_VERIFY => 'Verifikasi Berkas Surat (Sekretaris)',
            self::LETTER_APPROVE => 'Tandatangani / Setujui Surat (Ketua RT)',
            self::LETTER_REJECT => 'Tolak Permohonan Surat',
            self::LETTER_COMPLETE => 'Selesaikan / Serahkan Surat',
            self::LETTER_TEMPLATE_MANAGE => 'Kelola Template & Syarat Surat',
            self::COMPLAINT_READ => 'Lihat Laporan Pengaduan Warga',
            self::COMPLAINT_MANAGE => 'Tindak Lanjuti Pengaduan Warga',
            self::SECURITY_READ => 'Lihat Laporan Insiden Keamanan',
            self::SECURITY_MANAGE => 'Tindak Lanjuti Insiden Keamanan',
            self::ANNOUNCEMENT_MANAGE => 'Kelola Pengumuman Lingkungan',
            self::EVENT_MANAGE => 'Kelola Agenda Kegiatan Warga',
            self::ROUND_SCHEDULE_MANAGE => 'Kelola Jadwal Ronda Kamling',
            self::EMERGENCY_MANAGE => 'Kelola Kontak Darurat Lingkungan',
        };
    }
}
