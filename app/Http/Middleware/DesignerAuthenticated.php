<?php
namespace App\Http\Middleware;

use Auth;
use Closure;

class DesignerAuthenticated
{
    public function handle($request, Closure $next)
    {
        if(Auth::check()){
            // if user is not designer take him to his dashboard
            if ( Auth::user()->isDesigner() ) {
                return $next($request);
            }else{
                return redirect(route('dashboard'));
            }
        }

        abort(404);  // for other user throw 404 error
    }
}