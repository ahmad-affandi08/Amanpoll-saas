<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kepatuhan\Domain\Enums\StatusKepatuhanAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\StandarKepatuhan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

final class KelolaKepatuhanAset
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * Menugaskan seluruh persyaratan sebuah standar ke satu aset. Persyaratan
     * yang sudah pernah ditugaskan dilewati supaya penugasan ulang aman.
     *
     * @return array{ditambahkan: int, dilewati: int}
     */
    public function tugaskanStandar(Aset $aset, StandarKepatuhan $standar): array
    {
        if (! $standar->Aktif) {
            throw new AturanBisnisDilanggar('Standar nonaktif tidak dapat ditugaskan ke aset.');
        }

        $persyaratan = $standar->persyaratan()->get();
        if ($persyaratan->isEmpty()) {
            throw new AturanBisnisDilanggar('Standar belum memiliki persyaratan untuk ditugaskan.');
        }

        return $this->transaksi->jalankan(function () use ($aset, $standar, $persyaratan): array {
            $ditambahkan = 0;
            $dilewati = 0;

            foreach ($persyaratan as $satuPersyaratan) {
                $sudahAda = KepatuhanAset::query()
                    ->where('AsetId', $aset->Id)
                    ->where('PersyaratanKepatuhanId', $satuPersyaratan->Id)
                    ->exists();

                if ($sudahAda) {
                    $dilewati++;

                    continue;
                }

                KepatuhanAset::create([
                    'OrganisasiId' => $aset->OrganisasiId,
                    'AsetId' => $aset->Id,
                    'PersyaratanKepatuhanId' => $satuPersyaratan->Id,
                    'Status' => StatusKepatuhanAset::BelumDiperiksa->value,
                ]);
                $ditambahkan++;
            }

            $this->audit->catat('KepatuhanAset.StandarDitugaskan', 'Aset', $aset->Id, dataSesudah: [
                'StandarKepatuhanId' => $standar->Id,
                'Ditambahkan' => $ditambahkan,
                'Dilewati' => $dilewati,
            ]);

            return ['ditambahkan' => $ditambahkan, 'dilewati' => $dilewati];
        });
    }

    /**
     * Mencatat hasil pemeriksaan. Masa berlaku dihitung dari interval persyaratan
     * bila ada, sehingga tanggal kedaluwarsa tidak perlu diisi manual.
     *
     * @param  array<string, mixed>  $data
     */
    public function catatPemeriksaan(KepatuhanAset $kepatuhan, array $data, string $penggunaId): KepatuhanAset
    {
        $status = (string) $data['Status'];
        if (! in_array($status, [StatusKepatuhanAset::Patuh->value, StatusKepatuhanAset::TidakPatuh->value], true)) {
            throw new AturanBisnisDilanggar('Hasil pemeriksaan hanya boleh Patuh atau TidakPatuh.');
        }

        $tanggal = CarbonImmutable::parse((string) ($data['TanggalPemeriksaan'] ?? CarbonImmutable::today()->toDateString()));
        if ($tanggal->isFuture()) {
            throw new AturanBisnisDilanggar('Tanggal pemeriksaan tidak boleh berada di masa depan.');
        }

        return $this->transaksi->jalankan(function () use ($kepatuhan, $data, $status, $tanggal, $penggunaId): KepatuhanAset {
            $sebelum = $kepatuhan->toArray();
            $interval = $kepatuhan->persyaratanKepatuhan()->value('IntervalHari');

            $kepatuhan->Status = $status;
            $kepatuhan->TanggalPemeriksaan = $tanggal->toMutable();
            $kepatuhan->BerlakuSampai = $this->hitungBerlakuSampai($data, $tanggal, $interval === null ? null : (int) $interval);
            $kepatuhan->Catatan = $data['Catatan'] ?? null;
            $kepatuhan->DiperiksaOleh = $penggunaId;
            $kepatuhan->save();

            $this->audit->catat('KepatuhanAset.Diperiksa', 'KepatuhanAset', $kepatuhan->Id, dataSebelum: $sebelum, dataSesudah: $kepatuhan->toArray());

            return $kepatuhan->refresh();
        });
    }

    public function lepaskan(KepatuhanAset $kepatuhan): void
    {
        $id = $kepatuhan->Id;
        $sebelum = $kepatuhan->toArray();
        $kepatuhan->delete();
        $this->audit->catat('KepatuhanAset.Dilepaskan', 'KepatuhanAset', $id, dataSebelum: $sebelum);
    }

    /** @param array<string, mixed> $data */
    private function hitungBerlakuSampai(array $data, CarbonImmutable $tanggal, ?int $interval): ?Carbon
    {
        if (! empty($data['BerlakuSampai'])) {
            $manual = CarbonImmutable::parse((string) $data['BerlakuSampai']);
            if ($manual->lt($tanggal)) {
                throw new AturanBisnisDilanggar('Masa berlaku tidak boleh mendahului tanggal pemeriksaan.');
            }

            return $manual->toMutable();
        }

        return $interval !== null && $interval > 0 ? $tanggal->addDays($interval)->toMutable() : null;
    }
}
