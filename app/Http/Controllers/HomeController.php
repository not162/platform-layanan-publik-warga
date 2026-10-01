<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\EmergencyContact;
use App\Models\RoundSchedule;
use App\Models\RtOfficer;
use App\Services\FinanceService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(protected FinanceService $financeService) {}

    public function __invoke(): View
    {
        $financeSummary = $this->financeService->getPublicSummary();

        $announcements = Announcement::query()
            ->where('is_published', true)
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->limit(4)
            ->get();

        $upcomingEvents = CommunityEvent::query()
            ->where('is_published', true)
            ->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->limit(3)
            ->get();

        $officers = RtOfficer::query()
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        $emergencyContacts = EmergencyContact::query()
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        $roundSchedules = RoundSchedule::query()
            ->where('is_active', true)
            ->get();

        $carouselItems = collect();

        foreach ($announcements as $announcement) {
            $carouselItems->push([
                'type' => 'announcement',
                'badge' => $announcement->is_pinned ? 'PENGUMUMAN PENTING' : 'WARTA WARGA',
                'badge_class' => $announcement->is_pinned ? 'badge-pinned' : 'badge-primary',
                'title' => $announcement->title,
                'subtitle' => $announcement->published_at ? $announcement->published_at->translatedFormat('d F Y') : 'Terbaru',
                'description' => $announcement->content,
                'location' => null,
                'category' => $announcement->category ?? 'Umum',
                'action_label' => 'Baca Pengumuman',
                'action_url' => '#pengumuman',
            ]);
        }

        foreach ($upcomingEvents as $event) {
            $timeText = $event->start_time ? ' • '.substr($event->start_time, 0, 5).' WIB' : '';
            $carouselItems->push([
                'type' => 'event',
                'badge' => 'AGENDA WARGA',
                'badge_class' => 'badge-teal',
                'title' => $event->title,
                'subtitle' => ($event->event_date ? $event->event_date->translatedFormat('d F Y') : '').$timeText,
                'description' => $event->description,
                'location' => $event->location,
                'category' => 'Kegiatan RT',
                'action_label' => 'Lihat Lokasi & Agenda',
                'action_url' => '#agenda',
            ]);
        }

        if ($carouselItems->isEmpty()) {
            $carouselItems->push([
                'type' => 'feature',
                'badge' => 'LAYANAN DIGITAL',
                'badge_class' => 'badge-primary',
                'title' => 'Portal Layanan Publik & Administrasi RT Modern',
                'subtitle' => 'Terbuka & Akuntabel',
                'description' => 'Akses mandiri permohonan surat pengantar resmi, pemantauan status tiket berkas secara real-time, dan pelaporan keluhan fasilitas lingkungan tanpa hambatan birokrasi konvensional.',
                'location' => 'Kantor Sekretariat RT 01 / RW 05',
                'category' => 'Layanan Publik',
                'action_label' => 'Mulai Pengajuan Surat',
                'action_url' => route('login'),
            ]);
            $carouselItems->push([
                'type' => 'feature',
                'badge' => 'TRANSPARANSI KAS',
                'badge_class' => 'badge-teal',
                'title' => 'Akuntabilitas Buku Kas & Rekapitulasi Iuran Warga',
                'subtitle' => 'Laporan Terverifikasi',
                'description' => 'Setiap rupiah iuran warga dicatat secara terbuka menggunakan buku kas anti-fraud (immutable ledger). Seluruh mutasi penerimaan dan pengeluaran lingkungan dapat ditinjau oleh seluruh warga.',
                'location' => 'Sistem Buku Kas RT',
                'category' => 'Keuangan',
                'action_label' => 'Tinjau Transparansi Kas',
                'action_url' => '#transparansi-kas',
            ]);
        }

        return view('welcome', compact(
            'financeSummary',
            'announcements',
            'upcomingEvents',
            'officers',
            'emergencyContacts',
            'roundSchedules',
            'carouselItems'
        ));
    }
}
