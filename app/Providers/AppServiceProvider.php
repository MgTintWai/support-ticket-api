<?php

namespace App\Providers;

use App\Contracts\CommentRepositoryInterface;
use App\Contracts\OrganizationRepositoryInterface;
use App\Contracts\TicketRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Repositories\CommentRepository;
use App\Repositories\OrganizationRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrganizationRepositoryInterface::class, OrganizationRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);
    }

    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
