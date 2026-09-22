<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\LayananSesiDemo;
use App\Domain\Pemasaran\Domain\Enums\JenisEventDemo;
use App\Domain\Pemasaran\Domain\Enums\ModulDemo;
use App\Domain\Pemasaran\Http\Requests\CatatEventDemoRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DemoPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiDemo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AksesDitolak;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Sesi demo di host publik (MARKETING.md 11, 34.1). */
final class DemoPublikController extends Controller
{
    public function __construct(private readonly LayananSesiDemo $layanan) {}

    public function mulai(Request $request, DemoPemasaran $demo): JsonResponse
    {
        $pengenal = $request->attributes->get('pengenalPengunjung');

        if (! is_string($pengenal) || $pengenal === '') {
            throw new DataTidakDitemukan('Pengenal pengunjung belum terbentuk.');
        }

        $sesi = $this->layanan->mulai($demo, $pengenal, $this->sesiPengunjung($pengenal));

        return response()->json([
            'SesiDemoId' => $sesi->Id,
            'KedaluwarsaPada' => $sesi->KedaluwarsaPada->toIso8601String(),
            'ModulTampil' => $demo->ModulTampil ?? [],
            'FiturDibatasi' => $demo->FiturDibatasi ?? [],
            'CtaLabel' => $demo->CtaLabel,
            'CtaUrl' => $demo->CtaUrl,
        ], 201);
    }

    public function catat(CatatEventDemoRequest $request, SesiDemo $sesi): JsonResponse
    {
        $this->pastikanMilikPengunjung($request, $sesi);

        $data = $request->validated();
        $modul = isset($data['Modul']) ? ModulDemo::from((string) $data['Modul']) : null;

        /** @var array<string, mixed>|null $rincian */
        $rincian = $data['Rincian'] ?? null;

        $event = $this->layanan->catat(
            $sesi,
            JenisEventDemo::from((string) $data['Jenis']),
            $modul,
            $rincian,
        );

        return response()->json(['EventDemoId' => $event->Id], 201);
    }

    public function selesai(Request $request, SesiDemo $sesi): JsonResponse
    {
        $this->pastikanMilikPengunjung($request, $sesi);

        $this->layanan->selesaikan($sesi);

        return response()->json(['Status' => $sesi->Status->value]);
    }

    /** Sesi demo dikenali dari pengenal pengunjungnya, bukan dari id yang kebetulan ditebak. */
    private function pastikanMilikPengunjung(Request $request, SesiDemo $sesi): void
    {
        $pengenal = $request->attributes->get('pengenalPengunjung');

        if (! is_string($pengenal) || $pengenal !== $sesi->PengenalPengunjung) {
            throw new AksesDitolak('Sesi demo itu bukan milik pengunjung ini.');
        }
    }

    /** Sesi kunjungan terakhir pengunjung ini; itulah yang menautkan demo ke attribution-nya. */
    private function sesiPengunjung(string $pengenal): ?string
    {
        $id = SesiPengunjung::query()
            ->where('PengenalPengunjung', $pengenal)
            ->orderByDesc('DimulaiPada')
            ->value('Id');

        return is_string($id) ? $id : null;
    }
}
