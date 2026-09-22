<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\OrganisasiProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/** Membuat atau memperbarui prospek dari sumber mana pun (MARKETING.md 5.1). */
final class CatatProspek
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PerekamEventPemasaran $event,
        private readonly PindahkanTahapProspek $pindahkanTahap,
    ) {}

    /** @param array<string, mixed> $data */
    public function jalankan(array $data, SumberProspek $sumber, ?string $pengenalPengunjung = null): Prospek
    {
        return $this->transaksi->jalankan(function () use ($data, $sumber, $pengenalPengunjung): Prospek {
            $prospek = $this->cariYangSudahAda($data, $pengenalPengunjung);
            $baru = $prospek === null;

            $atribut = [
                'Nama' => $data['Nama'],
                'Email' => $data['Email'] ?? null,
                'Telepon' => $data['Telepon'] ?? null,
                'WhatsApp' => $data['WhatsApp'] ?? null,
                'Jabatan' => $data['Jabatan'] ?? null,
                'Catatan' => $data['Catatan'] ?? null,
                'AktivitasTerakhirPada' => now(),
            ];

            if ($baru) {
                $prospek = Prospek::create([
                    ...$atribut,
                    'Sumber' => $sumber->value,
                    'PengenalPengunjung' => $pengenalPengunjung,
                    'OrganisasiProspekId' => $this->perusahaanUntuk($data),
                    'KampanyeId' => $this->kampanyeDariAttribution($pengenalPengunjung),
                    'TahapPipelineId' => $this->tahapAwal()?->Id,
                    'Skor' => 0,
                ]);
            } else {
                // Prospek yang kembali mengisi formulir tidak diganti sumbernya.
                $prospek->fill(array_filter($atribut, fn (mixed $nilai): bool => $nilai !== null));
                $prospek->PengenalPengunjung ??= $pengenalPengunjung;
                $prospek->OrganisasiProspekId ??= $this->perusahaanUntuk($data);
                $prospek->save();
            }

            if ($baru) {
                $this->pindahkanTahap->catatTahapAwal($prospek);
            }

            $this->event->catat(
                KatalogPeristiwaPemasaran::FORMULIR_DIKIRIM,
                pengenalPengunjung: $prospek->PengenalPengunjung,
                dataTambahan: ['ProspekId' => $prospek->Id, 'Sumber' => $sumber->value, 'Baru' => $baru],
            );

            return $prospek;
        });
    }

    /**
     * Penggabungan hanya memakai sinyal kuat: alamat email yang sama, atau
     * pengenal pengunjung yang sama. MARKETING.md 14 melarang merge berdasarkan
     * sinyal lemah, dan menggabungkan dua orang berbeda jauh lebih mahal
     * daripada menyimpan satu prospek ganda.
     *
     * @param  array<string, mixed>  $data
     */
    private function cariYangSudahAda(array $data, ?string $pengenalPengunjung): ?Prospek
    {
        $email = isset($data['Email']) && is_string($data['Email']) ? trim($data['Email']) : '';

        if ($email !== '') {
            $prospek = Prospek::query()->where('Email', $email)->first();

            if ($prospek !== null) {
                return $prospek;
            }
        }

        if ($pengenalPengunjung === null) {
            return null;
        }

        return Prospek::query()->where('PengenalPengunjung', $pengenalPengunjung)->first();
    }

    /** @param array<string, mixed> $data */
    private function perusahaanUntuk(array $data): ?string
    {
        $nama = isset($data['Perusahaan']) && is_string($data['Perusahaan']) ? trim($data['Perusahaan']) : '';

        if ($nama === '') {
            return null;
        }

        return OrganisasiProspek::query()->firstOrCreate(
            ['Nama' => $nama],
            [
                'Industri' => $data['Industri'] ?? null,
                'JumlahLokasi' => $data['JumlahLokasi'] ?? null,
                'EstimasiAset' => $data['EstimasiAset'] ?? null,
                'EstimasiTeknisi' => $data['EstimasiTeknisi'] ?? null,
                'Kota' => $data['Kota'] ?? null,
                'Negara' => $data['Negara'] ?? null,
            ],
        )->Id;
    }

    /** Kampanye prospek diambil dari first touch, bukan last touch. */
    private function kampanyeDariAttribution(?string $pengenalPengunjung): ?string
    {
        if ($pengenalPengunjung === null) {
            return null;
        }

        $attribution = AttributionPemasaran::query()
            ->where('PengenalPengunjung', $pengenalPengunjung)
            ->first();

        if ($attribution === null) {
            return null;
        }

        if ($attribution->KampanyeIdPertama !== null) {
            return $attribution->KampanyeIdPertama;
        }

        return $attribution->KampanyePertama === null
            ? null
            : Kampanye::query()->where('Kode', $attribution->KampanyePertama)->value('Id');
    }

    private function tahapAwal(): ?TahapPipeline
    {
        return TahapPipeline::query()->where('Kode', KatalogTahapPipeline::BARU)->first();
    }
}
