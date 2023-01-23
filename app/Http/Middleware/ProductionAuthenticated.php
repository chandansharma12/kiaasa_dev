<?php
namespace App\Http\Middleware;

use Auth;
use Closure;

class ProductionAuthenticated
{
    public function handle($request, Closure $next)
    {
        if( Auth::check() ) {
            if(Auth::user()->isProduction()) {
                return $next($request);
            }else{
                return redirect('access-denied');
            }
        }

        abort(404);  // for other user throw 404 error
    }
}