<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\Models\Loan;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;

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
        
        // Hanya peran Admin (bukan Pengelola) yang boleh mengelola akun
        Gate::define('manage-users', fn ($user) => $user->role === 'admin');
    }
    
}