<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use App\Models\Perusahaan;
use App\Models\Universitas;
use App\Models\Wirausaha;
use App\Observers\PerusahaanObserver;
use App\Observers\UniversitasObserver;
use App\Observers\WirausahaObserver;

use App\Interfaces\AuthRepositoryInterface;
use App\Interfaces\AlumniRepositoryInterface;
use App\Interfaces\AdminRepositoryInterface;
use App\Interfaces\LowonganRepositoryInterface;
use App\Interfaces\KuesionerRepositoryInterface;
use App\Interfaces\MasterDataRepositoryInterface;
use App\Interfaces\StatusKarierRepositoryInterface;
use App\Interfaces\PengumumanRepositoryInterface;
use App\Interfaces\PengaturanTampilanRepositoryInterface;
use App\Interfaces\KemitraanRepositoryInterface;
use App\Interfaces\SebaranAlumniRepositoryInterface;
use App\Interfaces\Alumni\BerandaRepositoryInterface;
use App\Interfaces\Alumni\LowonganAlumniRepositoryInterface;
use App\Interfaces\Alumni\AlumniDirectoryRepositoryInterface;
use App\Interfaces\Alumni\ProfileRepositoryInterface;
use App\Interfaces\Alumni\ConnectionRepositoryInterface;
use App\Interfaces\Alumni\PostRepositoryInterface;
use App\Interfaces\MessageRepositoryInterface;

use App\Repositories\AuthRepository;
use App\Repositories\AlumniRepository;
use App\Repositories\AdminRepository;
use App\Repositories\LowonganRepository;
use App\Repositories\KuesionerRepository;
use App\Repositories\MasterDataRepository;
use App\Repositories\StatusKarierRepository;
use App\Repositories\PengumumanRepository;
use App\Repositories\PengaturanTampilanRepository;
use App\Repositories\KemitraanRepository;
use App\Repositories\SebaranAlumniRepository;
use App\Repositories\Alumni\BerandaRepository;
use App\Repositories\Alumni\LowonganAlumniRepository;
use App\Repositories\Alumni\AlumniDirectoryRepository;
use App\Repositories\Alumni\ProfileRepository;
use App\Repositories\Alumni\ConnectionRepository;
use App\Repositories\Alumni\PostRepository;
use App\Repositories\MessageRepository;

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
        $this->app->bind(MasterDataRepositoryInterface::class, MasterDataRepository::class);
        $this->app->bind(StatusKarierRepositoryInterface::class, StatusKarierRepository::class);
        $this->app->bind(BerandaRepositoryInterface::class, BerandaRepository::class);
        $this->app->bind(LowonganAlumniRepositoryInterface::class, LowonganAlumniRepository::class);
        $this->app->bind(AlumniDirectoryRepositoryInterface::class, AlumniDirectoryRepository::class);
        $this->app->bind(ProfileRepositoryInterface::class, ProfileRepository::class);
        $this->app->bind(PengumumanRepositoryInterface::class, PengumumanRepository::class);
        $this->app->bind(PengaturanTampilanRepositoryInterface::class, PengaturanTampilanRepository::class);
        $this->app->bind(KemitraanRepositoryInterface::class, KemitraanRepository::class);
        $this->app->bind(SebaranAlumniRepositoryInterface::class, SebaranAlumniRepository::class);
        $this->app->bind(ConnectionRepositoryInterface::class, ConnectionRepository::class);
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, MessageRepository::class);

        // Register Laravel Telescope (DISABLED untuk performa)
        // if ($this->app->environment('local')) {
        //     $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        // }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent lazy loading in non-production to catch N+1 queries early
        // DISABLED untuk performa di development
        // Model::preventLazyLoading(!app()->environment('production'));

        // Prevent silently discarding attributes not in $fillable
        Model::preventSilentlyDiscardingAttributes(!app()->environment('production'));

        // Auto-geocode saat entity baru dibuat/diupdate
        Perusahaan::observe(PerusahaanObserver::class);
        Universitas::observe(UniversitasObserver::class);
        Wirausaha::observe(WirausahaObserver::class);
    }
}
