<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DropdownSyncController extends Controller
{
    // 1. API for Flutter team to fetch all Clients
    public function getClients()
    {
        $clients = DB::table('clients')
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Clients metadata stream loaded successfully.',
            'count'   => $clients->count(),
            'data'    => $clients
        ], 200);
    }

    // 2. API for Flutter team to fetch all Product Types
    public function getProducts()
    {
        $products = DB::table('products')
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Product types metadata stream loaded successfully.',
            'count'   => $products->count(),
            'data'    => $products
        ], 200);
    }

    // 🔥 3. FIXED DROPDOWN SYNC METHOD: Line 51 target error resolution layout
    public function getAbstractors()
    {
        try {
            // Stripping out strict status filters to allow physical entries to flow to Flutter
            $abstractors = DB::table('users')
                ->where('role', 'abstractor')
                ->select('id', 'name', 'email', 'raw_password') // Added raw_password for credentials board mapping
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Active Abstractors workflow stream loaded successfully.',
                'count'   => $abstractors->count(),
                'data'    => $abstractors // Wrapped properly inside data key node structure
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}