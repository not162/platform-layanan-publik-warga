<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\EmergencyContact;
use App\Models\FinanceTransaction;
use App\Models\RoundSchedule;
use App\Models\RtOfficer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_overview_returns_aggregated_published_community_data(): void
    {
        Announcement::factory()->published()->create(['title' => 'Pengumuman Kerja Bakti']);
        CommunityEvent::factory()->create([
            'title' => 'Kerja Bakti Bersama',
            'event_date' => now()->addDays(2)->toDateString(),
        ]);
        RtOfficer::factory()->create(['name' => 'Bambang Sudirman', 'position' => 'Ketua RT 01']);
        EmergencyContact::factory()->create(['name' => 'Polsek Terdekat', 'phone_number' => '110']);
        FinanceTransaction::factory()->published()->income(2500000)->create();

        $response = $this->getJson('/api/v1/public/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.finance.total_income', 2500000)
            ->assertJsonCount(1, 'data.announcements')
            ->assertJsonCount(1, 'data.upcoming_events')
            ->assertJsonCount(1, 'data.officers')
            ->assertJsonCount(1, 'data.emergency_contacts');
    }

    public function test_public_announcements_returns_only_published_and_pinned_first(): void
    {
        Announcement::factory()->create([
            'title' => 'Draft Internal Pengurus',
            'is_published' => false,
        ]);

        $normalAnnouncement = Announcement::factory()->published()->create([
            'title' => 'Pengumuman Biasa',
            'is_pinned' => false,
            'published_at' => now()->subDay(),
        ]);

        $pinnedAnnouncement = Announcement::factory()->published()->pinned()->create([
            'title' => 'Pengumuman Penting Dipin',
            'is_pinned' => true,
            'published_at' => now()->subDays(3),
        ]);

        $response = $this->getJson('/api/v1/public/announcements');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Pengumuman Penting Dipin')
            ->assertJsonPath('data.1.title', 'Pengumuman Biasa');
    }

    public function test_public_announcements_can_be_filtered_by_category(): void
    {
        Announcement::factory()->published()->create([
            'title' => 'Iuran Sampah Naik',
            'category' => 'iuran',
        ]);
        Announcement::factory()->published()->create([
            'title' => 'Ronda Malam Wajib',
            'category' => 'keamanan',
        ]);

        $response = $this->getJson('/api/v1/public/announcements?category=iuran');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Iuran Sampah Naik');
    }

    public function test_public_events_returns_only_future_events(): void
    {
        // Past event
        CommunityEvent::factory()->create([
            'title' => 'Acara Masa Lalu',
            'event_date' => now()->subDays(5)->toDateString(),
            'is_published' => true,
        ]);

        // Future event
        CommunityEvent::factory()->create([
            'title' => 'Rapat Warga Bulan Depan',
            'event_date' => now()->addDays(5)->toDateString(),
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/v1/public/events');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Rapat Warga Bulan Depan');
    }

    public function test_public_officers_returns_active_officers_ordered(): void
    {
        RtOfficer::factory()->create([
            'name' => 'Sekretaris Pak Joko',
            'order_index' => 2,
            'is_active' => true,
        ]);
        RtOfficer::factory()->create([
            'name' => 'Ketua RT Pak Budi',
            'order_index' => 1,
            'is_active' => true,
        ]);
        RtOfficer::factory()->create([
            'name' => 'Mantan Pengurus',
            'order_index' => 3,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/public/officers');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Ketua RT Pak Budi')
            ->assertJsonPath('data.1.name', 'Sekretaris Pak Joko');
    }

    public function test_public_emergency_contacts_and_round_schedules(): void
    {
        EmergencyContact::factory()->create([
            'name' => 'Ambulans Gawat Darurat',
            'phone_number' => '119',
            'is_active' => true,
        ]);

        RoundSchedule::factory()->create([
            'day_of_week' => 'sabtu',
            'shift_name' => 'Malam',
            'officer_names' => ['Agus', 'Bambang', 'Catur'],
            'is_active' => true,
        ]);

        $responseContacts = $this->getJson('/api/v1/public/emergency-contacts');
        $responseContacts->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.phone_number', '119');

        $responseSchedules = $this->getJson('/api/v1/public/schedules');
        $responseSchedules->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.day_of_week', 'sabtu');
    }
}
