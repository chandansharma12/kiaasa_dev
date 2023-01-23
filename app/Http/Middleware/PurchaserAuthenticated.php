<?php
namespace App\Http\Middleware;

use Auth;
use Closure;

class PurchaserAuthenticated
{
    public function handle($request, Closure $next)
    {
        if(Auth::check()){
            if (Auth::user()->isPurchaser()) {
                return $next($request);
            }else{
                return redirect(route('dashboard'));
            }
        }

        abort(404);  // for other user throw 404 error
    }
}