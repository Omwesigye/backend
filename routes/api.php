<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ProviderProfileController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\MigrationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Public + Protected Routes for the API
|
*/

// --------------------
// PUBLIC ROUTES
// --------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/db-test', function () {
    $host = getenv('DB_HOST');
    $dsn = "pgsql:host=$host;port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_DATABASE');
    
    try {
        $pdo = new PDO($dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return response()->json([
            'success' => true,
            'message' => 'Database connected successfully!',
            'connection' => [
                'host' => $host,
                'port' => getenv('DB_PORT'),
                'database' => getenv('DB_DATABASE'),
                'username' => getenv('DB_USERNAME'),
                'dsn' => $dsn
            ]
        ]);
    } catch (PDOException $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'connection_details' => [
                'host' => $host,
                'dsn' => $dsn,
                'full_error' => $e
            ]
        ], 500);
    }
});

Route::get('/run-migrations', [MigrationController::class, 'run']);
Route::get('/test', function () {
    try {
        // Test database connection
        \DB::connection()->getPdo();
        
        // Check if migrations ran
        $tables = \DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = ?', ['public']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'API is working!',
            'database' => [
                'connected' => true,
                'name' => \DB::connection()->getDatabaseName(),
                'tables_count' => count($tables),
                'tables' => array_column($tables, 'table_name')
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Database connection failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/diagnose', function () {
    try {
        // Check basic Laravel
        $checks = [];
        
        // 1. Check APP_KEY
        $checks['app_key'] = !empty(env('APP_KEY')) ? '✓ Set' : '✗ Missing';
        
        // 2. Check database connection
        try {
            \DB::connection()->getPdo();
            $checks['database'] = '✓ Connected';
            $checks['db_name'] = \DB::connection()->getDatabaseName();
        } catch (\Exception $e) {
            $checks['database'] = '✗ Error: ' . $e->getMessage();
        }
        
        // 3. Check storage permissions
        $checks['storage_writable'] = is_writable(storage_path()) ? '✓ Writable' : '✗ Not writable';
        $checks['bootstrap_writable'] = is_writable(base_path('bootstrap/cache')) ? '✓ Writable' : '✗ Not writable';
        
        // 4. Check environment
        $checks['environment'] = app()->environment();
        $checks['debug_mode'] = config('app.debug') ? 'true (should be false in production)' : 'false';
        
        // 5. Check loaded .env file
        $checks['env_file'] = file_exists(base_path('.env')) ? '✓ Exists' : '✗ Missing';
        
        return response()->json([
            'status' => 'diagnostic',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Diagnostic failed',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Password Reset
Route::post('/password/forgot', [PasswordResetController::class, 'requestCode']);
Route::post('/password/verify', [PasswordResetController::class, 'verifyCode']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);

// Reports & Ratings
Route::post('/reports', [ReportController::class, 'store']);
Route::post('/ratings', [RatingController::class, 'store']);

// Providers
Route::get('/service-providers', [ServiceProviderController::class, 'index']);
Route::get('/providers', [ServiceProviderController::class, 'index']); // Alias

// Image fetch (wildcard path)
Route::get('/image/{path}', [ServiceProviderController::class, 'showImage'])
    ->where('path', '.*');

// Chat Public
Route::post('/chat/send', [ChatController::class, 'sendMessage']);
Route::get('/chat/history/{userId}/{contactId}', [ChatController::class, 'getConversation']);


// --------------------
// PROTECTED ROUTES (auth:sanctum)
// --------------------
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);

    // ADMIN ROUTES
    Route::prefix('admin')->group(function () {
        Route::get('/reports', [ReportController::class, 'index']);

        Route::get('/pending-providers', [AdminController::class, 'pendingProviders']);
        Route::post('/approve-provider/{id}', [AdminController::class, 'approveProvider']);

        Route::get('/users', [AdminController::class, 'users']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        Route::get('/providers', [AdminController::class, 'allProviders']);
        Route::delete('/providers/{id}', [AdminController::class, 'deleteProvider']);

        Route::get('/bookings', [AdminController::class, 'bookings']);
        Route::post('/bookings/{id}/status', [AdminController::class, 'updateBookingStatus']);
    });

    // BOOKING ROUTES
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
    Route::get('/provider/bookings', [BookingController::class, 'getProviderBookings']);
    Route::post('/bookings/{id}/accept', [BookingController::class, 'acceptBooking']);
    Route::post('/bookings/{id}/decline', [BookingController::class, 'declineBooking']);
    Route::post('/bookings/{id}/complete', [BookingController::class, 'completeBooking']);


    // --------------------
    // PAYMENT ROUTES
        Route::get('/payments/paypal-config', [PaymentController::class, 'getPayPalConfig']);
    // --------------------
    Route::post('/payments/process', [PaymentController::class, 'processPayment']);
    Route::get('/payments/status/{bookingId}', [PaymentController::class, 'getPaymentStatus']);
    Route::put('/payments/status/{bookingId}', [PaymentController::class, 'updatePaymentStatus']);

    // --------------------

    // PROVIDER PROFILE ROUTES
    Route::get('/provider/profile', [ProviderProfileController::class, 'show']);
    Route::post('/provider/profile', [ProviderProfileController::class, 'update']);

    Route::get('/provider/photos', [ProviderProfileController::class, 'getPhotos']);
    Route::post('/provider/photos', [ProviderProfileController::class, 'uploadPhoto']);
    Route::post('/provider/photos/delete', [ProviderProfileController::class, 'deletePhoto']);

    Route::get('/provider/ratings', [ProviderProfileController::class, 'getRatings']);

    // CHAT ROUTES
    Route::get('/chat/conversations', [ChatController::class, 'getConversations']);



    
});