<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Izin\PemeriksaIzin;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use App\Domain\Platform\Domain\Enums\ModeLapangan;
use App\Domain\Platform\Domain\ValueObjects\KatalogPeranAwal;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan peran lapangan bawaan milik organisasi lama dengan katalog FASE 39 (PRD 8.20).
 *
 * `KatalogPeranAwal` hanya dibaca saat peran dipasang, jadi organisasi yang
 * memasangnya sebelum FASE 39 masih memberi Teknisi dan Pelapor izin Kelola
 * atas seluruh tiket dan keluhan. Aksi ini dijalankan lewat perintah artisan
 * yang eksplisit, tidak pernah diam-diam, dan setiap peran yang berubah dicatat di audit.
 *
 * Yang disentuh sengaja sempit:
 * - hanya peran yang kodenya ada di katalog dan bertanda Tampilan Lapangan
 *   (`TEKNISI`, `PELAPOR`); peran lain milik tenant tidak dibaca sama sekali;
 * - izin yang dicabut hanya yang disebut `IZIN_DICABUT_DARI_PERAN_LAPANGAN`,
 *   bukan penyamaan penuh dengan katalog, supaya izin yang ditambahkan tenant
 *   sendiri tetap ada;
 * - penanda `TampilanLapangan` hanya diisi bila masih kosong. Nilai lain yang
 *   sudah dipilih tenant dibiarkan dan dilaporkan.
 *
 * Idempotent: menjalankannya lagi tidak mengubah apa pun dan tidak menulis audit baru.
 */
final class TerapkanKatalogPeranLapangan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly KonteksOrganisasi $konteks,
        private readonly LayananAudit $audit,
        private readonly PemeriksaIzin $pemeriksaIzin,
        private readonly PenentuModeLapangan $penentuModeLapangan,
    ) {}

    /**
     * Perubahan yang akan dilakukan pada satu organisasi, tanpa menulis apa pun.
     *
     * @return list<array{PeranId: string, Kode: string, Nama: string, IzinDicabut: list<string>, TampilanSebelum: string|null, TampilanSesudah: string, TampilanDibiarkan: bool}>
     */
    public function rencana(string $organisasiId): array
    {
        $rencana = [];

        foreach (KatalogPeranAwal::semua() as $contoh) {
            if ($contoh['TampilanLapangan'] === null) {
                continue;
            }

            $peran = DB::table('Peran')
                ->where('OrganisasiId', $organisasiId)
                ->where('Kode', $contoh['Kode'])
                ->whereNull('DihapusPada')
                ->first(['Id', 'Kode', 'Nama', 'TampilanLapangan']);

            if ($peran === null) {
                continue;
            }

            $izinDicabut = array_values(DB::table('PeranIzin as pi')
                ->join('Izin as i', 'i.Id', '=', 'pi.IzinId')
                ->where('pi.PeranId', $peran->Id)
                ->whereIn('i.Kode', KatalogPeranAwal::IZIN_DICABUT_DARI_PERAN_LAPANGAN[$contoh['Kode']] ?? [])
                ->orderBy('i.Kode')
                ->pluck('i.Kode')
                ->map(fn (mixed $kode): string => is_string($kode) ? $kode : '')
                ->all());

            $tampilanSebelum = is_string($peran->TampilanLapangan) ? $peran->TampilanLapangan : null;
            $tampilanSesudah = $tampilanSebelum ?? $contoh['TampilanLapangan']->value;
            $tampilanDibiarkan = $tampilanSebelum !== null && $tampilanSebelum !== $contoh['TampilanLapangan']->value;

            if ($izinDicabut === [] && $tampilanSebelum === $tampilanSesudah && ! $tampilanDibiarkan) {
                continue;
            }

            $rencana[] = [
                'PeranId' => (string) $peran->Id,
                'Kode' => (string) $peran->Kode,
                'Nama' => (string) $peran->Nama,
                'IzinDicabut' => $izinDicabut,
                'TampilanSebelum' => $tampilanSebelum,
                'TampilanSesudah' => $tampilanSesudah,
                'TampilanDibiarkan' => $tampilanDibiarkan,
            ];
        }

        return $rencana;
    }

    /**
     * Menerapkan rencana pada satu organisasi.
     *
     * @return list<array{PeranId: string, Kode: string, Nama: string, IzinDicabut: list<string>, TampilanSebelum: string|null, TampilanSesudah: string, TampilanDibiarkan: bool}>
     */
    public function jalankan(string $organisasiId): array
    {
        if ($this->konteks->ada() && $this->konteks->wajibId() !== $organisasiId) {
            throw new AturanBisnisDilanggar('Peran bawaan tidak boleh diselaraskan untuk organisasi lain.');
        }

        $konteksSebelumnya = $this->konteks->id();
        $this->konteks->tetapkan($organisasiId);

        try {
            $rencana = $this->rencana($organisasiId);

            foreach ($rencana as $satu) {
                if ($satu['IzinDicabut'] === [] && $satu['TampilanSebelum'] === $satu['TampilanSesudah']) {
                    continue;
                }

                $this->terapkan($organisasiId, $satu);
            }

            return $rencana;
        } finally {
            $this->konteks->tetapkan($konteksSebelumnya);
        }
    }

    /**
     * @param  array{PeranId: string, Kode: string, Nama: string, IzinDicabut: list<string>, TampilanSebelum: string|null, TampilanSesudah: string, TampilanDibiarkan: bool}  $satu
     */
    private function terapkan(string $organisasiId, array $satu): void
    {
        $this->transaksi->jalankan(function () use ($satu): void {
            $peran = Peran::query()->whereKey($satu['PeranId'])->lockForUpdate()->firstOrFail();

            if ($satu['IzinDicabut'] !== []) {
                $izinId = DB::table('Izin')->whereIn('Kode', $satu['IzinDicabut'])->pluck('Id');
                DB::table('PeranIzin')->where('PeranId', $peran->Id)->whereIn('IzinId', $izinId)->delete();
            }

            if ($satu['TampilanSebelum'] !== $satu['TampilanSesudah']) {
                $peran->TampilanLapangan = ModeLapangan::from($satu['TampilanSesudah']);
                $peran->save();
            }

            $this->audit->catat(
                'Peran.KatalogLapanganDiterapkan',
                'Peran',
                $peran->Id,
                dataSebelum: [
                    'Kode' => $satu['Kode'],
                    'IzinDicabut' => $satu['IzinDicabut'],
                    'TampilanLapangan' => $satu['TampilanSebelum'],
                ],
                dataSesudah: [
                    'Kode' => $satu['Kode'],
                    'TampilanLapangan' => $satu['TampilanSesudah'],
                ],
            );
        });

        $pemegang = DB::table('PenggunaPeran')
            ->where('OrganisasiId', $organisasiId)
            ->where('PeranId', $satu['PeranId'])
            ->distinct()
            ->pluck('PenggunaId');

        foreach ($pemegang as $penggunaId) {
            $this->pemeriksaIzin->bersihkanCache($organisasiId, (string) $penggunaId);
            $this->penentuModeLapangan->bersihkanCache($organisasiId, (string) $penggunaId);
        }
    }
}
