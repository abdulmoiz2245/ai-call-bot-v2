<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Super admin can access everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Regular users must have a company
        if (!$user->company_id) {
            abort(403, 'No company assigned to user');
        }

        // Add company scoping to the request
        $request->merge(['company_id' => $user->company_id]);
        
        // Store company ID in app for global access
        app()->instance('tenant.company_id', $user->company_id);
        
        return $next($request);
    }
}
