<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
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
        string $kontak,
        bool $diberikan,
        SumberKonsen $sumber,
        ?Prospek $prospek = null,
        ?string $alamatIp = null,
        ?string $agenPengguna = null,
        KanalPesan $kanal = KanalPesan::Email,
    ): KonsenPemasaran {
        return KonsenPemasaran::create([
            'ProspekId' => $prospek?->Id,
            'Kanal' => $kanal,
            'Kontak' => $kanal->normalkan($kontak),
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

    /** Pencabutan menulis konsen negatif sekaligus memasukkan kontaknya ke daftar supresi. */
    public function cabut(
        string $kontak,
        AlasanSupresi $alasan = AlasanSupresi::Unsubscribe,
        ?Prospek $prospek = null,
        ?string $catatan = null,
        KanalPesan $kanal = KanalPesan::Email,
    ): void {
        $this->transaksi->jalankan(function () use ($kontak, $alasan, $prospek, $catatan, $kanal): void {
            $this->catat($kontak, false, SumberKonsen::Unsubscribe, $prospek, kanal: $kanal);
            $this->supresi($kontak, $alasan, $catatan, $kanal);
        });
    }

    public function supresi(
        string $kontak,
        AlasanSupresi $alasan,
        ?string $catatan = null,
        KanalPesan $kanal = KanalPesan::Email,
    ): DaftarSupresi {
        $bersih = $kanal->normalkan($kontak);

        $baris = DaftarSupresi::query()->firstOrCreate(
            ['Kanal' => $kanal, 'KontakHash' => $this->sidik($bersih, $kanal)],
            [
                'Kontak' => $bersih,
                'Alasan' => $alasan,
                'Catatan' => $catatan,
                'DitambahkanPada' => CarbonImmutable::now(),
            ],
        );

        $this->audit->catat('DaftarSupresi.Ditambahkan', 'DaftarSupresi', $baris->Id, dataSesudah: [
            'Kanal' => $kanal->value,
            'Kontak' => $bersih,
            'Alasan' => $alasan->value,
        ]);

        return $baris;
    }

    public function disupresi(string $kontak, KanalPesan $kanal = KanalPesan::Email): bool
    {
        return DaftarSupresi::query()
            ->where('Kanal', $kanal->value)
            ->where('KontakHash', $this->sidik($kanal->normalkan($kontak), $kanal))
            ->exists();
    }

    /** Sidik satu arah: daftar supresi tetap dapat menolak kontak yang datanya sudah dihapus. */
    public function sidik(string $kontak, KanalPesan $kanal = KanalPesan::Email): string
    {
        return hash('sha256', $kanal->normalkan($kontak));
    }

    /** Konsen terakhir yang tercatat menang; tanpa catatan sama sekali, jawabannya tidak. */
    public function disetujui(string $kontak, KanalPesan $kanal = KanalPesan::Email): bool
    {
        $terakhir = KonsenPemasaran::query()
            ->where('Kanal', $kanal->value)
            ->where('Kontak', $kanal->normalkan($kontak))
            ->orderByDesc('DicatatPada')
            ->first();

        return $terakhir?->Diberikan === true;
    }

    /** Satu-satunya pertanyaan yang boleh ditanyakan sebelum mengirim pesan pemasaran. */
    public function bolehDikirimi(string $kontak, KanalPesan $kanal = KanalPesan::Email): bool
    {
        return $this->disetujui($kontak, $kanal) && ! $this->disupresi($kontak, $kanal);
    }
}
