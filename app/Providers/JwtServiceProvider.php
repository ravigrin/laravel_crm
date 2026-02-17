<?php

namespace App\Providers;

use PHPOpenSourceSaver\JWTAuth\Http\Parser\AuthHeaders;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\InputSource;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\QueryString;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\RouteParams;
use PHPOpenSourceSaver\JWTAuth\Providers\AbstractServiceProvider;

class JwtServiceProvider extends AbstractServiceProvider
{
    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        // Register middleware aliases
        $this->app['router']->aliasMiddleware('jwt.auth', \PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate::class);
        $this->app['router']->aliasMiddleware('jwt.refresh', \PHPOpenSourceSaver\JWTAuth\Http\Middleware\RefreshToken::class);
        
        $this->extendAuthGuard();
        $this->app['tymon.jwt.parser']->setChain([
            with(new AuthHeaders)->setHeaderPrefix('token'),
            new QueryString,
            new InputSource,
            new RouteParams,
        ]);
    }
}
