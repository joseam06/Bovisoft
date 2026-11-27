<?php

namespace App\Models\Concerns;

trait AuthorizesFinance
{
    public function scopeAutorizadasPara($query, int $owner)
    {
        return $query->where($this->getTable().'.user_id', $owner)->whereHas('finca', fn ($q) => $q->delUsuario($owner))
            ->where(fn ($q) => $q->whereNull('animal_id')->orWhereHas('animal', fn ($a) => $a->where('user_id', $owner)->whereHas('finca', fn ($f) => $f->delUsuario($owner))))
            ->when($this instanceof \App\Models\Egreso, fn ($q) => $q->where(fn ($e) => $e->whereNull('salud_id')->orWhereHas('salud', fn ($s) => $s->where('user_id', $owner)->whereHas('finca', fn ($f)=>$f->delUsuario($owner))->whereHas('animal', fn ($a)=>$a->where('user_id',$owner)))));
    }
}
