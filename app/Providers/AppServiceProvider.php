<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Eloquent\ExamRepository;
use App\Repositories\Eloquent\QuestionRepository;
use App\Repositories\Eloquent\StudentRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Repositories 
        $this->app->bind('App\Repositories\ExamRepository', ExamRepository::class);
        $this->app->bind('App\Repositories\QuestionRepository', QuestionRepository::class);
        $this->app->bind('App\Repositories\StudentRepository', StudentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->routes(function (Route $route) {
                return Str::startsWith($route->uri, 'api/');
            });
    }
}
