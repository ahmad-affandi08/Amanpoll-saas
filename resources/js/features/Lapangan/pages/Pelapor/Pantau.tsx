import { usePage } from '@inertiajs/react';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { Ikon3D } from '@/features/Lapangan/components/Ikon3D';
import { Kartu } from '@/features/Lapangan/components/Kartu';
import { PerhentianLinimasa, type TitikLinimasa } from '@/features/Lapangan/components/Perhentian';
import { Sobekan } from '@/features/Lapangan/components/Tiket';
import { tampilanStatus } from '@/features/Lapangan/components/pelapor/status';
import { ikonKategori } from '@/features/Lapangan/ikon';
import type { PropsPantauPelapor, StatusKeluhanPelapor } from '@/features/Lapangan/types';
import { jamPendek, kelompokHari } from '@/features/Lapangan/waktu';

/** Nama perhentian garis waktu laporan rekan. "Selesai" bukan "Perlu konfirmasimu": itu bukan laporanmu. */
const JUDUL_STATUS: Record<StatusKeluhanPelapor, string> = {
  Baru: 'Dilaporkan',
  Ditinjau: 'Ditinjau tim teknik',
  Diterima: 'Teknisi ditugaskan',
  Diproses: 'Sedang dikerjakan',
  Selesai: 'Selesai diperbaiki',
  Ditutup: 'Ditutup',
  Ditolak: 'Ditolak',
  Dibatalkan: 'Dibatalkan',
};

/**
 * Pantau laporan rekan (PRD 8.20, TASK 39.10): hanya garis waktu status laporan orang lain
 * pada alat/lokasi di sekitarmu. Server hanya mengirim nomor, judul, alat/lokasi, status,
 * dan jam tiap perubahan — tanpa nama pelapor, nama teknisi, keterangan, atau foto.
 */
export default function PantauPelapor() {
  const { props } = usePage<PropsPantauPelapor>();

  return (
    <KerangkaLapangan
      judulHalaman={`Pantau ${props.laporan.Nomor}`}
      varian="appbar"
      mode="Pelapor"
      judul="Pantau laporan"
      subjudul={<span className="tabular-nums">{props.laporan.Nomor}</span>}
      panjang
    >
      <IsiPantau {...props} />
    </KerangkaLapangan>
  );
}

function IsiPantau({ laporan, riwayat }: PropsPantauPelapor) {
  const status = tampilanStatus(laporan.Status);
  const ikon = ikonKategori(laporan.Aset?.Kategori ?? laporan.Aset?.Nama ?? laporan.Judul);
  const akhir = ['Ditutup', 'Ditolak', 'Dibatalkan'].includes(laporan.Status);
  const titik: TitikLinimasa[] = riwayat.map((satu, i) => ({
    judul: JUDUL_STATUS[satu.Status],
    keterangan: satu.Pada ? kelompokHari(satu.Pada) : undefined,
    jam: jamPendek(satu.Pada),
    keadaan: i === riwayat.length - 1 && !akhir ? 'kini' : 'lewat',
  }));

  return (
    <>
      <article className="relative z-10 -mt-14 rounded-[20px] bg-white shadow-lapangan-apung">
        <div className="px-4 pt-4 pb-3">
          <ChipStatus warna={status.warna} ikon={status.ikon}>
            {JUDUL_STATUS[laporan.Status]}
          </ChipStatus>
          <h2 className="mt-2 text-[17px] leading-[1.3] font-bold tracking-[-0.01em]">{laporan.Judul}</h2>
        </div>
        <Sobekan />
        <div className="flex items-center gap-3 px-4 pt-1.5 pb-3.5">
          <Ikon3D nama={laporan.Aset ? ikon.ikon : 'round_pushpin'} ukuran={32} />
          <div className="min-w-0 flex-1">
            <b className="block truncate text-sm font-bold">{laporan.Aset?.Nama ?? 'Lokasi saja'}</b>
            <span className="block truncate text-[13px] font-medium text-lapangan-teks-3">
              {[laporan.Aset?.KodeAset, laporan.LokasiLabel].filter(Boolean).join(' · ') || '—'}
            </span>
          </div>
        </div>
      </article>

      <PitaInfo
        nada="biru"
        ikon="magnifying_glass_tilted_left"
        judul="Laporan rekanmu"
        teks="Yang tampil hanya perjalanan statusnya. Kamu tidak perlu melapor lagi bila masalahnya sama."
      />

      <Kartu className="pt-4 pr-3 pb-0 pl-2">
        <h2 className="mb-3.5 pl-2 text-[15px] font-bold tracking-[-0.01em]">Perjalanan status</h2>
        {titik.length > 0 ? (
          <PerhentianLinimasa titik={titik} label="Perjalanan status" />
        ) : (
          <p className="px-2 pb-4 text-sm text-lapangan-teks-3">Belum ada perubahan status.</p>
        )}
      </Kartu>
    </>
  );
}
