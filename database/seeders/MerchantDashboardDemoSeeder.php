<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

class MerchantDashboardDemoSeeder extends Seeder
{
    public function run()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $password = env('VELOXI_DEMO_MERCHANT_PASSWORD');

        if (!$password && app()->environment(['local', 'testing'])) {
            $password = 'merchant-demo-local';
        }

        if (!$password) {
            throw new RuntimeException('VELOXI_DEMO_MERCHANT_PASSWORD must be defined outside local/testing environments.');
        }

        $role = Role::firstOrCreate(
            ['name' => 'merchant', 'guard_name' => 'web'],
            ['status' => 1]
        );

        $user = User::updateOrCreate(
            ['email' => 'merchant-demo@veloxi.local'],
            [
                'name' => 'Commerçant Démo Véloxi',
                'username' => 'merchant_demo_veloxi',
                'contact_number' => '0100000000',
                'password' => Hash::make($password),
                'user_type' => 'merchant',
                'status' => 1,
            ]
        );

        if (!$user->hasRole('merchant')) {
            $user->assignRole($role);
        }

        Merchant::where('slug', 'kebab-blancmesnil')
            ->update(['owner_user_id' => $user->id]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
