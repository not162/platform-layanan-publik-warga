<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_webmanifest_is_accessible_and_valid_json(): void
    {
        $path = public_path('manifest.webmanifest');
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $json = json_decode($content, true);

        $this->assertIsArray($json);
        $this->assertEquals('Layanan Publik Warga', $json['name']);
        $this->assertEquals('Portal Warga', $json['short_name']);
        $this->assertEquals('standalone', $json['display']);
        $this->assertEquals('#F8FAFC', $json['background_color']);
        $this->assertEquals('#1B365D', $json['theme_color']);
        $this->assertNotEmpty($json['icons']);
    }

    public function test_service_worker_file_is_accessible(): void
    {
        $path = public_path('sw.js');
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('lpw-static-v1', $content);
        $this->assertStringContainsString('/offline', $content);
        // Ensure private APIs are excluded from caching
        $this->assertStringContainsString("url.pathname.startsWith('/api/')", $content);
    }

    public function test_offline_fallback_page_returns_successful_response(): void
    {
        $response = $this->get('/offline');

        $response->assertStatus(200)
            ->assertSee('Anda Sedang Luring (Offline)', false)
            ->assertSee('color-scheme', false);
    }

    public function test_home_page_includes_manifest_and_pwa_meta_tags(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('name="theme-color" content="#1B365D"', false)
            ->assertSee('name="color-scheme" content="light"', false);
    }
}
