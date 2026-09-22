<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\JenisPermintaanData;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AktivitasProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DaftarSupresi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontakProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanFormulir;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PermintaanDataProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Anonimisasi menyisakan baris tanpa identitas, penghapusan membuangnya; keduanya menyisakan sidik alamat di daftar supresi (MARKETING.md 27). */
final class ProsesPermintaanData
{
    public const NAMA_ANONIM = 'Prospek Dianonimkan';

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    public function jalankan(PermintaanDataProspek $permintaan): PermintaanDataProspek
    {
        if ($permintaan->DiprosesPada !== null) {
            throw new AturanBisnisDilanggar('Permintaan ini sudah diproses.');
        }

        return $this->transaksi->jalankan(function () use ($permintaan): PermintaanDataProspek {
            $prospek = $permintaan->prospek ?? $this->cariProspek((string) $permintaan->Email);

            if ($prospek !== null) {
                $this->anonimkan($prospek);
            }

            if ($permintaan->Jenis === JenisPermintaanData::Penghapusan) {
                $this->hapus($permintaan, $prospek);
            }

            $permintaan->DiprosesPada = CarbonImmutable::now();
            $permintaan->save();

            $this->audit->catat(
                'PermintaanDataProspek.Diproses',
                'PermintaanDataProspek',
                $permintaan->Id,
                dataSesudah: ['Jenis' => $permintaan->Jenis->value, 'EmailHash' => $permintaan->EmailHash],
            );

            return $permintaan;
        });
    }

    /** Identitas dilepas dari barisnya; angka, tahap, dan attribution-nya tetap. */
    private function anonimkan(Prospek $prospek): void
    {
        KontakProspek::query()->where('ProspekId', $prospek->Id)->delete();

        AktivitasProspek::query()->where('ProspekId', $prospek->Id)->update([
            'Judul' => self::NAMA_ANONIM,
            'Isi' => null,
        ]);

        PengirimanFormulir::query()->where('ProspekId', $prospek->Id)->update([
            'Data' => '[]',
            'AlamatIp' => null,
            'AgenPengguna' => null,
        ]);

        PengirimanEmailPemasaran::query()->where('ProspekId', $prospek->Id)->update([
            'Email' => '',
            'Subjek' => '',
        ]);

        $prospek->Nama = self::NAMA_ANONIM;
        $prospek->Email = null;
        $prospek->Telepon = null;
        $prospek->WhatsApp = null;
        $prospek->Jabatan = null;
        $prospek->Catatan = null;
        $prospek->save();
    }

    /** Penghapusan membuang barisnya sekaligus alamat yang masih tersimpan di tempat lain. */
    private function hapus(PermintaanDataProspek $permintaan, ?Prospek $prospek): void
    {
        $hash = (string) $permintaan->EmailHash;
        $email = (string) $permintaan->Email;

        // Konsen bersifat hanya-tambah agar tidak dapat disunting; penghapusan yang sah adalah pengecualian yang disengaja.
        DB::table('KonsenPemasaran')->where('Email', $email)->delete();

        DaftarSupresi::query()->where('EmailHash', $hash)->update(['Email' => null]);

        $prospek?->delete();

        $permintaan->ProspekId = null;
        $permintaan->Email = null;
    }

    private function cariProspek(string $email): ?Prospek
    {
        if ($email === '') {
            return null;
        }

        return Prospek::query()->where('Email', $email)->first();
    }

    /** @return list<PermintaanDataProspek> */
    public function menunggu(int $batas = 100): array
    {
        return array_values(PermintaanDataProspek::query()
            ->whereNull('DiprosesPada')
            ->orderBy('DimintaPada')
            ->limit($batas)
            ->get()
            ->all());
    }
}
