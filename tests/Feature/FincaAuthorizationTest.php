<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Potrero;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FincasTestCase;

class FincaAuthorizationTest extends FincasTestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private Finca $finca;

    private Finca $otherFinca;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $engine = getenv('BOVISOFT_TEST_ENGINE') ?: 'sqlite';
        $this->assertSame($engine, DB::connection()->getDriverName());
        $this->assertSame($engine === 'sqlite' ? ':memory:' : getenv('BOVISOFT_TEST_DATABASE'), DB::connection()->getDatabaseName());

        $this->owner = User::factory()->create(['name' => 'Cuenta ficticia A', 'email' => 'fincas-a@example.test']);
        $this->other = User::factory()->create(['name' => 'Cuenta ficticia B', 'email' => 'fincas-b@example.test']);
        $this->finca = $this->createFinca($this->owner, 'A');
        $this->otherFinca = $this->createFinca($this->other, 'B');
    }

    public static function directActions(): array
    {
        return [
            'detalle' => ['GET', ''],
            'formulario' => ['GET', '/edit'],
            'actualizar' => ['PUT', ''],
            'eliminar' => ['DELETE', ''],
            'estado' => ['POST', '/toggle-estado'],
        ];
    }

    #[DataProvider('directActions')]
    public function test_other_account_cannot_access_or_modify_a_finca(string $method, string $suffix): void
    {
        foreach ([[$this->other, $this->finca], [$this->owner, $this->otherFinca]] as [$user, $foreignFinca]) {
            $before = $foreignFinca->fresh()->getRawOriginal();

            $this->actingAs($user)
                ->json($method, '/fincas/'.$foreignFinca->id.$suffix, $this->updatePayload())
                ->assertNotFound();

            $this->assertSame($before, $foreignFinca->fresh()->getRawOriginal());
        }
    }

    public function test_owner_can_view_create_edit_update_toggle_and_delete(): void
    {
        $this->actingAs($this->owner);
        $this->get('/fincas/create')->assertOk();
        $this->get('/fincas/'.$this->finca->id)->assertOk()->assertSee($this->finca->nombre);
        $this->get('/fincas/'.$this->finca->id.'/edit')->assertOk();
        $this->put('/fincas/'.$this->finca->id, $this->updatePayload() + ['user_id' => $this->other->id])
            ->assertRedirect(route('fincas.show', $this->finca));
        $this->assertSame('Nombre actualizado', $this->finca->fresh()->nombre);
        $this->assertSame($this->owner->id, (int) $this->finca->fresh()->user_id);

        $this->postJson('/fincas/'.$this->finca->id.'/toggle-estado')
            ->assertOk()->assertJsonPath('activa', false);
        $this->delete('/fincas/'.$this->finca->id)->assertRedirect(route('fincas.index'));
        $this->assertSoftDeleted('fincas', ['id' => $this->finca->id]);
    }

    public function test_creation_always_uses_the_authenticated_owner(): void
    {
        $this->actingAs($this->owner)->post('/fincas', [
            'nombre' => 'Finca ficticia nueva', 'codigo' => 'TEST-NUEVA',
            'latitud' => 4.5, 'longitud' => -74.1, 'user_id' => $this->other->id,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('fincas', ['codigo' => 'TEST-NUEVA', 'user_id' => $this->owner->id]);
    }

    public function test_unauthorized_update_is_rejected_before_validation(): void
    {
        $before = $this->finca->fresh()->getRawOriginal();
        $this->actingAs($this->other)->putJson('/fincas/'.$this->finca->id, [])->assertNotFound();
        $this->assertSame($before, $this->finca->fresh()->getRawOriginal());
    }

    #[DataProvider('directActions')]
    public function test_direct_actions_reject_missing_or_deleted_fincas(string $method, string $suffix): void
    {
        $this->finca->delete();
        $this->actingAs($this->owner)->json($method, '/fincas/'.$this->finca->id.$suffix, $this->updatePayload())->assertNotFound();
        $this->json($method, '/fincas/999999'.$suffix, $this->updatePayload())->assertNotFound();
        $this->assertSoftDeleted('fincas', ['id' => $this->finca->id]);
    }

    public function test_lists_and_map_only_include_each_accounts_fincas(): void
    {
        $inactive = $this->createFinca($this->owner, 'INACTIVA');
        $inactive->update(['activa' => false]);
        $withoutLocation = $this->createFinca($this->owner, 'SIN-MAPA');
        $withoutLocation->update(['latitud' => null]);

        foreach ([[$this->owner, $this->finca, $this->otherFinca], [$this->other, $this->otherFinca, $this->finca]] as [$user, $own, $foreign]) {
            $this->actingAs($user)->get('/fincas')->assertOk()
                ->assertSee($own->nombre)->assertDontSee($foreign->nombre);
            $this->getJson('/api/fincas/mapa')->assertOk()->assertJsonCount(1)
                ->assertJsonPath('0.id', $own->id)->assertDontSee($foreign->nombre);
        }
    }

    public static function relatedRoutes(): array
    {
        return [
            'animales' => ['/fincas/%s/animales'],
            'potreros JSON' => ['/api/fincas/%s/potreros'],
            'potreros HTML' => ['/fincas/%s/potreros'],
        ];
    }

    #[DataProvider('relatedRoutes')]
    public function test_related_queries_reject_foreign_missing_and_deleted_fincas(string $pattern): void
    {
        $this->actingAs($this->other)->getJson(sprintf($pattern, $this->finca->id))->assertNotFound();
        $this->getJson(sprintf($pattern, 999999))->assertNotFound();
        $this->otherFinca->delete();
        $this->getJson(sprintf($pattern, $this->otherFinca->id))->assertNotFound();
    }

    public function test_related_queries_keep_their_filters_and_never_expose_foreign_children(): void
    {
        foreach ([[$this->owner, $this->finca, 'A'], [$this->other, $this->otherFinca, 'B']] as [$user, $finca, $tag]) {
            $potrero = $this->createPotrero($user, $finca, $tag);
            $animal = $this->createAnimal($user, $finca, $tag);
            $this->createPotrero($user, $finca, $tag.'-DESCANSO')->update(['estado' => 'en_descanso']);
            $this->createAnimal($user, $finca, $tag.'-INACTIVO')->update(['estado' => 'vendido']);
            // Defensive checks for inconsistent legacy ownership, allowed by the current schema.
            $foreignUser = $user->is($this->owner) ? $this->other : $this->owner;
            $foreignPotrero = $this->createPotrero($foreignUser, $finca, $tag.'-AJENO');
            $foreignAnimal = $this->createAnimal($foreignUser, $finca, $tag.'-AJENO');

            $this->actingAs($user)->getJson('/fincas/'.$finca->id.'/animales')->assertOk()
                ->assertJsonCount(1)->assertJsonPath('0.id', $animal->id);
            $this->getJson('/api/fincas/'.$finca->id.'/potreros')->assertOk()
                ->assertJsonCount(1)->assertJsonPath('0.id', $potrero->id)
                ->assertJsonPath('0.disponibilidad', 7);
            $this->get('/fincas/'.$finca->id.'/potreros')->assertOk()
                ->assertSee($potrero->nombre)->assertDontSee($foreignPotrero->nombre);
            $this->get('/potreros')->assertOk()->assertSee($potrero->nombre)
                ->assertDontSee($foreignPotrero->nombre);
            $this->get('/animales')->assertOk()->assertDontSee($foreignAnimal->nombre);
        }
    }

    public function test_lists_hide_children_attached_to_another_accounts_finca(): void
    {
        $animal = $this->createAnimal($this->owner, $this->otherFinca, 'RELACION-AJENA');
        $potrero = $this->createPotrero($this->owner, $this->otherFinca, 'RELACION-AJENA');

        $this->actingAs($this->owner)->get('/animales')->assertOk()->assertDontSee($animal->nombre);
        $this->get('/potreros')->assertOk()->assertDontSee($potrero->nombre)
            ->assertViewHas('total', 0)->assertViewHas('activos', 0);
        $this->get('/potreros?finca_id='.$this->otherFinca->id)->assertNotFound();
    }

    public function test_finca_counts_exclude_children_owned_by_another_account(): void
    {
        $this->createAnimal($this->other, $this->finca, 'CONTADOR-AJENO');
        $this->createPotrero($this->other, $this->finca, 'CONTADOR-AJENO');

        $this->actingAs($this->owner)->get('/fincas/'.$this->finca->id)->assertOk()
            ->assertViewHas('finca', fn ($finca) => $finca->total_animales === 0 && $finca->total_potreros === 0);
        $this->get('/fincas')->assertOk()->assertViewHas('fincas', fn ($fincas) => $fincas->every(
            fn ($finca) => $finca->total_animales === 0 && $finca->total_potreros === 0
        ));
    }

    public function test_finca_route_cannot_be_overridden_with_a_query_filter(): void
    {
        $potrero = $this->createPotrero($this->owner, $this->finca, 'FILTRO');
        $this->actingAs($this->owner)
            ->get('/fincas/'.$this->finca->id.'/potreros?finca_id='.$this->otherFinca->id)
            ->assertOk()->assertViewHas('potreros', fn ($potreros) => $potreros->pluck('id')->all() === [$potrero->id]);
        $this->getJson('/potreros?finca_id=no-es-id')->assertUnprocessable();
    }

    public function test_guests_cannot_access_finca_endpoints(): void
    {
        foreach (['/fincas', '/api/fincas/mapa', '/fincas/'.$this->finca->id, '/fincas/'.$this->finca->id.'/animales', '/api/fincas/'.$this->finca->id.'/potreros', '/fincas/'.$this->finca->id.'/potreros'] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
    }

    private function createFinca(User $user, string $tag): Finca
    {
        return Finca::create(['user_id' => $user->id, 'codigo' => 'TEST-FIN-'.$tag,
            'nombre' => 'Finca ficticia '.$tag, 'activa' => true, 'latitud' => 4.5, 'longitud' => -74.1]);
    }

    private function createAnimal(User $user, Finca $finca, string $tag): Animal
    {
        return Animal::create(['user_id' => $user->id, 'finca_id' => $finca->id,
            'codigo' => 'TEST-ANI-'.$tag, 'nombre' => 'Animal ficticio '.$tag,
            'tipo' => 'vaca', 'sexo' => 'hembra', 'estado' => 'activo']);
    }

    private function createPotrero(User $user, Finca $finca, string $tag): Potrero
    {
        return Potrero::create(['user_id' => $user->id, 'finca_id' => $finca->id,
            'codigo' => 'TEST-POT-'.$tag, 'nombre' => 'Potrero ficticio '.$tag,
            'estado' => 'activo', 'capacidad_animales' => 10, 'animales_actuales' => 3]);
    }

    private function updatePayload(): array
    {
        return ['nombre' => 'Nombre actualizado', 'area' => 12.5,
            'latitud' => 4.6, 'longitud' => -74.2, 'activa' => true];
    }
}
