<?php

namespace Tests\Support;

use App\Models\Finca;
use App\Models\Potrero;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PerformanceDataset
{
    private array $farms = [];

    private array $pads = [];

    private int $previous = 0;

    public function __construct(private User $user) {
        $this->previous=DB::table('animales')->where('user_id',$user->id)->count();
        $this->farms=DB::table('fincas')->where('user_id',$user->id)->orderBy('id')->pluck('id')->all();
        $this->pads=DB::table('potreros')->where('user_id',$user->id)->orderBy('id')->pluck('id')->all();
    }

    public function grow(int $n): void
    {
        for ($i = count($this->farms); $i < $n / 100; $i++) {
            $f = Finca::create(['user_id' => $this->user->id, 'codigo' => 'PERF-F-'.$i, 'nombre' => 'Finca ficticia '.$i, 'area' => 100, 'activa' => true, 'latitud' => 4.5, 'longitud' => -74]);
            $this->farms[] = $f->id;
            $this->pads[] = Potrero::create(['user_id' => $this->user->id, 'finca_id' => $f->id, 'codigo' => 'PERF-P-'.$i, 'nombre' => 'Potrero ficticio '.$i, 'estado' => 'activo', 'capacidad_animales' => 100, 'dias_descanso' => 30, 'fecha_ultima_rotacion' => '2026-11-01'])->id;
        }
        $animals = [];
        $health = [];
        $production = [];
        $income = [];
        $expense = [];
        for ($i = $this->previous + 1; $i <= $n; $i++) {
            $j = intdiv($i - 1, 100);
            $common = ['user_id' => $this->user->id, 'finca_id' => $this->farms[$j], 'created_at' => now(), 'updated_at' => now(), 'client_uuid' => (string) Str::uuid(), 'version' => 1];
            $animals[] = $common + ['id' => $i, 'codigo' => 'PERF-A-'.$i, 'nombre' => 'Animal ficticio '.$i, 'tipo' => 'vaca', 'sexo' => 'hembra', 'estado' => 'activo', 'potrero_id' => $this->pads[$j]];
            $health[] = array_replace($common, ['client_uuid' => (string) Str::uuid(), 'id' => $i, 'codigo' => 'PERF-S-'.$i, 'animal_id' => $i, 'tipo' => 'tratamiento', 'categoria' => 'clinico', 'nombre_producto' => 'Producto ficticio', 'fecha_aplicacion' => '2026-11-24', 'proxima_aplicacion' => '2026-11-25', 'fin_carencia' => '2026-11-26', 'estado' => 'en_tratamiento', 'costo' => '0.00']);
            $production[] = array_replace($common, ['client_uuid' => (string) Str::uuid(), 'id' => $i, 'codigo' => 'PERF-L-'.$i, 'animal_id' => $i, 'fecha' => '2026-11-24', 'litros' => '12.35', 'sesion' => 'manana']);
            if ($i % 5 === 0) {
                $income[] = array_replace($common, ['client_uuid' => (string) Str::uuid(), 'codigo' => 'PERF-I-'.$i, 'fecha' => '2026-11-24', 'tipo' => 'otro', 'monto' => '100.25']);
                $expense[] = array_replace($common, ['client_uuid' => (string) Str::uuid(), 'codigo' => 'PERF-E-'.$i, 'fecha' => '2026-11-24', 'categoria' => 'otro', 'monto' => '40.10']);
            }
        }
        foreach (['animales' => $animals, 'salud' => $health, 'producciones' => $production, 'ingresos' => $income, 'egresos' => $expense] as $table => $rows) {
            foreach (array_chunk($rows, 250) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }
        foreach($this->pads as $id)DB::table('potreros')->where('id',$id)->update(['animales_actuales'=>DB::table('animales')->where('potrero_id',$id)->where('estado','activo')->count()]);
        $this->previous = $n;

    }
}
