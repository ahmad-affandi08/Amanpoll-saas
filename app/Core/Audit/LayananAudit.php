<?php

declare(strict_types=1);

namespace App\Core\Audit;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\IntegrasiAudit\Domain\Repositories\CatatanAuditRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Titik masuk tunggal untuk menulis CatatanAudit. Setiap modul domain
 * memanggil catat() dari Action-nya sendiri saat use-case-nya dibangun --
 * layanan ini tidak tahu apa-apa soal entitas bisnis tertentu.
 */
final class LayananAudit
{
    /**
     * Nama kunci (case-insensitive, dicocokkan sebagai substring) yang
     * nilainya diredaksi dari DataSebelum/DataSesudah supaya kredensial
     * tidak pernah tersimpan mentah di log audit.
     */
    private const KUNCI_RAHASIA = ['katasandi', 'password', 'tokenhash', 'hashkunci', 'token', 'rahasia', 'secret'];

    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly KorelasiId $korelasiId,
        private readonly CatatanAuditRepository $catatanAuditRepository,
    ) {}

    /**
     * @param  array<string, mixed>|null  $dataSebelum
     * @param  array<string, mixed>|null  $dataSesudah
     */
    public function catat(
        string $aksi,
        string $jenisEntitas,
        ?string $entitasId = null,
        ?array $dataSebelum = null,
        ?array $dataSesudah = null,
    ): void {
        if (! config('amanpoll.audit_aktif', true)) {
            return;
        }

        $this->catatanAuditRepository->simpan(new CatatanAudit([
            'OrganisasiId' => $this->konteksOrganisasi->id(),
            'PenggunaId' => Auth::id(),
            'Aksi' => $aksi,
            'JenisEntitas' => $jenisEntitas,
            'EntitasId' => $entitasId,
            'DataSebelum' => $dataSebelum ? $this->redaksi($dataSebelum) : null,
            'DataSesudah' => $dataSesudah ? $this->redaksi($dataSesudah) : null,
            'AlamatIp' => request()->ip(),
            'AgenPengguna' => request()->userAgent(),
            'KorelasiId' => $this->korelasiId->ambil(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function redaksi(array $data): array
    {
        $hasil = [];
        foreach ($data as $kunci => $nilai) {
            $kunciCocokRahasia = collect(self::KUNCI_RAHASIA)
                ->contains(fn (string $pola): bool => Str::contains(Str::lower((string) $kunci), $pola));

            $hasil[$kunci] = match (true) {
                $kunciCocokRahasia => '***DIREDAKSI***',
                is_array($nilai) => $this->redaksi($nilai),
                default => $nilai,
            };
        }

        return $hasil;
    }
}
