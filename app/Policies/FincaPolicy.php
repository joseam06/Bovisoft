<?php

namespace App\Policies;

use App\Models\Finca;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FincaPolicy
{
    public function view(User $user, Finca $finca): Response
    {
        return (string) $user->getAuthIdentifier() === (string) $finca->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, Finca $finca): Response
    {
        return $this->view($user, $finca);
    }

    public function delete(User $user, Finca $finca): Response
    {
        return $this->view($user, $finca);
    }

    public function toggleEstado(User $user, Finca $finca): Response
    {
        return $this->view($user, $finca);
    }
}
