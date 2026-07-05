cd ~/hub.veloxi.fr
cat > create_admin.php <<'PHP'
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

// ✅ Paramètres
$name = 'Veloxi Admin';
$email = 'admin@veloxi.fr';
$username = 'veloxi_admin';      // <- obligatoire chez toi
$passwordPlain = 'Veloxi#2026!';

// Evite doublons
$existing = User::where('email', $email)->first();
if ($existing) {
    echo "User already exists: id={$existing->id} email={$existing->email}\n";
    exit;
}

// Crée user (avec username)
$user = User::create([
    'name' => $name,
    'username' => $username,
    'email' => $email,
    'password' => bcrypt($passwordPlain),
]);

// Assigne role admin (Spatie)
$user->assignRole('admin');

echo "CREATED: id={$user->id} email={$user->email} username={$user->username} role=admin\n";
PHP