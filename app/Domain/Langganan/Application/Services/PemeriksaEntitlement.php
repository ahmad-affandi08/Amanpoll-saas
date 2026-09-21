<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Services;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Domain\ValueObjects\Entitlement;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat entitlement dijawab (22.05).
 *
 * Seluruh penegakan — gerbang rute, penjaga batas, dan prop yang dikirim ke
 * UI — memanggil kelas ini, sehingga tidak mungkin ada jalur yang memakai
 * aturan berbeda. Inilah yang membuat Gate 22 dapat dipenuhi: menembak API
 * langsung tidak melewati pemeriksaan mana pun yang hanya hidup di UI, sebab
 * di UI tidak ada aturan yang tidak ada di sini.
 */
final class PemeriksaEntitlement
{
    private const MENIT_CACHE = 5;

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly KonteksOrganisasi $konteks,
        private readonly LayananLangganan $layananLangganan,
        private readonly LayananKebijakanTenggang $kebijakan,
    ) {}

    public function untukOrganisasi(string $organisasiId): Entitlement
    {
        /** @var array<string, mixed>|null $tersimpan */
        $tersimpan = $this->cache->get($this->kunciCache($organisasiId));

        if (is_array($tersimpan)) {
            return $this->dariArray($tersimpan);
        }

        $entitlement = $this->hitung($organisasiId);
        $this->cache->put(
            $this->kunciCache($organisasiId),
            $entitlement->keArray(),
            now()->addMinutes(self::MENIT_CACHE),
        );

        return $entitlement;
    }

    /** Entitlement organisasi yang sedang aktif pada permintaan ini. */
    public function sekarang(): Entitlement
    {
        $organisasiId = $this->konteks->id();

        return $organisasiId === null
            ? Entitlement::tanpaTenant()
            : $this->untukOrganisasi($organisasiId);
    }

    public function bolehFitur(string $kodeFitur, ?string $organisasiId = null): bool
    {
        $entitlement = $organisasiId === null ? $this->sekarang() : $this->untukOrganisasi($organisasiId);

        return $entitlement->bolehFitur($kodeFitur);
    }

    /**
     * Dipanggil setiap kali langganan atau isi paket berubah, supaya kenaikan
     * paket langsung terasa dan penurunan paket tidak tertunda lima menit.
     */
    public function bersihkanCache(string $organisasiId): void
    {
        $this->cache->forget($this->kunciCache($organisasiId));
    }

    /**
     * Perubahan pada sebuah paket menyentuh semua organisasi yang memakainya,
     * jadi cache-nya dibersihkan per organisasi pemakai — bukan dengan
     * mengosongkan seluruh cache aplikasi.
     */
    public function bersihkanCachePaket(string $paketLanggananId): void
    {
        $organisasiId = Langganan::query()
            ->withoutGlobalScopes()
            ->where('PaketLanggananId', $paketLanggananId)
            ->distinct()
            ->pluck('OrganisasiId');

        foreach ($organisasiId as $satu) {
            $this->bersihkanCache((string) $satu);
        }
    }

    private function hitung(string $organisasiId): Entitlement
    {
        $langganan = $this->layananLangganan->untukOrganisasi($organisasiId);
        if ($langganan === null) {
            return $this->ujiCobaAwal($organisasiId);
        }

        $status = $this->layananLangganan->statusEfektif($langganan);
        $paket = $langganan->paketLangganan;

        $fitur = [];
        $batas = [];

        // Katalog menjadi kerangkanya, bukan isi tabel: fitur yang belum pernah
        // ditetapkan pada sebuah paket tetap muncul dengan nilai bawaannya,
        // sehingga menambah fitur baru tidak membuat paket lama menjawab "tidak
        // tahu" dan gerbangnya gagal terbuka atau gagal tertutup.
        foreach (KatalogFitur::semua() as $kode => $definisi) {
            $fitur[$kode] = $definisi->diizinkanBawaan;
            $batas[$kode] = $definisi->batasBawaan;
        }

        if ($paket !== null) {
            foreach ($this->barisPaketFitur((string) $paket->Id) as $baris) {
                $kode = (string) ($baris['Kode'] ?? '');
                if (! KatalogFitur::ada($kode)) {
                    continue;
                }

                $fitur[$kode] = (bool) ($baris['Diizinkan'] ?? false);
                $batas[$kode] = ($baris['BatasNilai'] ?? null) === null ? null : (float) $baris['BatasNilai'];
            }
        }

        return new Entitlement(
            paketId: $paket?->Id,
            namaPaket: $paket?->Nama,
            status: $status,
            fitur: $fitur,
            batas: $batas,
            berakhirPada: $langganan->BerakhirPada?->toDateString(),
            ujiCobaSampai: $this->layananLangganan->ujiCobaMasihBerjalan($langganan)
                ? $langganan->UjiCobaSampai?->toDateString()
                : null,
        );
    }

    /**
     * Organisasi yang belum diberi paket berada dalam uji coba awal, dihitung
     * dari tanggal organisasinya dibuat. Ini membuat tenant baru langsung dapat
     * bekerja, sekaligus memastikan tenant yang terlupakan berhenti sendiri
     * alih-alih menjadi pelanggan gratis selamanya.
     */
    private function ujiCobaAwal(string $organisasiId): Entitlement
    {
        $dibuatPada = DB::table('Organisasi')->where('Id', $organisasiId)->value('DibuatPada');
        if ($dibuatPada === null) {
            return Entitlement::tanpaTenant();
        }

        $sampai = CarbonImmutable::parse((string) $dibuatPada)
            ->startOfDay()
            ->addDays($this->kebijakan->hariUjiCoba());

        $fitur = [];
        foreach (KatalogFitur::kode() as $kode) {
            // Uji coba memperlihatkan seluruh modul; itulah gunanya uji coba.
            $fitur[$kode] = true;
        }

        return Entitlement::ujiCobaAwal(
            $fitur,
            CarbonImmutable::now()->startOfDay()->lessThanOrEqualTo($sampai),
            $sampai->toDateString(),
        );
    }

    /**
     * Baris mentah dikonversi sekali di sini sehingga sisa kelas bekerja dengan
     * array bertipe, bukan properti dinamis.
     *
     * @return list<array<string, mixed>>
     */
    private function barisPaketFitur(string $paketId): array
    {
        $baris = DB::table('PaketFitur as pf')
            ->join('FiturPaket as f', 'f.Id', '=', 'pf.FiturPaketId')
            ->where('pf.PaketLanggananId', $paketId)
            ->get(['f.Kode', 'pf.Diizinkan', 'pf.BatasNilai']);

        $hasil = [];
        foreach ($baris as $satu) {
            $hasil[] = get_object_vars($satu);
        }

        return $hasil;
    }

    /** @param array<string, mixed> $data */
    private function dariArray(array $data): Entitlement
    {
        /** @var array<string, bool> $fitur */
        $fitur = array_map(boolval(...), (array) ($data['Fitur'] ?? []));

        $batas = [];
        foreach ((array) ($data['Batas'] ?? []) as $kode => $nilai) {
            $batas[(string) $kode] = $nilai === null ? null : (float) $nilai;
        }

        return new Entitlement(
            paketId: isset($data['PaketId']) ? (string) $data['PaketId'] : null,
            namaPaket: isset($data['NamaPaket']) ? (string) $data['NamaPaket'] : null,
            status: StatusLangganan::from((string) $data['Status']),
            fitur: $fitur,
            batas: $batas,
            berakhirPada: isset($data['BerakhirPada']) ? (string) $data['BerakhirPada'] : null,
            ujiCobaSampai: isset($data['UjiCobaSampai']) ? (string) $data['UjiCobaSampai'] : null,
        );
    }

    private function kunciCache(string $organisasiId): string
    {
        return "entitlement:{$organisasiId}";
    }
}
