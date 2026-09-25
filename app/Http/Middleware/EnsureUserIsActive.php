<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Reject API requests from deactivated accounts (is_active = false).
     * Sanctum tokens issued while the account was active stop working as soon
     * as an admin deactivates the customer.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        if ($user && ! $user->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'Your account has been deactivated. Please contact support.',
                'errors' => null,
            ], 401);
        }

        return $next($request);
    }
}
