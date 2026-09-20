<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Shared\Domain\Exceptions\AksesDitolak;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use App\Shared\Domain\Exceptions\KonflikData;
use App\Shared\Domain\Exceptions\PengecualianDomain;
use App\Shared\Domain\Exceptions\VersiDataBerubah;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PengecualianDomainTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string<PengecualianDomain>, 1: int, 2: string}>
     */
    public static function daftarPengecualian(): array
    {
        return [
            'aturan bisnis dilanggar' => [AturanBisnisDilanggar::class, 422, 'ATURAN_BISNIS_DILANGGAR'],
            'akses ditolak' => [AksesDitolak::class, 403, 'AKSES_DITOLAK'],
            'data tidak ditemukan' => [DataTidakDitemukan::class, 404, 'DATA_TIDAK_DITEMUKAN'],
            'konflik data' => [KonflikData::class, 409, 'KONFLIK_DATA'],
            'versi data berubah' => [VersiDataBerubah::class, 409, 'VERSI_DATA_BERUBAH'],
        ];
    }

    /**
     * @param  class-string<PengecualianDomain>  $kelasPengecualian
     */
    #[DataProvider('daftarPengecualian')]
    public function test_pengecualian_domain_dipetakan_konsisten_untuk_permintaan_json(
        string $kelasPengecualian,
        int $statusDiharapkan,
        string $kodeErrorDiharapkan,
    ): void {
        Route::get('/_uji/pengecualian', function () use ($kelasPengecualian): never {
            throw new $kelasPengecualian('pesan uji coba');
        });

        $response = $this->getJson('/_uji/pengecualian');

        $response->assertStatus($statusDiharapkan);
        $response->assertJson([
            'pesan' => 'pesan uji coba',
            'kode_error' => $kodeErrorDiharapkan,
        ]);
    }

    public function test_pengecualian_domain_tidak_menampilkan_stack_trace_pada_permintaan_web(): void
    {
        config(['app.debug' => false]);

        Route::get('/_uji/pengecualian-web', function (): never {
            throw new DataTidakDitemukan('aset tidak ditemukan');
        });

        $response = $this->get('/_uji/pengecualian-web');

        $response->assertStatus(404);
        $response->assertDontSee('Stack trace', escape: false);
        $response->assertDontSee(__FILE__, escape: false);
    }
}
