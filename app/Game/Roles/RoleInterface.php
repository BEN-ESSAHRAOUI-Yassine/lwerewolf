<?php

namespace App\Game\Roles;

interface RoleInterface
{
    public function getKey(): string;
    public function getName(string $locale): string;
    public function getFaction(): string;
    public function getNightOrder(): ?int;
    public function getAbilities(): array;
    public function getWinCondition(): string;
    public function hasNightAction(): bool;
}
