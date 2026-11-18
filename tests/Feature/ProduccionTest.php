<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Produccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FincasTestCase;

class ProduccionTest extends FincasTestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private Finca $finca;

    private Finca $destination;

    private Finca $foreignFinca;

    private Animal $animal;

    private Animal $foreignAnimal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(10, 0));
        $this->owner = User::factory()->create(['email' => 'produccion-a@example.test']);
        $this->other = User::factory()->create(['email' => 'produccion-b@example.test']);
        $this->finca = $this->finca($this->owner, 'ORIGINAL');
        $this->destination = $this->finca($this->owner, 'DESTINO');
        $this->foreignFinca = $this->finca($this->other, 'AJENA');
        $this->animal = $this->animal($this->owner, $this->finca, 'ORIGINAL');
        $this->foreignAnimal = $this->animal($this->other, $this->foreignFinca, 'AJENO');
    }

    public function test_owner_can_create_a_valid_production_and_cannot_assign_its_owner_or_code(): void
    {
        $this->actingAs($this->owner)->post('/produccion', $this->payload() + [
            'user_id' => $this->other->id, 'codigo' => 'CODIGO-INYECTADO',
        ])->assertRedirect(route('produccion.index'));

        $registro = Produccion::sole();
        $this->assertSame($this->owner->id, (int) $registro->user_id);
        $this->assertSame($this->animal->id, (int) $registro->animal_id);
        $this->assertSame($this->finca->id, (int) $registro->finca_id);
        $this->assertSame('12.35', $registro->litros);
        $this->assertNotSame('CODIGO-INYECTADO', $registro->codigo);
    }

    public static function unavailableReferences(): array
    {
        return [
            'animal ajeno' => ['animal', 'foreign', 'animal_id'],
            'finca ajena' => ['finca', 'foreign', 'finca_id'],
            'otra finca propia' => ['finca', 'different', 'finca_id'],
            'animal vendido' => ['animal', 'vendido', 'animal_id'],
            'animal muerto' => ['animal', 'muerto', 'animal_id'],
            'tipo no productivo' => ['animal', 'toro', 'animal_id'],
            'finca inactiva' => ['finca', 'inactive', 'finca_id'],
            'animal eliminado' => ['animal', 'deleted', 'animal_id'],
            'finca eliminada' => ['finca', 'deleted', 'finca_id'],
            'animal inexistente' => ['animal', 'missing', 'animal_id'],
            'finca inexistente' => ['finca', 'missing', 'finca_id'],
        ];
    }

    #[DataProvider('unavailableReferences')]
    public function test_creation_rejects_unavailable_or_inconsistent_references(string $entity, string $state, string $field): void
    {
        $payload = $this->payload();
        if ($state === 'foreign') {
            $payload[$field] = $entity === 'animal' ? $this->foreignAnimal->id : $this->foreignFinca->id;
        } elseif ($state === 'different') {
            $payload[$field] = $this->destination->id;
        } elseif ($state === 'missing') {
            $payload[$field] = 999999;
        } elseif ($state === 'deleted') {
            ($entity === 'animal' ? $this->animal : $this->finca)->delete();
        } elseif ($state === 'inactive') {
            $this->finca->update(['activa' => false]);
        } elseif ($state === 'toro') {
            $this->animal->update(['tipo' => 'toro']);
        } else {
            $this->animal->update(['estado' => $state]);
        }

        $this->actingAs($this->owner)->postJson('/produccion', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('producciones', 0);
    }

    public function test_novillas_are_also_eligible(): void
    {
        $this->animal->update(['tipo' => 'novilla']);
        $this->actingAs($this->owner)->post('/produccion', $this->payload())->assertRedirect();
        $this->assertDatabaseCount('producciones', 1);
    }

    public static function sessions(): array
    {
        return ['turno especificado' => ['manana'], 'turno sin especificar' => [null]];
    }

    #[DataProvider('sessions')]
    public function test_separate_measurements_with_identical_animal_date_and_session_remain_separate(?string $session): void
    {
        $payload = array_replace($this->payload(), ['sesion' => $session]);
        $this->actingAs($this->owner)->post('/produccion', $payload)->assertRedirect();
        $this->post('/produccion', $payload)->assertRedirect();
        $this->assertDatabaseCount('producciones', 2);
        $this->assertSame(2, Produccion::distinct()->count('codigo'));
        $this->assertEquals(24.70, Produccion::sum('litros'));
    }

    public static function historicalStates(): array
    {
        return ['trasladado' => [true, 'activo'], 'inactivo' => [false, 'vendido'],
            'trasladado e inactivo' => [true, 'muerto']];
    }

    #[DataProvider('historicalStates')]
    public function test_historical_corrections_preserve_original_references(bool $moved, string $state): void
    {
        $registro = $this->registro();
        $this->animal->update(['finca_id' => $moved ? $this->destination->id : $this->finca->id, 'estado' => $state]);
        $this->finca->update(['activa' => false]);

        $this->actingAs($this->owner)->get('/produccion/'.$registro->id.'/edit')->assertOk()
            ->assertSee('Animal ORIGINAL')->assertSee('Finca ORIGINAL')
            ->assertSee('requiere revisión online');
        $payload = array_replace($this->payload(), ['litros' => 18.75, 'sesion' => 'tarde',
            'calidad' => 'excelente', 'observaciones' => 'Corrección histórica']);
        $this->put('/produccion/'.$registro->id, $payload)->assertRedirect(route('produccion.show', $registro));

        $registro->refresh();
        $this->assertSame($this->animal->id, (int) $registro->animal_id);
        $this->assertSame($this->finca->id, (int) $registro->finca_id);
        $this->assertSame('2026-11-16', $registro->fecha->format('Y-m-d'));
        $this->assertSame('18.75', $registro->litros);
        $this->assertSame('tarde', $registro->sesion);
        $this->assertSame('excelente', $registro->calidad);
        $this->assertSame('Corrección histórica', $registro->observaciones);
    }

    #[DataProvider('historicalStates')]
    public function test_date_changes_require_review_and_leave_the_entire_record_untouched(bool $moved, string $state): void
    {
        $registro = $this->registro();
        $this->animal->update(['finca_id' => $moved ? $this->destination->id : $this->finca->id, 'estado' => $state]);
        $before = $registro->fresh()->getRawOriginal();
        $this->actingAs($this->owner)->putJson('/produccion/'.$registro->id,
            array_replace($this->payload(), ['fecha' => '2026-11-17', 'litros' => 50]))
            ->assertUnprocessable()->assertJsonValidationErrors('fecha')
            ->assertJsonPath('errors.fecha.0', fn ($message) => str_contains($message, 'requiere revisión online'));
        $this->assertSame($before, $registro->fresh()->getRawOriginal());
    }

    public function test_date_can_still_be_corrected_for_an_active_animal_in_its_original_finca(): void
    {
        $registro = $this->registro();
        $this->actingAs($this->owner)->put('/produccion/'.$registro->id,
            array_replace($this->payload(), ['fecha' => '2026-11-17']))->assertRedirect();
        $this->assertSame('2026-11-17', $registro->fresh()->fecha->format('Y-m-d'));
    }

    public static function immutableFields(): array
    {
        return ['animal propio diferente' => ['animal_id', false], 'animal ajeno' => ['animal_id', true],
            'finca propia diferente' => ['finca_id', false], 'finca ajena' => ['finca_id', true]];
    }

    #[DataProvider('immutableFields')]
    public function test_edit_cannot_reassign_original_references(string $field, bool $foreign): void
    {
        $registro = $this->registro();
        $before = $registro->fresh()->getRawOriginal();
        $replacement = $field === 'animal_id'
            ? ($foreign ? $this->foreignAnimal->id : $this->animal($this->owner, $this->finca, 'OTRO')->id)
            : ($foreign ? $this->foreignFinca->id : $this->destination->id);
        $this->actingAs($this->owner)->putJson('/produccion/'.$registro->id,
            array_replace($this->payload(), [$field => $replacement]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame($before, $registro->fresh()->getRawOriginal());
    }

    public function test_edit_can_omit_immutable_ids_and_cannot_assign_owner_or_code(): void
    {
        $registro = $this->registro();
        $payload = $this->payload();
        unset($payload['animal_id'], $payload['finca_id']);
        $this->actingAs($this->owner)->put('/produccion/'.$registro->id,
            $payload + ['user_id' => $this->other->id, 'codigo' => 'INTRUSO'])->assertRedirect();
        $this->assertSame($this->owner->id, (int) $registro->fresh()->user_id);
        $this->assertSame('TEST-PROD', $registro->fresh()->codigo);
    }

    public function test_other_account_cannot_read_update_or_delete_a_production(): void
    {
        $registro = $this->registro();
        $before = $registro->fresh()->getRawOriginal();
        $this->actingAs($this->other);
        foreach ([['GET', ''], ['GET', '/edit'], ['PUT', ''], ['DELETE', '']] as [$method, $suffix]) {
            $this->json($method, '/produccion/'.$registro->id.$suffix, $this->payload())->assertNotFound();
            $this->assertSame($before, $registro->fresh()->getRawOriginal());
        }
        $this->get('/produccion')->assertOk()->assertDontSee('TEST-PROD');
    }

    public function test_legacy_record_with_foreign_references_is_not_exposed_or_editable(): void
    {
        $registro = $this->registro();
        $registro->update(['finca_id' => $this->foreignFinca->id]);
        $before = $registro->fresh()->getRawOriginal();
        $this->actingAs($this->owner)->get('/produccion/'.$registro->id)->assertNotFound();
        $this->get('/produccion')->assertOk()->assertDontSee('TEST-PROD');
        $this->putJson('/produccion/'.$registro->id, $this->payload())->assertNotFound();
        $this->assertSame($before, $registro->fresh()->getRawOriginal());
    }

    public static function invalidFields(): array
    {
        return ['litros cero' => ['litros', 0], 'litros excesivos' => ['litros', 1000],
            'precision' => ['litros', '12.345'], 'fecha futura' => ['fecha', '2026-11-19'],
            'fecha invalida' => ['fecha', '2026-02-30'], 'turno' => ['sesion', 'madrugada'],
            'calidad' => ['calidad', 'desconocida'], 'observaciones' => ['observaciones', str_repeat('x', 1001)]];
    }

    #[DataProvider('invalidFields')]
    public function test_validation_is_applied_to_both_creation_and_correction(string $field, mixed $value): void
    {
        $registro = $this->registro();
        $before = $registro->fresh()->getRawOriginal();
        $payload = array_replace($this->payload(), [$field => $value]);
        $this->actingAs($this->owner)->postJson('/produccion', $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->putJson('/produccion/'.$registro->id, $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('producciones', 1);
        $this->assertSame($before, $registro->fresh()->getRawOriginal());
    }

    public function test_create_form_only_offers_eligible_authorized_references(): void
    {
        $this->animal($this->owner, $this->finca, 'VENDIDO')->update(['estado' => 'vendido']);
        $this->animal($this->owner, $this->foreignFinca, 'RELACION-AJENA');
        $this->actingAs($this->owner)->get('/produccion/create?animal_id='.$this->foreignAnimal->id)
            ->assertOk()->assertViewHas('animales', fn ($animales) => $animales->pluck('id')->all() === [$this->animal->id])
            ->assertViewHas('animalPresel', null)->assertDontSee('Animal AJENO');
        $this->get('/produccion')->assertOk()->assertDontSee('Finca AJENA')
            ->assertViewHas('animales', fn ($animales) => $animales->pluck('id')->all() === [$this->animal->id]);
    }

    public function test_server_rechecks_an_animal_moved_after_opening_the_create_form(): void
    {
        $this->actingAs($this->owner)->get('/produccion/create')->assertOk();
        $this->animal->update(['finca_id' => $this->destination->id]);
        $this->postJson('/produccion', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('finca_id');
        $this->assertDatabaseCount('producciones', 0);
    }

    public function test_original_references_and_date_survive_a_validation_redirect(): void
    {
        $registro = $this->registro();
        $this->animal->update(['estado' => 'vendido']);
        $editUrl = '/produccion/'.$registro->id.'/edit';
        $this->actingAs($this->owner)->from($editUrl)->put('/produccion/'.$registro->id,
            array_replace($this->payload(), ['finca_id' => $this->destination->id, 'fecha' => '2026-11-17']))
            ->assertRedirect($editUrl)->assertSessionHasErrors(['fecha', 'finca_id']);
        $this->get($editUrl)->assertOk()
            ->assertSee('name="finca_id" value="'.$this->finca->id.'"', false)
            ->assertSee('name="fecha" value="2026-11-16"', false)
            ->assertSee('requiere revisión online');
    }

    public function test_optional_fields_can_be_cleared_when_correcting_a_record(): void
    {
        $registro = $this->registro();
        $this->actingAs($this->owner)->put('/produccion/'.$registro->id,
            array_replace($this->payload(), ['sesion' => null, 'calidad' => null, 'observaciones' => null]))
            ->assertRedirect();
        $registro->refresh();
        $this->assertNull($registro->sesion);
        $this->assertNull($registro->calidad);
        $this->assertNull($registro->observaciones);
    }

    public function test_deleted_original_reference_blocks_corrections_without_resurrecting_data(): void
    {
        $registro = $this->registro();
        $before = $registro->fresh()->getRawOriginal();
        $this->animal->delete();
        $this->actingAs($this->owner)->get('/produccion/'.$registro->id.'/edit')->assertNotFound();
        $this->putJson('/produccion/'.$registro->id, $this->payload())->assertNotFound();
        $this->assertSame($before, $registro->fresh()->getRawOriginal());
        $this->assertSoftDeleted('animales', ['id' => $this->animal->id]);
    }

    private function payload(): array
    {
        return ['animal_id' => $this->animal->id, 'finca_id' => $this->finca->id,
            'fecha' => '2026-11-16', 'sesion' => 'manana', 'litros' => '12.35',
            'calidad' => 'buena', 'observaciones' => 'Medición ficticia'];
    }

    private function registro(): Produccion
    {
        return Produccion::create($this->payload() + ['user_id' => $this->owner->id, 'codigo' => 'TEST-PROD']);
    }

    private function finca(User $owner, string $tag): Finca
    {
        return Finca::create(['nombre' => 'Finca '.$tag, 'codigo' => 'TEST-FIN-'.$tag, 'user_id' => $owner->id, 'activa' => true]);
    }

    private function animal(User $owner, Finca $finca, string $tag): Animal
    {
        return Animal::create(['nombre' => 'Animal '.$tag, 'codigo' => 'TEST-ANI-'.$tag,
            'user_id' => $owner->id, 'finca_id' => $finca->id, 'tipo' => 'vaca', 'sexo' => 'hembra', 'estado' => 'activo']);
    }
}
