<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  $userType
     * @param  mixed  ...$allowedTypes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $userType, ...$allowedTypes)
    {
        $allowed = array_merge([$userType], $allowedTypes);
        if(in_array(auth()->user()->type, $allowed)){
            return $next($request);
        }

        return response()->json(['You do not have permission to access for this page.']);
    }

}
