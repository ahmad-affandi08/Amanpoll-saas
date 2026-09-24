import { router, usePage } from '@inertiajs/react';
import { CheckCheck } from 'lucide-react';
import { useMemo, useState } from 'react';
import { cn } from '@/lib/utils';
import KerangkaLapangan, { TombolAppbar } from '@/layouts/KerangkaLapangan';
import { ruteNotifikasi } from '@/features/Notifikasi/api';
import type { Notifikasi } from '@/features/Notifikasi/types';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { WadahIkon3D, type NamaIkon3D } from '@/features/Lapangan/components/Ikon3D';
import { Kartu } from '@/features/Lapangan/components/Kartu';
import { PanelTabPil, TabPil } from '@/features/Lapangan/components/TabPil';
import type { PropsHalamanNotifikasi, TintIkon } from '@/features/Lapangan/types';
import { jamPendek, kelompokHari } from '@/features/Lapangan/waktu';

type TabNotifikasi = 'semua' | 'tiket' | 'info';

interface PadananJenis {
  pola: RegExp;
  ikon: NamaIkon3D;
  tint: TintIkon;
}

/** Kata mendesak di judul atau jenis peristiwa didahulukan. */
const IKON_JUDUL: PadananJenis[] = [
  { pola: /Kritis|Darurat/i, ikon: 'police_car_light', tint: 'merah' },
  { pola: /Sla\.Terlewati|Terlambat/i, ikon: 'alarm_clock', tint: 'kuning' },
  { pola: /Sla\.Mendekati/i, ikon: 'hourglass_done', tint: 'kuning' },
];

/** Ikon 3D per jenis peristiwa (papan Teknisi layar 04, Pelapor layar 03). */
const IKON_JENIS: PadananJenis[] = [
  { pola: /Selesai|Ditutup|Disetujui/i, ikon: 'check_mark_button', tint: 'hijau' },
  { pola: /Ditolak|Dibatalkan/i, ikon: 'cross_mark', tint: 'merah' },
  { pola: /^PerintahKerja\.Ditugaskan/i, ikon: 'man_mechanic', tint: 'oranye' },
  { pola: /^PerintahKerja\./i, ikon: 'hammer_and_wrench', tint: 'biru' },
  { pola: /^Keluhan\.Baru/i, ikon: 'megaphone', tint: 'oranye' },
  { pola: /^Keluhan\./i, ikon: 'magnifying_glass_tilted_left', tint: 'kuning' },
  { pola: /^(Stok|SukuCadang|Reservasi|Persediaan)\./i, ikon: 'package', tint: 'ungu' },
  { pola: /^(Pemeliharaan|Rencana|Preventif|Inspeksi|Jadwal)/i, ikon: 'spiral_calendar', tint: 'biru' },
  { pola: /^Persetujuan\./i, ikon: 'memo', tint: 'biru' },
  { pola: /^Kontrak\./i, ikon: 'handshake', tint: 'biru' },
  { pola: /^Laporan\./i, ikon: 'bookmark_tabs', tint: 'biru' },
];

const ENTITAS_TIKET = ['PerintahKerja', 'Keluhan', 'JadwalPemeliharaan', 'Inspeksi'];

function ikonNotifikasi(notifikasi: Notifikasi): { ikon: NamaIkon3D; tint: TintIkon } {
  // Judul ikut dicocokkan supaya "Tiket kritis" atau "Tiket terlambat" mendapat ikon yang lebih mendesak.
  const teks = `${notifikasi.Judul ?? ''} ${notifikasi.JenisPeristiwa}`;
  const cocok =
    IKON_JUDUL.find((satu) => satu.pola.test(teks)) ??
    IKON_JENIS.find((satu) => satu.pola.test(notifikasi.JenisPeristiwa));
  return cocok ? { ikon: cocok.ikon, tint: cocok.tint } : { ikon: 'bell', tint: 'kuning' };
}

/** Notifikasi "Tiket" menyangkut pekerjaan atau laporan; sisanya "Info". */
function termasukTiket(notifikasi: Notifikasi): boolean {
  if (notifikasi.JenisEntitas && ENTITAS_TIKET.includes(notifikasi.JenisEntitas)) return true;
  return /^(PerintahKerja|Keluhan|Pemeliharaan|Inspeksi)\./.test(notifikasi.JenisPeristiwa);
}

function kelompokkan(daftar: Notifikasi[]): { hari: string; isi: Notifikasi[] }[] {
  const kelompok: { hari: string; isi: Notifikasi[] }[] = [];
  for (const satu of daftar) {
    const hari = kelompokHari(satu.DibuatPada);
    const terakhir = kelompok[kelompok.length - 1];
    if (terakhir && terakhir.hari === hari) {
      terakhir.isi.push(satu);
    } else {
      kelompok.push({ hari, isi: [satu] });
    }
  }
  return kelompok;
}

/** Notifikasi Mode Lapangan (papan Teknisi layar 04, Pelapor layar 03). */
export default function LapanganNotifikasi() {
  const { props } = usePage<PropsHalamanNotifikasi>();
  const daftarAwal = props.notifikasi ?? [];
  const [dibacaLokal, setDibacaLokal] = useState<Set<string>>(() => new Set());
  const [semuaDibaca, setSemuaDibaca] = useState(false);
  const [tab, setTab] = useState<TabNotifikasi>('semua');
  const pakaiTab = props.lapangan?.mode !== 'Pelapor';

  const daftar = useMemo(
    () =>
      daftarAwal.map((satu) =>
        !satu.DibacaPada && (semuaDibaca || dibacaLokal.has(satu.Id))
          ? { ...satu, DibacaPada: new Date().toISOString() }
          : satu,
      ),
    [daftarAwal, dibacaLokal, semuaDibaca],
  );
  const jumlahBelumDibaca = semuaDibaca
    ? 0
    : Math.max(
        0,
        (props.jumlahBelumDibaca ?? 0) -
          daftarAwal.filter((s) => !s.DibacaPada && dibacaLokal.has(s.Id)).length,
      );

  const tersaring = pakaiTab
    ? daftar.filter(
        (satu) => tab === 'semua' || (tab === 'tiket' ? termasukTiket(satu) : !termasukTiket(satu)),
      )
    : daftar;

  /** Menandai sudah dibaca memakai endpoint notifikasi yang sama dengan dasbor. */
  const bacaSatu = (notifikasi: Notifikasi) => {
    if (notifikasi.DibacaPada) return;
    setDibacaLokal((lama) => new Set(lama).add(notifikasi.Id));
    router.post(ruteNotifikasi.baca(notifikasi.Id), {}, { preserveScroll: true, preserveState: true });
  };

  const bacaSemua = () => {
    setSemuaDibaca(true);
    router.post(ruteNotifikasi.bacaSemua, {}, { preserveScroll: true, preserveState: true });
  };

  const isi = (
    <>
      {tersaring.length === 0 ? (
        <IlustrasiMomen
          ringkas
          ikon="bell"
          judul={daftar.length === 0 ? 'Belum ada notifikasi' : 'Tidak ada notifikasi di sini'}
          teks={
            daftar.length === 0
              ? 'Kabar tentang tiket dan laporanmu akan muncul di sini.'
              : 'Coba lihat tab lainnya.'
          }
        />
      ) : (
        kelompokkan(tersaring).map((kelompok) => (
          <section key={kelompok.hari} className="flex flex-col gap-3">
            <h2 className="px-0.5 pt-0.5 text-[15px] font-bold text-lapangan-teks">{kelompok.hari}</h2>
            <Kartu className="overflow-hidden">
              <ul>
                {kelompok.isi.map((notifikasi) => (
                  <BarisNotifikasi key={notifikasi.Id} notifikasi={notifikasi} onBaca={bacaSatu} />
                ))}
              </ul>
            </Kartu>
          </section>
        ))
      )}
    </>
  );

  return (
    <KerangkaLapangan
      judulHalaman="Notifikasi"
      varian="appbar"
      judul="Notifikasi"
      subjudul={jumlahBelumDibaca > 0 ? `${jumlahBelumDibaca} belum dibaca` : 'Semua sudah dibaca'}
      aksiKanan={
        jumlahBelumDibaca > 0 ? (
          <TombolAppbar
            label="Tandai semua dibaca"
            teks="Tandai dibaca"
            ikon={CheckCheck}
            onClick={bacaSemua}
          />
        ) : undefined
      }
      classNameIsi="gap-3"
    >
      {pakaiTab ? (
        <>
          <TabPil<TabNotifikasi>
            label="Jenis notifikasi"
            idAwalan="notifikasi"
            aktif={tab}
            onGanti={setTab}
            item={[
              { kunci: 'semua', label: 'Semua', jumlah: jumlahBelumDibaca },
              { kunci: 'tiket', label: 'Tiket' },
              { kunci: 'info', label: 'Info' },
            ]}
          />
          <PanelTabPil idAwalan="notifikasi" kunci={tab}>
            {isi}
          </PanelTabPil>
        </>
      ) : (
        isi
      )}
    </KerangkaLapangan>
  );
}

function BarisNotifikasi({
  notifikasi,
  onBaca,
}: {
  notifikasi: Notifikasi;
  onBaca: (n: Notifikasi) => void;
}) {
  const baru = !notifikasi.DibacaPada;
  const { ikon, tint } = ikonNotifikasi(notifikasi);
  const kelas = cn('relative flex w-full gap-3 px-4 py-3.5 text-left', baru && 'bg-lapangan-biru-50/50');

  const isi = (
    <>
      <WadahIkon3D nama={ikon} tint={tint} />
      <span className="min-w-0 flex-1 pr-3.5">
        <b className={cn('block text-[15px] leading-[1.3]', baru ? 'font-bold' : 'font-semibold')}>
          {notifikasi.Judul ?? notifikasi.JenisPeristiwa}
        </b>
        <span className="mt-0.5 block text-[13.5px] leading-[1.4] text-lapangan-teks-2">
          {notifikasi.Isi}
        </span>
        <small className="mt-[5px] block text-xs font-semibold text-lapangan-teks-3">
          {jamPendek(notifikasi.DibuatPada)}
        </small>
      </span>
      {baru && (
        <>
          <span
            aria-hidden
            className="absolute top-[18px] right-4 size-2.5 rounded-full bg-lapangan-oranye-600"
          />
          <span className="sr-only">Belum dibaca. Ketuk untuk menandai sudah dibaca.</span>
        </>
      )}
    </>
  );

  return (
    <li className="[&+&]:border-t-[1.5px] [&+&]:border-lapangan-garis-2">
      {baru ? (
        <button
          type="button"
          onClick={() => onBaca(notifikasi)}
          className={cn(
            kelas,
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500 focus-visible:ring-inset',
          )}
        >
          {isi}
        </button>
      ) : (
        <div className={kelas}>{isi}</div>
      )}
    </li>
  );
}
