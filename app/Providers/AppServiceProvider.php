<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\Models\Loan;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        View::composer('layouts.app', function ($view) {
        if (! auth()->check()) {
            return;
        }

        $view->with('dueAlert', [
            'overdue' => Loan::overdue()->count(),
            'soon'    => Loan::dueSoon()->count(),
        ]);
            });
    }
    
}