<?php

namespace App\Http\Controllers;

use App\Services\ProductionSyncService;
use Illuminate\Http\Request;

class ProductionSyncController extends Controller
{
    public function __construct(private readonly ProductionSyncService $sync) {}

    private function respond(array $body)
    {
        return response()->json($body)->header('Cache-Control', 'no-store, private');
    }

    private function enabled(): void
    {
        abort_unless(config('offline.production.enabled'), 503, 'El piloto de sincronización está pausado.');
    }

    public function snapshot(Request $request)
    {
        $this->enabled();

        return $this->respond($this->sync->snapshot($request->user(), $request->all()));
    }

    public function page(Request $request, string $snapshot)
    {
        $this->enabled();
        $data = $request->validate(['page' => 'sometimes|integer|min:1']);

        return $this->respond($this->sync->snapshotPage($request->user(), $snapshot, $data['page'] ?? 1));
    }

    public function changes(Request $request)
    {
        $this->enabled();
        $data = $request->validate(['cursor' => 'required|string|max:8000', 'limit' => 'sometimes|integer|min:1|max:200']);

        return $this->respond($this->sync->changes($request->user(), $data['cursor'], $data['limit'] ?? 100));
    }

    public function push(Request $request)
    {
        $this->enabled();
        $data = $request->validate(['account_id' => 'required|integer|min:1', 'device_uuid' => 'required|uuid', 'operations' => 'required|array|min:1|max:50', 'operations.*' => 'array']);
        if ((string) $data['account_id'] !== (string) $request->user()->id) {
            return response()->json(['error' => 'account_mismatch'], 409)->header('Cache-Control', 'no-store, private');
        }
        $results = [];
        foreach ($data['operations'] as $operation) {
            $results[] = ['operation_uuid' => $operation['operation_uuid'] ?? null] + $this->sync->applyOperation($request->user(), $data['device_uuid'], $operation);
        }

        return $this->respond(['results' => $results]);
    }
}
