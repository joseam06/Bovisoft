<?php

namespace App\Services;

use Carbon\Carbon;

final class AlertRules
{
    // Declarative rules downloaded by the browser; the same catalog drives PHP.
    public static function catalog(): array
    {
        return ['categories' => ['preventivo' => 'Preventivo', 'clinico' => 'Clínico', 'reproductivo' => 'Reproductivo', 'seguimiento' => 'Seguimiento'], 'rotation' => ['max' => 7, 'level' => 'advertencia', 'title' => 'Rotación de potrero: {nombre}', 'description' => '{nombre} — próxima rotación {fecha}'], 'types' => \App\Models\Salud::getTipos(), 'rules' => [
            ['key' => 'vencida', 'field' => 'proxima_aplicacion', 'max' => -1, 'level' => 'critico', 'title' => 'Aplicación vencida: {tipo}', 'description' => '{animal} — venció hace {n} día{plural}', 'icon' => 'fa-circle-exclamation', 'color' => 'red'],
            ['key' => 'hoy', 'field' => 'proxima_aplicacion', 'min' => 0, 'max' => 0, 'level' => 'urgente', 'title' => 'Aplicación pendiente HOY: {tipo}', 'description' => '{animal} — programado para hoy', 'icon' => 'fa-bell', 'color' => 'orange'],
            ['key' => 'proxima', 'field' => 'proxima_aplicacion', 'min' => 1, 'max' => 7, 'level' => 'advertencia', 'title' => 'Próxima aplicación: {tipo}', 'description' => '{animal} — en {n} día{plural}', 'icon' => 'fa-clock', 'color' => 'yellow'],
            ['key' => 'pronto', 'field' => 'proxima_aplicacion', 'min' => 8, 'max' => 15, 'level' => 'info', 'title' => 'Aplicación en {n} días: {tipo}', 'description' => '{animal} — {fecha}', 'icon' => 'fa-calendar-check', 'color' => 'blue'],
            ['key' => 'tratamiento', 'field' => 'fecha_aplicacion', 'state' => 'en_tratamiento', 'skip_next' => 15, 'level' => 'info', 'title' => 'Tratamiento activo: {tipo}', 'description' => '{animal} — en tratamiento', 'icon' => 'fa-heart-pulse', 'color' => 'purple'],
            ['key' => 'carencia', 'field' => 'fin_carencia', 'min' => 0, 'level' => 'info', 'title' => 'Período de carencia activo', 'description' => '{animal} — vence en {n} día{plural}', 'icon' => 'fa-shield-halved', 'color' => 'teal'],
        ]];
    }

    public static function generate(array $health, string $today, array $paddocks = []): array
    {
        $catalog = self::catalog();
        $alerts = [];
        $todayDate = Carbon::parse($today);
        $dateCache = [];
        $cutoffs = [];
        $dateInfo = function (string $raw) use ($todayDate, &$dateCache): array {
            if (! isset($dateCache[$raw])) {
                $date = Carbon::parse($raw);
                $dateCache[$raw] = ['days' => (int) $todayDate->diffInDays($date, false), 'formatted' => $date->format('d/m/Y'), 'timestamp' => $date->timestamp];
            }

            return $dateCache[$raw];
        };
        foreach ($health as $record) {
            if (($record['estado'] ?? '') === 'cancelado' || ($record['deleted'] ?? false)) {
                continue;
            }
            foreach ($catalog['rules'] as $rule) {
                $date = $record[$rule['field']] ?? null;
                if (! $date) {
                    continue;
                }
                $info = $dateInfo($date);
                $days = $info['days'];
                if (isset($rule['state']) && $record['estado'] !== $rule['state']) {
                    continue;
                }
                if (isset($rule['skip_next']) && ! empty($record['proxima_aplicacion'])) {
                    $cutoffs[$rule['skip_next']] ??= $todayDate->copy()->addDays($rule['skip_next'])->timestamp;
                    if ($dateInfo($record['proxima_aplicacion'])['timestamp'] <= $cutoffs[$rule['skip_next']]) {
                        continue;
                    }
                }
                if (isset($rule['min']) && $days < $rule['min'] || isset($rule['max']) && $days > $rule['max']) {
                    continue;
                }
                $n = abs($days);
                $fields = ['{tipo}' => $catalog['types'][$record['tipo']] ?? $record['tipo'], '{animal}' => $record['animal_nombre'] ?? 'Animal', '{n}' => (string) $n, '{plural}' => $n === 1 ? '' : 's', '{fecha}' => $info['formatted']];
                $alerts[] = ['id' => 'sal-'.$rule['key'].'-'.$record['record_uuid'], 'tipo' => $rule['key'], 'nivel' => $rule['level'],
                    'titulo' => strtr($rule['title'], $fields), 'descripcion' => strtr($rule['description'], $fields), 'animal' => $record['animal_nombre'] ?? 'Animal', 'finca' => $record['finca_nombre'] ?? 'Sin finca',
                    'fecha' => $fields['{fecha}'], 'salud_id' => $record['id'], 'categoria' => $record['categoria'] ?? null, 'icono' => $rule['icon'], 'color' => $rule['color']];
            }
        }
        foreach ($paddocks as $p) {
            if (($p['deleted'] ?? false) || empty($p['fecha_ultima_rotacion']) || empty($p['dias_descanso'])) {
                continue;
            }
            $date = Carbon::parse($p['fecha_ultima_rotacion'])->addDays((int) $p['dias_descanso']);
            if ($todayDate->diffInDays($date, false) > $catalog['rotation']['max']) {
                continue;
            }
            $fields = ['{nombre}' => $p['nombre'], '{fecha}' => $date->format('d/m/Y')];
            $alerts[] = ['id' => 'pot-rotacion-'.$p['record_uuid'], 'tipo' => 'rotacion', 'nivel' => $catalog['rotation']['level'], 'titulo' => strtr($catalog['rotation']['title'], $fields), 'descripcion' => strtr($catalog['rotation']['description'], $fields), 'animal' => $p['nombre'], 'finca' => $p['finca_nombre'] ?? 'Sin finca', 'fecha' => $date->format('d/m/Y'), 'salud_id' => null, 'url' => '/potreros/'.$p['id'], 'categoria' => null, 'icono' => 'fa-arrows-rotate', 'color' => 'yellow'];
        }
        $rank = ['critico' => 0, 'urgente' => 1, 'advertencia' => 2, 'info' => 3];
        usort($alerts, fn ($a, $b) => ($rank[$a['nivel']] <=> $rank[$b['nivel']]) ?: strcmp($a['id'], $b['id']));

        return $alerts;
    }
}
