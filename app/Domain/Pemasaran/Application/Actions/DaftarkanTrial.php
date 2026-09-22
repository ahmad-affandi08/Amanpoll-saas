<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Application\Actions\KelolaLangganan;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Pemasaran\Application\Services\PembacaKonfigurasiTrial;
use App\Domain\Pemasaran\Application\Services\PenjagaKartuTrial;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\ValueObjects\KonfigurasiTrial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Str;

/**
 * Pendaftaran trial mandiri dari host dashboard (MARKETING.md 12, 34.1).
 *
 * Satu transaksi menghasilkan empat hal yang tidak boleh ada setengah-setengah:
 * organisasi, pengguna pemiliknya, langganan uji coba, dan trialnya. Workspace
 * tanpa pemilik, atau pemilik tanpa langganan, adalah tenant yang tidak dapat
 * dipakai dan tidak dapat ditagih.
 */
final class DaftarkanTrial
{
    private const KODE_PERAN_PEMILIK = 'PEMILIK';

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly KonteksOrganisasi $konteks,
        private readonly PembacaKonfigurasiTrial $konfigurasi,
        private readonly PenjagaKartuTrial $penjagaKartu,
        private readonly KelolaLangganan $kelolaLangganan,
        private readonly CatatProspek $catatProspek,
        private readonly MulaiTrial $mulaiTrial,
    ) {}

    /** @param array<string, mixed> $data */
    public function jalankan(array $data, ?string $pengenalPengunjung = null): Trial
    {
        $tokenKartu = isset($data['TokenKartu']) && is_string($data['TokenKartu']) ? $data['TokenKartu'] : null;
        $this->penjagaKartu->pastikanBoleh($tokenKartu);

        $setelan = $this->konfigurasi->berlaku();

        if ($setelan->paketId === null) {
            throw new AturanBisnisDilanggar('Belum ada paket aktif yang dapat dipakai untuk trial.');
        }

        // Tenant baru ditulis tanpa konteks tenant mana pun; ScopeOrganisasi menolak yang sebaliknya.
        $konteksSemula = $this->konteks->id();
        $this->konteks->bersihkan();

        try {
            return $this->transaksi->jalankan(
                fn (): Trial => $this->tulis($data, $pengenalPengunjung, $setelan),
            );
        } finally {
            $this->konteks->tetapkan($konteksSemula);
        }
    }

    /** @param array<string, mixed> $data */
    private function tulis(array $data, ?string $pengenalPengunjung, KonfigurasiTrial $setelan): Trial
    {
        $organisasi = $this->buatOrganisasi($data);
        $this->buatPemilik($organisasi, $data);

        $langganan = $this->kelolaLangganan->mulai($organisasi->Id, [
            'PaketLanggananId' => $setelan->paketId,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'DenganUjiCoba' => true,
        ]);

        $prospek = $this->catatProspek->jalankan(
            [
                'Nama' => $data['Nama'],
                'Email' => $data['Email'],
                'Telepon' => $data['Telepon'] ?? null,
                'Perusahaan' => $data['NamaOrganisasi'],
            ],
            SumberProspek::Trial,
            $pengenalPengunjung,
        );

        return $this->mulaiTrial->jalankan($organisasi->Id, $prospek, $langganan->Id);
    }

    /** @param array<string, mixed> $data */
    private function buatOrganisasi(array $data): Organisasi
    {
        return Organisasi::create([
            'Kode' => $this->kodeUnik((string) $data['NamaOrganisasi']),
            'Nama' => $data['NamaOrganisasi'],
            'Status' => 'Aktif',
        ]);
    }

    /** @param array<string, mixed> $data */
    private function buatPemilik(Organisasi $organisasi, array $data): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => $data['Nama'],
            'Email' => $data['Email'],
            'KataSandi' => $data['KataSandi'],
            'Status' => 'Aktif',
        ]);

        PenggunaPeran::create([
            'OrganisasiId' => $organisasi->Id,
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $this->peranPemilik($organisasi)->Id,
        ]);

        return $pengguna;
    }

    /** Pemilik workspace baru memegang seluruh izin: tidak ada orang lain yang dapat memberinya. */
    private function peranPemilik(Organisasi $organisasi): Peran
    {
        $peran = Peran::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => self::KODE_PERAN_PEMILIK,
            'Nama' => 'Pemilik',
            'Keterangan' => 'Dibuat otomatis saat pendaftaran trial.',
            'BawaanSistem' => true,
        ]);

        foreach (Izin::query()->pluck('Id') as $izinId) {
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izinId]);
        }

        return $peran;
    }

    /** Kode organisasi dipakai saat masuk, jadi harus terbaca manusia sekaligus unik. */
    private function kodeUnik(string $nama): string
    {
        $dasar = Str::upper(Str::substr(Str::slug($nama, ''), 0, 8));
        $dasar = $dasar === '' ? 'ORG' : $dasar;

        do {
            $kode = $dasar.'-'.Str::upper(Str::random(4));
        } while (Organisasi::query()->where('Kode', $kode)->exists());

        return $kode;
    }
}
