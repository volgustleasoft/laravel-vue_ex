<?php

namespace App\Http\Middleware;

use App\Http\Traits\PincodeTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPincode
{
    use PincodeTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if(getenv('pincode_enabled')) {
            if(! empty($person = Auth::user())){
                if ($person->IsCareGiver or $person->IsManager) {
                    $this->updateSessionActivity($request, $person);
                }
            }
        }
        return $next($request);
    }
}
