<?php

declare(strict_types=1);

namespace App\Core\Penomoran;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Penomoran\Services\LayananKodeOtomatis;

/**
 * Mengisi kode data induk saat kosong, sehingga pengguna tidak perlu mengetiknya.
 *
 * Dipasang sesudah MilikOrganisasi supaya OrganisasiId sudah terisi ketika
 * penghitungnya dicari. Kode yang diisi sendiri oleh pengguna tidak disentuh.
 */
trait PunyaKodeOtomatis
{
    protected static function bootPunyaKodeOtomatis(): void
    {
        static::creating(function (self $model): void {
            $kolom = $model->kolomKode();

            if (filled($model->{$kolom})) {
                return;
            }

            $model->{$kolom} = app(LayananKodeOtomatis::class)->berikutnya(
                entitas: $model->getTable(),
                awalan: $model->awalanKode(),
                organisasiId: $model->organisasiUntukKode(),
                sudahDipakai: static fn (string $kode): bool => $model->newQuery()
                    ->withoutGlobalScopes()
                    ->where($kolom, $kode)
                    ->where($model->lingkupKode())
                    ->exists(),
            );
        });
    }

    /** Awalan kode entitas ini, mis. GDG untuk Gudang. */
    abstract public function awalanKode(): string;

    /** Kolom yang menyimpan kode; sebagian entitas memakai nama lain. */
    public function kolomKode(): string
    {
        return 'Kode';
    }

    /**
     * Kolom pembatas keunikan kode selain kolom kodenya sendiri.
     *
     * @return array<string, mixed>
     */
    public function lingkupKode(): array
    {
        $organisasiId = $this->organisasiUntukKode();

        return $organisasiId === null ? [] : ['OrganisasiId' => $organisasiId];
    }

    /** Null untuk entitas milik platform yang tabelnya tidak bertenant. */
    public function organisasiUntukKode(): ?string
    {
        if (! in_array('OrganisasiId', $this->getFillable(), true)) {
            return null;
        }

        if (filled($this->OrganisasiId)) {
            return (string) $this->OrganisasiId;
        }

        $konteks = app(KonteksOrganisasi::class);

        return $konteks->ada() ? $konteks->wajibId() : null;
    }
}
