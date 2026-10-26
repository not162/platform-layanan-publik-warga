<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_webmanifest_is_valid_and_installable(): void
    {
        $json = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertIsArray($json);
        $this->assertSame('PRISMA RT 04 Kemayoran', $json['name']);
        $this->assertSame('standalone', $json['display']);
        $this->assertSame('/dashboard', $json['start_url']);

        $sizes = collect($json['icons'])->pluck('sizes')->all();
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertContains('maskable', collect($json['icons'])->pluck('purpose')->all());

        foreach ($json['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_service_worker_never_caches_api_and_has_offline_fallback(): void
    {
        $content = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('/offline', $content);
        $this->assertStringContainsString('api|sanctum|login|logout', $content);
        $this->assertStringContainsString("request.method !== 'GET'", $content);
    }

    public function test_offline_fallback_page_renders(): void
    {
        $this->get('/offline')->assertOk()->assertSee('Anda sedang offline');
    }

    public function test_public_pages_expose_pwa_meta_and_mobile_navigation(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('name="theme-color"', false)
            ->assertSee('viewport-fit=cover', false)
            ->assertSee('class="bottom-nav"', false)
            ->assertSee('class="topnav"', false);
    }
}
