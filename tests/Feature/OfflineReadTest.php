<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\FincasTestCase;

class OfflineReadTest extends FincasTestCase
{
    use RefreshDatabase;

    public function test_cached_shell_is_generic_even_when_requested_by_an_authenticated_user(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['name' => 'Persona privada de prueba']);
        $this->actingAs($user)->get('/offline-shell')->assertOk()
            ->assertSee('Estás usando BoviSoft sin conexión')
            ->assertDontSee('workspace-controls')
            ->assertDontSee('Persona privada de prueba')
            ->assertDontSee($user->email)
            ->assertDontSee('name="_token"', false)
            ->assertDontSee('name="csrf-token"', false)
            ->assertDontSee('https://fonts.googleapis.com', false)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_session_catalog_is_account_scoped_without_an_offline_deadline(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $own = Finca::create(['codigo' => 'READ-A', 'nombre' => 'Propia', 'activa' => true, 'user_id' => $a->id]);
        Finca::create(['codigo' => 'READ-B', 'nombre' => 'Ajena', 'activa' => true, 'user_id' => $b->id]);
        $this->travelTo(now()->startOfSecond());
        $response = $this->actingAs($a)->getJson('/offline/session')->assertOk()
            ->assertJsonPath('account_id', $a->id)
            ->assertJsonCount(1, 'fincas')
            ->assertJsonPath('fincas.0.id', $own->id)
            ->assertJsonMissingPath('lease_expires_at')
            ->assertHeader('X-Bovisoft-Account', (string) $a->id)
            ->assertHeader('Cache-Control', 'no-store, private');
        $binding = $response->headers->get('X-Bovisoft-Binding');
        $this->assertNotEmpty($binding);
        $this->getJson('/offline/session')->assertHeader('X-Bovisoft-Binding', $binding);
        $other = $this->actingAs($b)->getJson('/offline/session')->assertOk()
            ->assertJsonCount(1, 'fincas')->assertJsonPath('fincas.0.nombre', 'Ajena');
        $this->assertNotSame($binding, $other->headers->get('X-Bovisoft-Binding'));
    }

    public function test_guest_can_load_the_shell_but_receives_no_private_catalog(): void
    {
        $this->withoutVite();
        $this->get('/offline-shell')->assertOk();
        $this->get('/offline/workspace')->assertRedirect('/dashboard');
        $this->get('/offline/produccion')->assertRedirect('/dashboard');
        $this->getJson('/offline/session')->assertOk()->assertJsonPath('account_id', null)
            ->assertJsonCount(0, 'fincas')->assertHeader('X-Bovisoft-Account', 'guest');
    }

    public function test_asset_manifest_contains_only_existing_local_resources_and_no_private_endpoints(): void
    {
        // The real browser tests also verify every resource can actually be cached.
        $response = $this->getJson('/offline/asset-manifest')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $response->json('version'));
        $urls = $response->json('urls');
        $this->assertContains('/offline-shell', $urls);
        $this->assertNotContains('/offline/produccion', $urls);
        $this->assertContains('/offline-store.js', $urls);
        foreach ($urls as $url) {
            $this->assertStringStartsWith('/', $url);
            $this->assertStringNotContainsString('://', $url);
            $this->assertStringNotContainsString('/api/', $url);
            if ($url !== '/offline-shell') {
                $this->assertFileExists(public_path(ltrim($url, '/')));
            }
        }
    }
}
