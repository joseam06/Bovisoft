<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class WorkspaceVersionConflict extends RuntimeException
{
    public function __construct(public readonly Model $current)
    {
        parent::__construct('El registro cambió en otro dispositivo. Revisa el conflicto antes de guardar.');
    }
}
