<?php

namespace Tests\Unit;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_roles_are_seeded(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->assertCount(24, Role::all());
    }

    public function test_each_role_has_required_fields(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $roles = Role::all();

        foreach ($roles as $role) {
            $this->assertNotNull($role->key);
            $this->assertNotNull($role->faction);
            $this->assertNotNull($role->win_condition);
            $this->assertContains($role->faction, ['village', 'werewolves', 'white_werewolf', 'pied_piper', 'angel']);
        }
    }

    public function test_night_order_is_unique_for_active_roles(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $nightOrders = Role::whereNotNull('night_order')->pluck('night_order')->toArray();
        $this->assertCount(count(array_unique($nightOrders)), $nightOrders);
    }
}
