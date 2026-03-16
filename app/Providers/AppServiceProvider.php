<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\MailServiceProvider as LaravelMailServiceProvider;
use Mailtrap\Bridge\Transport\MailtrapTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Illuminate\Support\Facades\Mail;


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
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(50)->by($request->ip()); // Súbelo a 50
        });

        Mail::extend('mailtrap', function (array $config) {
            return (new MailtrapTransportFactory)->create(
                new Dsn(
                    'mailtrap+api',
                    'default',
                    config('mail.mailers.mailtrap.api_key') // Buscamos la key en el config de mail
                )
            );
        });
    }
}
