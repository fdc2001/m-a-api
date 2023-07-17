<?php

namespace App\Http\Middleware;

use App\Models\Configs;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AuthOrbitMiddleware {
    public function handle(Request $request, Closure $next) {
        $orbit_key = Configs::where('field', 'orbit_key')->first();
        if($orbit_key) {
            $orbit_key = $orbit_key->value;
        }

        $orbit_key = Crypt::decryptString($orbit_key);
        $requestToken = $request->header('authorization');
        $requestToken = str_replace('Bearer ', '', $requestToken);
        if ($requestToken != $orbit_key){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
