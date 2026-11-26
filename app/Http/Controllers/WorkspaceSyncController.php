<?php

namespace App\Http\Controllers;

use App\Services\WorkspaceSync;
use Illuminate\Http\Request;

class WorkspaceSyncController extends Controller
{
    private function enabled(): void
    {
        abort_unless(config('offline.workspace.enabled'), 503, 'La sincronización de Fincas y Animales está pausada. Conserva tus pendientes.');
    }

    public function snapshot(Request $request, WorkspaceSync $sync)
    {
        $this->enabled();

        return response()->json($sync->snapshot($request->user(), $request->input('format') === 'catalog-chunks-v1'))->header('Cache-Control', 'no-store');
    }

    public function page(Request $request, string $snapshot, WorkspaceSync $sync)
    {
        $this->enabled();
        $data = $request->validate(['page' => 'required|integer|min:1']);

        return response()->json($sync->page($request->user(), $snapshot, $data['page']))->header('Cache-Control', 'no-store');
    }

    public function changes(Request $request, WorkspaceSync $sync)
    {
        $this->enabled();
        $data = $request->validate(['cursor' => 'required|string|max:4096']);

        return response()->json($sync->changes($request->user(), $data['cursor']))->header('Cache-Control', 'no-store');
    }

    public function operation(Request $request, WorkspaceSync $sync)
    {
        $this->enabled();
        $data = $request->validate(['account_id' => 'required|integer', 'device_uuid' => 'required|uuid', 'operation' => 'required|array']);
        abort_unless((int) $data['account_id'] === $request->user()->id, 409, 'La cuenta cambió. Los pendientes se conservan bloqueados.');

        $result = $sync->apply($request->user(), $data['device_uuid'], $data['operation']);

        return response()->json($result + [
            'protocol' => 1, 'account_id' => $request->user()->id,
            'operation_uuid' => strtolower($data['operation']['operation_uuid']),
            'record_uuid' => strtolower($data['operation']['record_uuid']),
            'module' => $data['operation']['module'],
        ])->header('Cache-Control', 'no-store');
    }
}
