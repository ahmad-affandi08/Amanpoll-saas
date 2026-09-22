<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Services;

use App\Domain\Kepatuhan\Domain\Contracts\AdapterSinkronisasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** Adapter bawaan untuk sistem eksternal ber-REST. */
final class AdapterSinkronisasiRest implements AdapterSinkronisasi
{
    public function __construct(private readonly PenyusunHeaderIntegrasi $header) {}

    /**
     * @return array{berhasil: int<0, max>, gagal: int<0, max>}
     */
    public function tarik(IntegrasiEksternal $integrasi, string $jenisProses): array
    {
        return $this->hitung($this->permintaan($integrasi)->get($this->tujuan($integrasi, $jenisProses)));
    }

    /**
     * @return array{berhasil: int<0, max>, gagal: int<0, max>}
     */
    public function dorong(IntegrasiEksternal $integrasi, string $jenisProses): array
    {
        return $this->hitung($this->permintaan($integrasi)->post($this->tujuan($integrasi, $jenisProses)));
    }

    private function permintaan(IntegrasiEksternal $integrasi): PendingRequest
    {
        return Http::timeout(30)->withHeaders($this->header->untuk($integrasi));
    }

    private function tujuan(IntegrasiEksternal $integrasi, string $jenisProses): string
    {
        if (empty($integrasi->UrlDasar)) {
            throw new AturanBisnisDilanggar('URL dasar integrasi belum diisi.');
        }

        return rtrim((string) $integrasi->UrlDasar, '/').'/'.ltrim(strtolower($jenisProses), '/');
    }

    /**
     * Respons gagal dilempar supaya job sinkronisasi mencobanya ulang.
     *
     * @return array{berhasil: int<0, max>, gagal: int<0, max>}
     */
    private function hitung(Response $respons): array
    {
        if (! $respons->successful()) {
            throw new AturanBisnisDilanggar("Sistem tujuan membalas status {$respons->status()}.");
        }

        $isi = $respons->json();
        $baris = match (true) {
            is_array($isi) && array_is_list($isi) => $isi,
            is_array($isi) && is_array($isi['data'] ?? null) => $isi['data'],
            default => [],
        };

        $gagal = is_array($isi) && is_array($isi['gagal'] ?? null) ? count($isi['gagal']) : 0;

        return ['berhasil' => count($baris), 'gagal' => $gagal];
    }
}
