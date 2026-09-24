import { Search, SlidersHorizontal, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { cn } from '@/lib/utils';
import KerangkaLapangan, { TombolAppbar } from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { IsianTiket, MasukanTiket } from '@/features/Lapangan/components/IsianTiket';
import { Kartu } from '@/features/Lapangan/components/Kartu';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { PanelTabPil, TabPil } from '@/features/Lapangan/components/TabPil';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import type { PropsTugasTeknisi, TiketTeknisi } from '@/features/Lapangan/types';
import { KartuTiketTeknisi } from '@/features/Lapangan/components/teknisi/KartuTiketTeknisi';
import { tiketLokal } from '@/features/Lapangan/components/teknisi/statusLokal';
import { usePeringatanOffline, useKirimAntreanSaatBuka } from '@/features/Lapangan/components/teknisi/umum';
import { STATUS_SELESAI_TEKNISI, terlambat } from '@/features/Lapangan/components/teknisi/waktuTiket';

type TabTugas = 'hari-ini' | 'terlambat' | 'selesai';

const PRIORITAS = ['Semua', 'Kritis', 'Tinggi', 'Normal', 'Rendah'] as const;

function tabAwal(): TabTugas {
  if (typeof window === 'undefined') return 'hari-ini';
  const tab = new URLSearchParams(window.location.search).get('tab');
  return tab === 'terlambat' || tab === 'selesai' ? tab : 'hari-ini';
}

/** Tiket Saya (DESIGN §36.6 layar 05): tab pil Hari ini · Terlambat · Selesai. */
export default function TugasTeknisi(props: PropsTugasTeknisi) {
  const [tab, setTab] = useState<TabTugas>(tabAwal);
  const [cariTerbuka, setCariTerbuka] = useState(false);
  const [kata, setKata] = useState('');
  const [saringTerbuka, setSaringTerbuka] = useState(false);
  const [prioritas, setPrioritas] = useState<(typeof PRIORITAS)[number]>('Semua');

  const tanggal = new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  return (
    <KerangkaLapangan
      judulHalaman="Tiket Saya"
      navAktif="tugas"
      apung={false}
      statusSinkron={false}
      classNameIsi="-mt-2 pt-0"
      isiHero={
        <IsiKepala
          tanggal={tanggal}
          tab={tab}
          onTab={setTab}
          onCari={() => setCariTerbuka((buka) => !buka)}
          onSaring={() => setSaringTerbuka(true)}
          saringAktif={prioritas !== 'Semua'}
          {...props}
        />
      }
    >
      <DaftarTugas
        {...props}
        tab={tab}
        kata={kata}
        prioritas={prioritas}
        cariTerbuka={cariTerbuka}
        onKata={setKata}
        onTutupCari={() => {
          setKata('');
          setCariTerbuka(false);
        }}
      />
      <LembarBawah
        buka={saringTerbuka}
        onBukaBerubah={setSaringTerbuka}
        judul="Saring tiket"
        deskripsi="Tampilkan tiket menurut prioritas."
        kaki={
          <TombolLapangan penuh onClick={() => setSaringTerbuka(false)}>
            Terapkan
          </TombolLapangan>
        }
      >
        <div role="radiogroup" aria-label="Prioritas" className="flex flex-wrap gap-2">
          {PRIORITAS.map((satu) => (
            <button
              key={satu}
              type="button"
              role="radio"
              aria-checked={prioritas === satu}
              onClick={() => setPrioritas(satu)}
              className={cn(
                'h-11 rounded-xl px-4 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
                prioritas === satu
                  ? 'bg-lapangan-navy-800 text-white'
                  : 'bg-white text-lapangan-teks-2 ring-[1.5px] ring-lapangan-garis ring-inset',
              )}
            >
              {satu}
            </button>
          ))}
        </div>
      </LembarBawah>
    </KerangkaLapangan>
  );
}

interface PropsKepala extends PropsTugasTeknisi {
  tanggal: string;
  tab: TabTugas;
  onTab: (tab: TabTugas) => void;
  onCari: () => void;
  onSaring: () => void;
  saringAktif: boolean;
}

function IsiKepala({ tanggal, tab, onTab, onCari, onSaring, saringAktif, tiket }: PropsKepala) {
  const { antrian } = useSinkronisasiOffline();
  const aktif = tiket
    .map((satu) => tiketLokal(satu, antrian))
    .filter((satu) => !STATUS_SELESAI_TEKNISI.includes(satu.Status));
  const jumlahTerlambat = aktif.filter((satu) => terlambat(satu)).length;

  return (
    <div className="flex flex-col gap-4">
      <div className="flex items-center gap-2.5">
        <div className="min-w-0 flex-1">
          <h1 className="text-[26px] leading-tight font-extrabold tracking-[-0.02em]">Tiket Saya</h1>
          <p className="text-[13px] font-medium text-white/75 first-letter:uppercase">{tanggal}</p>
        </div>
        <TombolAppbar label="Cari tiket" ikon={Search} onClick={onCari} />
        <span className="relative">
          <TombolAppbar label="Saring tiket" ikon={SlidersHorizontal} onClick={onSaring} />
          {saringAktif && (
            <span
              aria-hidden
              className="absolute top-1.5 right-1.5 size-2.5 rounded-full border-2 border-lapangan-navy-800 bg-lapangan-oranye-600"
            />
          )}
        </span>
      </div>
      <TabPil<TabTugas>
        label="Kelompok tiket"
        idAwalan="tiket-saya"
        aktif={tab}
        onGanti={onTab}
        item={[
          { kunci: 'hari-ini', label: 'Hari ini', jumlah: aktif.length },
          { kunci: 'terlambat', label: 'Terlambat', jumlah: jumlahTerlambat },
          { kunci: 'selesai', label: 'Selesai' },
        ]}
      />
    </div>
  );
}

interface PropsDaftar extends PropsTugasTeknisi {
  tab: TabTugas;
  kata: string;
  prioritas: (typeof PRIORITAS)[number];
  cariTerbuka: boolean;
  onKata: (kata: string) => void;
  onTutupCari: () => void;
}

function cocok(tiket: TiketTeknisi, kata: string, prioritas: string): boolean {
  if (prioritas !== 'Semua' && tiket.Prioritas !== prioritas) return false;
  if (kata.trim() === '') return true;
  const teks = `${tiket.Nomor} ${tiket.Judul} ${tiket.Aset?.Nama ?? ''} ${tiket.Aset?.KodeAset ?? ''} ${tiket.Lokasi?.Nama ?? ''}`;
  return teks.toLowerCase().includes(kata.trim().toLowerCase());
}

function DaftarTugas({
  tiket,
  selesai,
  tab,
  kata,
  prioritas,
  cariTerbuka,
  onKata,
  onTutupCari,
}: PropsDaftar) {
  usePeringatanOffline();
  useKirimAntreanSaatBuka();
  const { antrian } = useSinkronisasiOffline();

  const { aktif, daftarSelesai } = useMemo(() => {
    const lokal = tiket.map((satu) => tiketLokal(satu, antrian));
    return {
      aktif: lokal.filter((satu) => !STATUS_SELESAI_TEKNISI.includes(satu.Status)),
      daftarSelesai: [...lokal.filter((satu) => STATUS_SELESAI_TEKNISI.includes(satu.Status)), ...selesai],
    };
  }, [tiket, selesai, antrian]);

  const sumber =
    tab === 'selesai' ? daftarSelesai : tab === 'terlambat' ? aktif.filter((satu) => terlambat(satu)) : aktif;
  const tampil = sumber.filter((satu) => cocok(satu, kata, prioritas));

  const kosong = {
    'hari-ini': {
      judul: 'Belum ada tiket',
      teks: 'Tiket yang ditugaskan kepadamu akan muncul di sini.',
      ikon: 'clipboard' as const,
    },
    terlambat: {
      judul: 'Tidak ada yang terlambat',
      teks: 'Semua tiketmu masih dalam batas waktu.',
      ikon: 'check_mark_button' as const,
    },
    selesai: {
      judul: 'Belum ada yang selesai',
      teks: 'Tiket yang kamu selesaikan dua minggu terakhir tampil di sini.',
      ikon: 'trophy' as const,
    },
  }[tab];

  return (
    <PanelTabPil idAwalan="tiket-saya" kunci={tab} className="gap-3.5">
      {cariTerbuka && (
        <IsianTiket
          label="Cari tiket"
          ikon={Search}
          kanan={
            <button
              type="button"
              onClick={onTutupCari}
              aria-label="Tutup pencarian"
              className="flex size-11 items-center justify-center rounded-full text-lapangan-teks-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
            >
              <X aria-hidden className="size-5" />
            </button>
          }
        >
          <MasukanTiket
            autoFocus
            value={kata}
            onChange={(event) => onKata(event.target.value)}
            placeholder="Nomor, judul, atau aset"
            enterKeyHint="search"
          />
        </IsianTiket>
      )}

      {tampil.length === 0 ? (
        <Kartu>
          <IlustrasiMomen
            ringkas
            jenis="kosong"
            ikon={kata || prioritas !== 'Semua' ? 'magnifying_glass_tilted_left' : kosong.ikon}
            judul={kata || prioritas !== 'Semua' ? 'Tidak ada yang cocok' : kosong.judul}
            teks={kata || prioritas !== 'Semua' ? 'Coba kata kunci atau saringan lain.' : kosong.teks}
          />
        </Kartu>
      ) : (
        <ul className="flex flex-col gap-3.5">
          {tampil.map((satu) => (
            <li key={satu.Id}>
              <KartuTiketTeknisi tiket={satu} href={ruteLapangan.teknisi.tugasDetail(satu.Id)} />
            </li>
          ))}
        </ul>
      )}
    </PanelTabPil>
  );
}
