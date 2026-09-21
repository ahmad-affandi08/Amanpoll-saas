<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\ValueObjects;

use App\Domain\Langganan\Domain\Enums\StatusLangganan;

/**
 * Potret entitlement satu organisasi pada satu saat (22.05).
 *
 * Dihitung sekali lalu disimpan di cache, sehingga seluruh pemeriksaan dalam
 * satu permintaan — gerbang rute, penjaga batas, dan prop untuk UI — membaca
 * jawaban yang sama. Tanpa itu, UI bisa menampilkan tombol yang backend tolak
 * pada permintaan berikutnya.
 */
final readonly class Entitlement
{
    /**
     * @param  array<string, bool>  $fitur  kode fitur => diizinkan
     * @param  array<string, float|null>  $batas  kode fitur => batas (null berarti tanpa batas)
     */
    public function __construct(
        public ?string $paketId,
        public ?string $namaPaket,
        public StatusLangganan $status,
        public array $fitur,
        public array $batas,
        public ?string $berakhirPada = null,
        public ?string $ujiCobaSampai = null,
    ) {}

    /**
     * Permintaan tanpa tenant sama sekali (mis. webhook atau konsol platform).
     * Tidak diberi fitur apa pun karena tidak ada organisasi yang haknya bisa
     * dinilai.
     */
    public static function tanpaTenant(): self
    {
        return new self(null, null, StatusLangganan::Kedaluwarsa, [], []);
    }

    /**
     * Uji coba awal sebuah organisasi yang belum diberi paket.
     *
     * Organisasi baru harus dapat langsung dipakai tanpa menunggu admin
     * platform menetapkan paket, tetapi keleluasaan itu berbatas waktu: setelah
     * uji coba lewat, statusnya menjadi kedaluwarsa dan akses tulis berhenti.
     * Dengan begitu tenant yang terlupakan gagal ke arah tertutup, bukan ke
     * arah layanan gratis tanpa batas.
     *
     * @param  array<string, bool>  $fitur
     */
    public static function ujiCobaAwal(
        array $fitur,
        bool $masihBerjalan,
        string $sampai,
    ): self {
        return new self(
            paketId: null,
            namaPaket: 'Uji Coba',
            status: $masihBerjalan ? StatusLangganan::UjiCoba : StatusLangganan::Kedaluwarsa,
            fitur: $fitur,
            // Uji coba awal tidak dibatasi jumlah; yang membatasinya adalah waktu.
            batas: [],
            berakhirPada: $sampai,
            ujiCobaSampai: $masihBerjalan ? $sampai : null,
        );
    }

    public function bolehFitur(string $kode): bool
    {
        return $this->fitur[$kode] ?? false;
    }

    /** null berarti tanpa batas. */
    public function batas(string $kode): ?float
    {
        return $this->batas[$kode] ?? null;
    }

    public function memberiAksesPenuh(): bool
    {
        return $this->status->memberiAksesPenuh();
    }

    /** @return array<string, mixed> */
    public function keArray(): array
    {
        return [
            'PaketId' => $this->paketId,
            'NamaPaket' => $this->namaPaket,
            'Status' => $this->status->value,
            'LabelStatus' => $this->status->label(),
            'AksesPenuh' => $this->memberiAksesPenuh(),
            'Fitur' => $this->fitur,
            'Batas' => $this->batas,
            'BerakhirPada' => $this->berakhirPada,
            'UjiCobaSampai' => $this->ujiCobaSampai,
        ];
    }
}
