<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MigrationController extends Controller
{
    public function run(Request $request)
    {
        // Simple security - you can remove/change this
        if ($request->get('key') !== 'migrate123') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Migrations completed',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createAdmin(Request $request)
{
    // Simple security - you can make this more secure
    if ($request->get('key') !== 'createadmin123') {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    try {
        // Check if admin already exists
        $existingAdmin = \App\Models\User::where('email', 'admin@taskconnect.com')->first();
        
        if ($existingAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin user already exists',
                'admin' => [
                    'id' => $existingAdmin->id,
                    'email' => $existingAdmin->email,
                    'role' => $existingAdmin->role
                ]
            ]);
        }
        
        // Create admin user
        $admin = \App\Models\User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('securepassword'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Admin user created successfully',
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'password' => 'securepassword' // Show only once!
            ],
            'note' => 'Change this password immediately after first login!'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}
}