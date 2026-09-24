<?php

namespace App\Providers;

use App\Shared\Infrastructure\Inertia\FeaturePageViewFinder;
use App\Shared\Infrastructure\Keamanan\PenjagaKonfigurasiProduksi;
use App\Shared\Infrastructure\Validasi\NamaIsianBaris;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator as PabrikValidator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Validator;

class AppServiceProvider extends ServiceProvider
{
    /** Register any application services. */
    public function register(): void
    {
        $this->app->bind('inertia.view-finder', function ($app) {
            return new FeaturePageViewFinder(
                $app['files'],
                $app['config']->get('inertia.pages.paths'),
                $app['config']->get('inertia.pages.extensions')
            );
        });
    }

    /** Bootstrap any application services. */
    public function boot(): void
    {
        // Inertia props tidak butuh amplop 'data' ala API; frontend memakai prop resource langsung.
        JsonResource::withoutWrapping();

        // Isian baris (`Detail.0.HargaSatuan`) ditulis "harga satuan baris 1" di pesan validasi.
        PabrikValidator::resolver(function (Translator $penerjemah, array $data, array $aturan, array $pesan, array $atribut): Validator {
            $validator = new Validator($penerjemah, $data, $aturan, $pesan, $atribut);
            $validator->setImplicitAttributesFormatter(NamaIsianBaris::tampilkan(...));

            return $validator;
        });

        PenjagaKonfigurasiProduksi::periksa($this->app);
    }
}
