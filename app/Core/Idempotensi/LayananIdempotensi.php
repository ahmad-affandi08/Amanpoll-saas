<?php

declare(strict_types=1);

namespace App\Core\Idempotensi;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KunciIdempotensi;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** Idempotensi permintaan tulis: kunci dicatat per organisasi + rute + kunci klien, disertai sidik jari muatan. */
final class LayananIdempotensi
{
    public const HEADER = 'Idempotency-Key';

    /** Respons yang lebih besar dari ini tidak disimpan; permintaan ulang diproses lagi. */
    private const BATAS_SIMPAN_RESPONS = 64_000;

    public function __construct(private readonly int $ttlJam = 24) {}

    public function sidikJari(Request $request): string
    {
        return hash('sha256', $request->getContent());
    }

    public function rute(Request $request): string
    {
        return $request->method().' '.($request->route()?->uri() ?? $request->path());
    }

    /**
     * Mendaftarkan kunci. Mengembalikan baris yang sudah ada bila permintaan ini
     * pengulangan, atau null bila ini permintaan pertama yang harus diproses.
     *
     * @throws KonflikIdempotensi bila kunci dipakai ulang dengan muatan berbeda
     */
    public function daftarkan(?string $organisasiId, string $kunci, string $rute, string $sidikJari): ?KunciIdempotensi
    {
        $adaSebelumnya = $this->cari($organisasiId, $kunci, $rute);

        if ($adaSebelumnya !== null) {
            if ($adaSebelumnya->KadaluarsaPada->isPast()) {
                $adaSebelumnya->delete();
            } else {
                $this->pastikanSidikJariSama($adaSebelumnya, $sidikJari);

                return $adaSebelumnya;
            }
        }

        try {
            KunciIdempotensi::create([
                'OrganisasiId' => $organisasiId,
                'Kunci' => $kunci,
                'Rute' => $rute,
                'HashPermintaan' => $sidikJari,
                'KadaluarsaPada' => now()->addHours($this->ttlJam),
            ]);

            return null;
        } catch (QueryException $e) {
            // Dua permintaan identik yang tiba bersamaan.
            if (! $this->pelanggaranUnik($e)) {
                throw $e;
            }

            $baris = $this->cari($organisasiId, $kunci, $rute);
            if ($baris === null) {
                throw $e;
            }
            $this->pastikanSidikJariSama($baris, $sidikJari);

            return $baris;
        }
    }

    public function simpanRespons(?string $organisasiId, string $kunci, string $rute, Response $respons): void
    {
        $baris = $this->cari($organisasiId, $kunci, $rute);
        if ($baris === null) {
            return;
        }

        $isi = (string) $respons->getContent();

        // Respons gagal tidak dikunci supaya klien boleh mencoba lagi dengan kunci sama.
        if ($respons->getStatusCode() >= 400) {
            $baris->delete();

            return;
        }

        if (strlen($isi) > self::BATAS_SIMPAN_RESPONS) {
            $baris->delete();

            return;
        }

        /** @var int<0, 399> $status */
        $status = $respons->getStatusCode();
        $baris->StatusHttp = $status;
        $baris->Respons = $isi;
        $baris->save();
    }

    public function bersihkanKedaluwarsa(): int
    {
        return (int) DB::table('KunciIdempotensi')->where('KadaluarsaPada', '<', now())->delete();
    }

    private function cari(?string $organisasiId, string $kunci, string $rute): ?KunciIdempotensi
    {
        // Query tanpa scope organisasi.
        return KunciIdempotensi::query()
            ->withoutGlobalScopes()
            ->when($organisasiId === null, fn ($query) => $query->whereNull('OrganisasiId'))
            ->when($organisasiId !== null, fn ($query) => $query->where('OrganisasiId', $organisasiId))
            ->where('Kunci', $kunci)
            ->where('Rute', $rute)
            ->first();
    }

    private function pastikanSidikJariSama(KunciIdempotensi $baris, string $sidikJari): void
    {
        if ($baris->HashPermintaan !== null && ! hash_equals($baris->HashPermintaan, $sidikJari)) {
            throw new KonflikIdempotensi(
                'Kunci idempotensi ini sudah dipakai untuk permintaan dengan isi berbeda.'
            );
        }
    }

    private function pelanggaranUnik(QueryException $e): bool
    {
        return in_array((string) ($e->errorInfo[1] ?? ''), ['1062'], true)
            || str_contains(strtolower($e->getMessage()), 'unique');
    }
}
