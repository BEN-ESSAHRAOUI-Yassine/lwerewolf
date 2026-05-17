<?php

namespace App\Game\Roles;

abstract class BaseRole implements RoleInterface
{
    public function hasNightAction(): bool
    {
        return $this->getNightOrder() !== null;
    }
}
