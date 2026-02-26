<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Interfaces\AuthRepositoryInterface;
use App\Interfaces\AlumniRepositoryInterface;
use App\Interfaces\AdminRepositoryInterface;
use App\Interfaces\LowonganRepositoryInterface;
use App\Interfaces\KuesionerRepositoryInterface;
use App\Interfaces\SectionQuesRepositoryInterface;
use App\Interfaces\MasterDataRepositoryInterface;
use App\Interfaces\StatusKarierRepositoryInterface;

use App\Repositories\AuthRepository;
use App\Repositories\AlumniRepository;
use App\Repositories\AdminRepository;
use App\Repositories\LowonganRepository;
use App\Repositories\KuesionerRepository;
use App\Repositories\SectionQuesRepository;
use App\Repositories\MasterDataRepository;
use App\Repositories\StatusKarierRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(AlumniRepositoryInterface::class, AlumniRepository::class);
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
        $this->app->bind(LowonganRepositoryInterface::class, LowonganRepository::class);
        $this->app->bind(KuesionerRepositoryInterface::class, KuesionerRepository::class);
        $this->app->bind(SectionQuesRepositoryInterface::class, SectionQuesRepository::class);
        $this->app->bind(MasterDataRepositoryInterface::class, MasterDataRepository::class);
        $this->app->bind(StatusKarierRepositoryInterface::class, StatusKarierRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
