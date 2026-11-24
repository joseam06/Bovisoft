<?php

namespace App\Models\Concerns;

use App\Exceptions\WorkspaceVersionConflict;
use App\Services\WorkspaceJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait SyncsWorkspace
{
    // Covers normal forms and Eloquent writes from other modules (e.g. Finanzas).
    // Bulk SQL updates intentionally do not form part of the synchronization contract.
    public function save(array $options = [])
    {
        return app(WorkspaceJournal::class)->transaction((int) $this->user_id, function () use ($options) {
            $oldPotrero = null;
            if ($this->exists) {
                $current = static::withTrashed()->lockForUpdate()->findOrFail($this->getKey());
                if ((int) $current->version !== (int) $this->version) {
                    throw new WorkspaceVersionConflict($current);
                }
                $this->version = (int) $current->version + 1;
                $oldPotrero = $current instanceof \App\Models\Animal ? $current->potrero_id : null;
            } else {
                $this->client_uuid ??= (string) Str::uuid();
                $this->version = 1;
            }
            $result = parent::save($options);
            if ($result) {
                app(WorkspaceJournal::class)->record($this);
                if ($this instanceof \App\Models\Animal) app(\App\Services\WorkspaceRecords::class)->refreshOccupancy((int) $this->user_id, [$oldPotrero, $this->potrero_id]);
            }

            return $result;
        });
    }

    public function delete()
    {
        if (! $this->exists) {
            return null;
        }

        return app(WorkspaceJournal::class)->transaction((int) $this->user_id, function () {
            $current = static::withTrashed()->lockForUpdate()->findOrFail($this->getKey());
            if ((int) $current->version !== (int) $this->version) {
                throw new WorkspaceVersionConflict($current);
            }
            $result = parent::delete();
            $this->version = (int) $current->version + 1;
            DB::table($this->getTable())->where('id', $this->id)->update(['version' => $this->version]);
            app(WorkspaceJournal::class)->record($this);
            if ($this instanceof \App\Models\Animal) app(\App\Services\WorkspaceRecords::class)->refreshOccupancy((int) $this->user_id, [$current->potrero_id]);

            return $result;
        });
    }
}
