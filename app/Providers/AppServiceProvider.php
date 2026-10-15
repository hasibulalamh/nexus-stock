<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The admin template is Bootstrap 5, so use Bootstrap pagination markup
        Paginator::useBootstrapFive();

        // Superadmin passes every permission check.
        // Return null (not false) so other users fall through to normal checks.
        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });

        Event::listen(Login::class, function (Login $event): void {
            activity()->causedBy($event->user)->event('login')->log('Logged in');
        });
        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user) {
                activity()->causedBy($event->user)->event('logout')->log('Logged out');
            }
        });
    }
}
