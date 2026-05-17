<?php

namespace App\Game\Actions\Werewolves;

use App\Game\Actions\BaseAction;
use App\Models\GameState;
use App\Models\Role;

class AccursedWolfFatherConvertAction extends BaseAction
{
    public function getPriority(): int { return 5; }

    public function isValid(GameState $state): bool
    {
        if (!parent::isValid($state)) return false;
        $data = $state->data ?? [];
        return empty($data['wolf_father_used']);
    }

    public function resolve(GameState $state): void
    {
        if (!$this->target) return;

        $werewolfRole = Role::where('key', 'werewolf')->first();
        if (!$werewolfRole) return;

        $this->target->role_id = $werewolfRole->id;
        $this->target->save();

        $data = $state->data ?? [];
        $data['wolf_father_used'] = true;
        $data['wolf_father_convert_target_id'] = $this->target->id;
        $state->data = $data;
        $state->save();
    }
}
