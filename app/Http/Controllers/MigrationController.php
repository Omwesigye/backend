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
}