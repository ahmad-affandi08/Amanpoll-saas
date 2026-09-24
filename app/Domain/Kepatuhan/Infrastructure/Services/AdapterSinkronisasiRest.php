<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Services;

use App\Domain\Kepatuhan\Domain\Contracts\AdapterSinkronisasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Keamanan\PenjagaUrlKeluar;
use App\Shared\Infrastructure\Keamanan\UrlKeluarSah;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/** Adapter bawaan untuk sistem eksternal ber-REST. */
final class AdapterSinkronisasiRest implements AdapterSinkronisasi
{
    public function __construct(
        private readonly PenyusunHeaderIntegrasi $header,
        private readonly PenjagaUrlKeluar $penjaga,
    ) {}

    /**
     * @return array{berhasil: int<0, max>, gagal: int<0, max>}
     */
    public function tarik(IntegrasiEksternal $integrasi, string $jenisProses): array
    {
        $tujuan = $this->tujuan($integrasi, $jenisProses);

        return $this->hitung($this->permintaan($integrasi, $tujuan)->get($tujuan->url));
    }

    /**
     * @return array{berhasil: int<0, max>, gagal: int<0, max>}
     */
    public function dorong(IntegrasiEksternal $integrasi, string $jenisProses): array
    {
        $tujuan = $this->tujuan($integrasi, $jenisProses);

        return $this->hitung($this->permintaan($integrasi, $tujuan)->post($tujuan->url));
    }

    private function permintaan(IntegrasiEksternal $integrasi, UrlKeluarSah $tujuan): PendingRequest
    {
        return $this->penjaga->klien($tujuan)->timeout(30)->withHeaders($this->header->untuk($integrasi));
    }

    /** URL akhir diperiksa penjaga jaringan tepat sebelum dipanggil, bukan hanya saat disimpan. */
    private function tujuan(IntegrasiEksternal $integrasi, string $jenisProses): UrlKeluarSah
    {
        if (empty($integrasi->UrlDasar)) {
            throw new AturanBisnisDilanggar('URL dasar integrasi belum diisi.');
        }

        // Tiap segmen dikodekan supaya spasi, "?", atau "#" pada nama proses tidak mengubah arti URL.
        $jalur = implode('/', array_map(rawurlencode(...), explode('/', ltrim(strtolower($jenisProses), '/'))));

        return $this->penjaga->periksa(rtrim((string) $integrasi->UrlDasar, '/').'/'.$jalur);
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
