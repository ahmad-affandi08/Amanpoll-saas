<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Controllers;

use App\Domain\Pelaporan\Application\Services\LayananDasbor;
use App\Domain\Pelaporan\Application\Services\LayananMetrik;
use App\Domain\Pelaporan\Domain\Enums\BentukKomponen;
use App\Domain\Pelaporan\Domain\KatalogKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\DasborTersimpan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Dasbor operasional dan manajemen (21.02). */
final class DasborController extends Controller
{
    public function __invoke(
        Request $request,
        LayananDasbor $layananDasbor,
        LayananMetrik $layananMetrik,
    ): Response {
        $pengguna = $request->user('web');
        $filter = FilterMetrik::dariArray($request->all());

        $tersimpan = $layananDasbor->dasborUntuk($pengguna);
        $dipilih = $this->pilihDasbor($request, $tersimpan);

        $susunan = $dipilih instanceof DasborTersimpan
            ? $layananDasbor->dariTersimpan($dipilih)
            : $layananDasbor->preset($pengguna);

        $kunciKpi = array_values(array_unique(array_map(
            fn (array $komponen): string => (string) $komponen['KunciKpi'],
            $susunan['Komponen'],
        )));

        return Inertia::render('Dashboard/Index', [
            'susunan' => $susunan,
            'metrik' => $layananMetrik->hitungBanyak($kunciKpi, $filter, $pengguna),
            'filter' => $filter->keArray(),
            'dasborTersimpan' => $tersimpan
                ->map(fn (DasborTersimpan $dasbor): array => [
                    'Id' => $dasbor->Id,
                    'Nama' => $dasbor->Nama,
                    'Bawaan' => $dasbor->Bawaan,
                    'Milik' => $dasbor->PemilikId === $pengguna->Id,
                ])
                ->values()
                ->all(),
            'pilihanUnit' => UnitOrganisasi::query()
                ->orderBy('Nama')
                ->get(['Id', 'Nama'])
                ->map(fn (UnitOrganisasi $unit): array => ['Id' => $unit->Id, 'Nama' => $unit->Nama])
                ->all(),
            'pilihanLokasi' => Lokasi::query()
                ->orderBy('Nama')
                ->get(['Id', 'Nama'])
                ->map(fn (Lokasi $lokasi): array => ['Id' => $lokasi->Id, 'Nama' => $lokasi->Nama])
                ->all(),
            'katalogKpi' => $this->katalogDenganBentuk($layananMetrik->katalogUntuk($pengguna)),
        ]);
    }

    /**
     * Melengkapi katalog dengan bentuk yang sah untuk tiap KPI, supaya pemilih
     * komponen di klien tidak menawarkan bagan yang akan ditolak server.
     *
     * @param  list<array<string, mixed>>  $katalog
     * @return list<array<string, mixed>>
     */
    private function katalogDenganBentuk(array $katalog): array
    {
        return array_map(function (array $kpi): array {
            $definisi = KatalogKpi::ambil((string) $kpi['Kunci']);

            return [
                ...$kpi,
                'Bentuk' => array_map(
                    fn (BentukKomponen $bentuk): array => ['Nilai' => $bentuk->value, 'Label' => $bentuk->label()],
                    BentukKomponen::untukKpi($definisi),
                ),
            ];
        }, $katalog);
    }

    /** @param Collection<int, DasborTersimpan> $tersimpan */
    private function pilihDasbor(Request $request, $tersimpan): ?DasborTersimpan
    {
        $diminta = $request->query('dasbor');

        if (is_string($diminta) && $diminta !== '') {
            // 'preset' adalah permintaan eksplisit untuk kembali ke bawaan peran.
            if ($diminta === 'preset') {
                return null;
            }

            return $tersimpan->firstWhere('Id', $diminta);
        }

        return $tersimpan->firstWhere('Bawaan', true);
    }
}
