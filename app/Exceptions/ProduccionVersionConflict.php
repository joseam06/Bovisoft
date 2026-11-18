<?php

namespace App\Exceptions;

use App\Models\Produccion;
use App\Services\ProductionJournal;
use Illuminate\Http\Request;
use RuntimeException;

class ProduccionVersionConflict extends RuntimeException
{
    public function __construct(public readonly Produccion $current)
    {
        parent::__construct('Este registro cambió desde que abriste el formulario. Revisa la versión actual y vuelve a abrir la edición antes de reenviar tus cambios.');
    }

    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'version_conflict', 'message' => $this->getMessage(), 'current' => ProductionJournal::serialize($this->current)], 409);
        }

        return redirect()->back()->withErrors(['base_version' => $this->getMessage()])->withInput($request->except('_token'));
    }
}
