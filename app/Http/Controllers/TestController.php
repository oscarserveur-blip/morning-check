<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class TestController extends Controller
{
    public function test()
    {
        return response()->json(['message' => 'Test controller works!']);
    }

    public function testUser()
    {
        try {
            $users = User::all();
            return response()->json(['message' => 'User model works!', 'count' => $users->count()]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
