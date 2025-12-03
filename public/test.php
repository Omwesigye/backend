<?php
// Simple test to see what's working
echo "<h1>PHP Test</h1>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server Time: " . date('Y-m-d H:i:s') . "<br>";

// Check Laravel files
echo "<h2>File Check:</h2>";
$files = [
    '../vendor/autoload.php' => 'Composer',
    '../bootstrap/app.php' => 'Laravel',
    '../.env' => 'Environment',
    '../storage' => 'Storage'
];

foreach ($files as $file => $name) {
    $path = __DIR__ . '/' . $file;
    echo "$name: " . (file_exists($path) ? "✅ Exists" : "❌ Missing") . "<br>";
}

// Safe environment check
echo "<h2>Environment Variables:</h2>";
$safeVars = ['APP_ENV', 'APP_DEBUG', 'DB_CONNECTION'];
foreach ($safeVars as $var) {
    $value = getenv($var);
    echo "$var = " . ($value ?: 'NOT SET') . "<br>";
}

// Try database connection
echo "<h2>Database Test:</h2>";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    
    // If we get here, Laravel is working
    echo "✅ Laravel is running!<br>";
    
    // Try database
    $dbHost = getenv('DB_HOST');
    echo "DB_HOST = " . ($dbHost ?: 'NOT SET') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}