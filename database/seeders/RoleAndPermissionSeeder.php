<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Profile;


class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->truncateTables();

        // Load roles and permissions from config
        $config = Config::get('spatie_seeder.roles_structure');

        if ($config === null) {
            $this->command->error("The configuration has not been published or is missing.");
            return;
        }
        
        foreach ($config as $roleName => $modules) {
            // Create a role
            $role = Role::firstOrCreate(['name' => $roleName, 'display_name' => ucwords(str_replace('_', ' ', $roleName))]);
            $permissions = [];

            $this->command->info("Creating Role: " . strtoupper($roleName));

            // Assign permissions to the role
            foreach ($modules as $module => $actions) {
                foreach (explode(',', $actions) as $action) {
                    $permissionName = "{$module}_{$action}";
                    $permissions[] = Permission::firstOrCreate([
                        'name' => $permissionName,
                        'display_name' => ucwords(str_replace('_', ' ', $permissionName)),//like users_read will be in db : Users Read

                    ])->id;

                    $this->command->info("Creating Permission: {$permissionName}");
                }
            }

            $role->permissions()->sync($permissions);
            // Optionally create default users
            if (Config::get('spatie_seeder.create_users', true)) {
                $this->createDefaultUsers($roleName);
            }
        }

    }

    /**
     * Truncate all related tables.
     */
    private function truncateTables(): void
    {
        $this->command->info('Truncating roles, permissions, and related tables');
        Schema::disableForeignKeyConstraints();

        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        Role::truncate();
        Permission::truncate();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Create default users for each role.
     */
    private function createDefaultUsers($roleName): void
    {
        $defaultUsers = [
            ['role' => $roleName , 'email' => $roleName . '@gmail.com']
        ];
        foreach ($defaultUsers as $data) {
            $user = User::create([
                'email' => $data['email'],
                'password' => 'password', // Ensure password is hashed
                'username' => $data['role'],

            ]);
            
            $profile = Profile::create([
                'user_id' => $user->id,
                'full_name' => $data['role']
            ]);
            
            $role = Role::where('name', $data['role'])->first();
            if ($role) {
                $user->assignRole($role);
                $this->command->info("Assigned Role: {$data['role']} to User: {$data['email']}");
            } else {
                $this->command->error("Role '{$data['role']}' not found for User: {$data['email']}");
            }

        }
    }
}
