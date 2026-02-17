<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthenticateWithJWT
{
    /**
     * Handle an incoming request.
     * 
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = JWTAuth::parseToken();

            $entityId = $token->payload()->get('external_entity_id');
            $projectId = $token->payload()->get('external_project_id');

            if (!$entityId && !$projectId) {
                return response()->json([
                    'error' => 'Project id or Entity id should be specified'
                ], 403);
            }

            if ($entityId != $request->get('external_entity_id')
                && $projectId != $request->get('external_project_id')) {

                return response()->json([
                    'error' => 'Requested entity id is wrong'
                ], 403);
            }
        } catch (TokenExpiredException $e) {
            return response()->json([
                'error' => 'Provided token is expired.'
            ], 403);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'error' => 'An error while decoding token.'
            ], 403);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Can\'t parse token'
            ], 403);
        }

        return $next($request);
    }
}



