<?php

namespace App\Http\Middleware;

use App\Models\Configs;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AuthWordpressMiddleware {
    public function handle(Request $request, Closure $next) {
        $wordpress_key = Configs::where('field', 'wordpress_key')->first();
        if($wordpress_key) {
            $wordpress_key = $wordpress_key->value;
        }
        $wordpress_key = Crypt::decryptString($wordpress_key);

        $requestToken = $request->header('authorization');
        $requestToken = str_replace('Bearer ', '', $requestToken);
        if ($requestToken != $wordpress_key){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
