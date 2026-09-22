<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\SumberKonsen;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DaftarSupresi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KonsenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Carbon\CarbonImmutable;

/** Consent hanya-tambah: pencabutan adalah baris baru, bukan suntingan, agar riwayatnya tetap jadi bukti (MARKETING.md 27). */
final class LayananKonsen
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
        private readonly LayananAudit $audit,
    ) {}

    public function catat(
        string $email,
        bool $diberikan,
        SumberKonsen $sumber,
        ?Prospek $prospek = null,
        ?string $alamatIp = null,
        ?string $agenPengguna = null,
    ): KonsenPemasaran {
        return KonsenPemasaran::create([
            'ProspekId' => $prospek?->Id,
            'Email' => $this->normalkan($email),
            'Diberikan' => $diberikan,
            'Sumber' => $sumber,
            'VersiKebijakan' => (string) $this->konfigurasi->ambil(
                KatalogKonfigurasiPemasaran::CONSENT_VERSI_KEBIJAKAN,
            ),
            'AlamatIp' => $alamatIp,
            'AgenPengguna' => $agenPengguna,
            'DicatatPada' => CarbonImmutable::now(),
        ]);
    }

    /** Pencabutan menulis konsen negatif sekaligus memasukkan alamatnya ke daftar supresi. */
    public function cabut(
        string $email,
        AlasanSupresi $alasan = AlasanSupresi::Unsubscribe,
        ?Prospek $prospek = null,
        ?string $catatan = null,
    ): void {
        $this->transaksi->jalankan(function () use ($email, $alasan, $prospek, $catatan): void {
            $this->catat($email, false, SumberKonsen::Unsubscribe, $prospek);
            $this->supresi($email, $alasan, $catatan);
        });
    }

    public function supresi(string $email, AlasanSupresi $alasan, ?string $catatan = null): DaftarSupresi
    {
        $bersih = $this->normalkan($email);

        $baris = DaftarSupresi::query()->firstOrCreate(
            ['EmailHash' => $this->sidik($bersih)],
            [
                'Email' => $bersih,
                'Alasan' => $alasan,
                'Catatan' => $catatan,
                'DitambahkanPada' => CarbonImmutable::now(),
            ],
        );

        $this->audit->catat('DaftarSupresi.Ditambahkan', 'DaftarSupresi', $baris->Id, dataSesudah: [
            'Email' => $bersih,
            'Alasan' => $alasan->value,
        ]);

        return $baris;
    }

    public function disupresi(string $email): bool
    {
        return DaftarSupresi::query()->where('EmailHash', $this->sidik($this->normalkan($email)))->exists();
    }

    /** Sidik satu arah: daftar supresi tetap dapat menolak alamat yang datanya sudah dihapus. */
    public function sidik(string $email): string
    {
        return hash('sha256', $this->normalkan($email));
    }

    /** Konsen terakhir yang tercatat menang; tanpa catatan sama sekali, jawabannya tidak. */
    public function disetujui(string $email): bool
    {
        $terakhir = KonsenPemasaran::query()
            ->where('Email', $this->normalkan($email))
            ->orderByDesc('DicatatPada')
            ->first();

        return $terakhir?->Diberikan === true;
    }

    /** Satu-satunya pertanyaan yang boleh ditanyakan sebelum mengirim pesan pemasaran. */
    public function bolehDikirimi(string $email): bool
    {
        return $this->disetujui($email) && ! $this->disupresi($email);
    }

    private function normalkan(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
