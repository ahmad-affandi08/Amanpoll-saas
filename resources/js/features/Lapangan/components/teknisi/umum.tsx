import { router } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { KartuApung } from '@/features/Lapangan/components/Kartu';
import type { NamaIkon3D } from '@/features/Lapangan/components/Ikon3D';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import { PerhentianLangkah, type LangkahPerhentian } from '@/features/Lapangan/components/Perhentian';
import { ruteLapangan } from '@/features/Lapangan/api';
import type { KeadaanLokalTiket } from '@/features/Lapangan/components/teknisi/statusLokal';
import { pewaktu } from '@/features/Lapangan/components/teknisi/waktuTiket';

/**
 * Kunjungan Inertia yang gagal karena sinyal hilang dan layarnya belum tersimpan di perangkat
 * diberi pesan, bukan diam saja. Layar yang pernah dibuka tetap tersaji dari service worker.
 */
export function usePeringatanOffline(): void {
  useEffect(
    () =>
      router.on('networkError', () => {
        toast.error(
          navigator.onLine
            ? 'Server tidak terjangkau. Coba lagi sebentar.'
            : 'Layar ini belum tersimpan di HP. Butuh sinyal untuk membukanya pertama kali.',
        );
      }),
    [],
  );
}

/**
 * Mengirim antrean yang tertinggal begitu layar dibuka dengan sinyal. Penyedia sinkronisasi
 * hanya mengirim saat peristiwa `online` atau saat ada mutasi baru; tanpa ini, perubahan
 * yang dibuat sebelum aplikasi ditutup menunggu sampai teknisi menekan "Coba kirim".
 */
export function useKirimAntreanSaatBuka(): void {
  const { dorong } = useSinkronisasiOffline();

  useEffect(() => {
    if (navigator.onLine) void dorong();
    // Sekali per layar; `dorong` sendiri menolak berjalan ganda.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);
}

/** Detik berjalan sejak `mulai`, diperbarui tiap detik. */
export function useDetikBerjalan(mulai: string | null | undefined): number {
  const [sekarang, setSekarang] = useState(() => Date.now());

  useEffect(() => {
    if (!mulai) return;
    const pewaktuId = window.setInterval(() => setSekarang(Date.now()), 1000);
    return () => window.clearInterval(pewaktuId);
  }, [mulai]);

  if (!mulai) return 0;
  return Math.max(0, (sekarang - new Date(mulai).getTime()) / 1000);
}

/** Pewaktu kerja di appbar (papan layar 07–11): titik merah berdenyut saat berjalan. */
export function PewaktuKerja({ detik, berjalan }: { detik: number; berjalan: boolean }) {
  return (
    <span
      role="timer"
      aria-label={`Waktu kerja ${pewaktu(detik)}${berjalan ? '' : ', dijeda'}`}
      className="inline-flex h-9 shrink-0 items-center gap-2 rounded-full bg-white/15 pr-3.5 pl-3 text-base font-bold tabular-nums"
    >
      <span
        aria-hidden
        className={
          berjalan
            ? 'size-2 animate-pulse rounded-full bg-lapangan-oranye-600 shadow-[0_0_0_4px_rgb(232_101_12_/_0.3)] motion-reduce:animate-none'
            : 'size-2 rounded-full bg-white/60'
        }
      />
      {pewaktu(detik)}
    </span>
  );
}

export const LANGKAH_KERJA = ['checklist', 'diagnosis', 'suku-cadang', 'foto', 'ringkasan'] as const;
export type LangkahKerja = (typeof LANGKAH_KERJA)[number];

const LABEL_LANGKAH: Record<LangkahKerja, string> = {
  checklist: 'Checklist',
  diagnosis: 'Diagnosis',
  'suku-cadang': 'Suku cadang',
  foto: 'Foto',
  ringkasan: 'Selesai',
};

/** Perhentian langkah pengerjaan dalam kartu apung di bawah appbar. */
export function KartuLangkahKerja({ aktif }: { aktif: LangkahKerja }) {
  const indeks = LANGKAH_KERJA.indexOf(aktif);
  const langkah: LangkahPerhentian[] = LANGKAH_KERJA.map((satu, i) => ({
    label: LABEL_LANGKAH[satu],
    keadaan: i < indeks ? 'lewat' : i === indeks ? 'kini' : 'nanti',
  }));

  return (
    <KartuApung>
      <PerhentianLangkah langkah={langkah} />
    </KartuApung>
  );
}

/** Pita peringatan tiket: konflik yang perlu dipilih atau perubahan yang ditolak server. */
export function PitaMasalahTiket({ keadaan }: { keadaan: KeadaanLokalTiket }) {
  const konflik = keadaan.Konflik[0];
  const gagal = keadaan.Gagal[0];

  if (konflik) {
    return (
      <PitaInfo
        nada="merah"
        ikon="warning"
        judul="Tiket ini diubah di dua tempat"
        teks="Pilih versi yang dipakai supaya perubahanmu tidak hilang."
        tautan={{ label: 'Pilih versi', href: ruteLapangan.teknisi.konflik(konflik.KunciOperasi) }}
      />
    );
  }

  if (gagal) {
    return (
      <PitaInfo
        nada="merah"
        ikon="warning"
        judul="Perubahan ditolak server"
        teks={gagal.Konflik?.Pesan ?? gagal.Label}
        tautan={{ label: 'Lihat', href: ruteLapangan.akun }}
      />
    );
  }

  return null;
}

/** Ikon 3D untuk kode kegagalan dari nama yang ditentukan tenant (papan layar 08). */
export function ikonKodeKegagalan(nama: string): NamaIkon3D {
  if (/listrik|elektr|daya|tegangan/i.test(nama)) return 'high_voltage';
  if (/panas|suhu|terbakar|api/i.test(nama)) return 'fire';
  if (/mekani|aus|getar|bantalan|gear/i.test(nama)) return 'gear';
  if (/kabel|konektor|terminal/i.test(nama)) return 'electric_plug';
  if (/sensor|kontrol|panel/i.test(nama)) return 'satellite_antenna';
  if (/bocor|air|pipa/i.test(nama)) return 'droplet';
  return 'toolbox';
}

/**
 * Bilah aksi menempel di bawah untuk layar alur yang tombolnya butuh state langkah
 * (sama persis dengan bilah `KerangkaLapangan`); pasangkan `KELAS_ISI_BERBILAH` pada isi.
 */
export function BilahTetap({ children }: { children: ReactNode }) {
  return (
    <div className="fixed inset-x-0 bottom-0 z-30 mx-auto flex w-full max-w-[480px] gap-2.5 rounded-t-3xl bg-white px-4 pt-3.5 pb-[calc(env(safe-area-inset-bottom)+14px)] shadow-lapangan-bilah">
      {children}
    </div>
  );
}

export const KELAS_ISI_BERBILAH = 'pb-[calc(env(safe-area-inset-bottom)+112px)]';

/** Penanda sesi tab: pengguna memilih melewati "Menyiapkan Mode Lapangan", beranda tidak mengalihkan lagi. */
export const KUNCI_SIAPKAN_DILEWATI = 'amanpoll:lapangan:siapkan-dilewati';
