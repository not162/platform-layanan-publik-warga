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
        $this->assertSame('PRISMA RT 04 — Layanan Warga', $json['name']);
        $this->assertSame('standalone', $json['display']);
        $this->assertSame('/dashboard?source=pwa', $json['start_url']);

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

    public function test_brand_logo_splash_and_pwa_icons_are_present_and_demo_shortcuts_are_gone(): void
    {
        foreach (['images/prisma-mark.svg', 'images/prisma-icon.svg', 'icons/apple-touch-icon.png', 'icons/icon-512.png', 'icons/icon-maskable-512.png'] as $file) {
            $this->assertFileExists(public_path($file));
        }

        $home = $this->get('/')->assertOk()->assertSee('id="splash"', false)->assertSee('Urus surat, cek kas, lapor masalah', false)->assertSee('prisma-mark.svg', false);
        $this->assertStringContainsString('apple-touch-icon.png', $home->getContent());

        $this->get('/login')->assertOk()->assertDontSee('Pintasan akun demo')->assertDontSee('data-demo', false)->assertDontSee('Superadmin');
        $this->assertSame('standalone', json_decode(file_get_contents(public_path('manifest.webmanifest')), true)['display']);
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
