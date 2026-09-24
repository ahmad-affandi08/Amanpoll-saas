import { Link, router } from '@inertiajs/react';
import { isAxiosError } from 'axios';
import { CircleAlert, CircleCheck } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { http } from '@/lib/http';
import { cn } from '@/lib/utils';
import { ruteLapangan } from '@/features/Lapangan/api';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { Ikon3D, kelasTint, type NamaIkon3D } from '@/features/Lapangan/components/Ikon3D';
import { AreaTiket, IsianTiket } from '@/features/Lapangan/components/IsianTiket';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { ikonKategori } from '@/features/Lapangan/ikon';
import type { AsetDitemukanTeknisi, TintIkon } from '@/features/Lapangan/types';
import { jamPendek, tanggalPendek } from '@/features/Lapangan/waktu';
import { useAksiTiket } from '@/features/Lapangan/components/teknisi/aksiTiket';
import { STATUS_SEDANG_DIKERJAKAN } from '@/features/Lapangan/components/teknisi/statusLokal';
import { teksLokasi } from '@/features/Lapangan/components/teknisi/waktuTiket';

const KONDISI: Record<string, { label: string; warna: 'hijau' | 'kuning' | 'merah' }> = {
  Baik: { label: 'Baik', warna: 'hijau' },
  PerluPerhatian: { label: 'Perlu perhatian', warna: 'kuning' },
  Rusak: { label: 'Rusak', warna: 'merah' },
};

const HASIL_INSPEKSI = [
  { nilai: 'Lolos', label: 'Lolos' },
  { nilai: 'PerluPerhatian', label: 'Perlu perhatian' },
  { nilai: 'Gagal', label: 'Gagal' },
] as const;

function bulanTahun(nilai: string): string {
  return new Date(nilai).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
}

interface AksiCepat {
  label: string;
  ikon: NamaIkon3D;
  tint: TintIkon;
  href?: string;
  onClick?: () => void;
  /** Alasan aksi tidak tersedia; aksi tampil redup dan tidak dapat diketuk. */
  tidakTersedia?: string;
}

/**
 * Isi lembar "Aset ditemukan" (DESIGN §36.6 layar 14, PRD 10): aset dan kondisinya,
 * tiket terbuka milik teknisi, dan aksi cepat sesuai izin (Mulai kerja, Inspeksi,
 * Lapor, Riwayat). Dipakai di kamera pindai dan tab Aset.
 */
export function IsiAsetDitemukan({
  aset,
  luring = false,
  chip = 'Aset ditemukan',
}: {
  aset: AsetDitemukanTeknisi;
  /** Aset dari paket offline: aksi yang butuh server ditandai tidak tersedia. */
  luring?: boolean;
  /** Chip di atas nama; `null` untuk menyembunyikan (mis. dibuka dari daftar aset). */
  chip?: string | null;
}) {
  const { mulai, memproses } = useAksiTiket();
  const [inspeksiBuka, setInspeksiBuka] = useState(false);
  const ikon = ikonKategori(aset.Kategori ?? aset.Nama);
  const kondisi = aset.Kondisi ? KONDISI[aset.Kondisi] : undefined;
  const tiket = aset.TiketSaya;
  const sedangDikerjakan = tiket ? STATUS_SEDANG_DIKERJAKAN.includes(tiket.Status) : false;

  const aksi: AksiCepat[] = [
    {
      label: 'Mulai kerja',
      ikon: 'hammer_and_wrench',
      tint: 'oranye',
      onClick: tiket ? () => void mulai(tiket) : undefined,
      tidakTersedia: tiket ? undefined : 'Tidak ada tiket untukmu di aset ini',
    },
    {
      label: 'Inspeksi',
      ikon: 'memo',
      tint: 'kuning',
      onClick: aset.Inspeksi ? () => setInspeksiBuka(true) : undefined,
      tidakTersedia: aset.Inspeksi ? (luring ? 'Butuh sinyal' : undefined) : 'Tidak ada inspeksi terjadwal',
    },
    {
      label: 'Lapor',
      ikon: 'megaphone',
      tint: 'merah',
      href: aset.BolehLapor ? ruteLapangan.pelapor.laporDengan({ aset: aset.Id }) : undefined,
      tidakTersedia: aset.BolehLapor ? undefined : 'Tanpa izin melapor',
    },
    {
      label: 'Riwayat',
      ikon: 'card_index_dividers',
      tint: 'hijau',
      href: aset.BolehLihatRiwayat ? ruteLapangan.teknisi.riwayatAset(aset.Id) : undefined,
      tidakTersedia: aset.BolehLihatRiwayat ? undefined : 'Tanpa izin melihat riwayat',
    },
  ];

  return (
    <div className="flex flex-col gap-3.5">
      <div className="flex items-center gap-3.5">
        <span className="flex size-[92px] shrink-0 items-center justify-center rounded-[26px] gradien-ilustrasi-lapangan shadow-[inset_0_0_0_1.5px_var(--color-lapangan-biru-50)]">
          <Ikon3D nama={ikon.ikon} ukuran={66} segera />
        </span>
        <div className="min-w-0 flex-1">
          {chip && (
            <ChipStatus warna="hijau" ikon={CircleCheck}>
              {chip}
            </ChipStatus>
          )}
          <p className="mt-1.5 text-[22px] leading-tight font-bold tracking-[-0.01em]">{aset.Nama}</p>
          <p className="truncate text-sm text-lapangan-teks-3">
            {[aset.KodeAset, teksLokasi(aset.Lokasi)].filter(Boolean).join(' · ')}
          </p>
        </div>
      </div>

      {(kondisi || aset.GaransiBerakhirPada) && (
        <div className="flex flex-wrap gap-2">
          {kondisi && (
            <ChipStatus warna={kondisi.warna} ikon={kondisi.warna === 'hijau' ? CircleCheck : CircleAlert}>
              Kondisi: {kondisi.label}
            </ChipStatus>
          )}
          {aset.GaransiBerakhirPada && (
            <ChipStatus warna="abu">Garansi s.d. {bulanTahun(aset.GaransiBerakhirPada)}</ChipStatus>
          )}
        </div>
      )}

      {tiket && (
        <div className="flex items-center gap-3 rounded-[18px] bg-lapangan-oranye-50 p-3.5 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-oranye-100)]">
          <div className="min-w-0 flex-1">
            <p className="text-[13px] font-bold text-lapangan-oranye-teks">
              Ada tiket terbuka
              {tiket.Prioritas === 'Kritis' || tiket.Prioritas === 'Tinggi' ? ` · ${tiket.Prioritas}` : ''}
            </p>
            <b className="block text-[15px] leading-snug font-bold">{tiket.Judul}</b>
            <span className="block text-[13px] text-lapangan-teks-3">
              {tiket.Nomor}
              {tiket.BatasPada ? ` · target ${jamPendek(tiket.BatasPada)}` : ''}
            </span>
          </div>
          {sedangDikerjakan ? (
            <TombolLapangan
              ukuran="kecil"
              className="h-12 px-5 text-base"
              disabled={memproses === tiket.Id}
              onClick={() => void mulai(tiket)}
            >
              Lanjutkan
            </TombolLapangan>
          ) : (
            <TombolLapangan asChild ukuran="kecil" className="h-12 px-5 text-base">
              <Link href={ruteLapangan.teknisi.tugasDetail(tiket.Id)}>Buka</Link>
            </TombolLapangan>
          )}
        </div>
      )}

      <nav aria-label="Aksi cepat aset">
        <ul className="grid grid-cols-4 gap-1.5 py-1">
          {aksi.map((satu) => {
            const tersedia = !satu.tidakTersedia && (satu.href || satu.onClick);
            const isi = (
              <>
                <span
                  className={cn(
                    'flex size-[60px] items-center justify-center rounded-[18px]',
                    kelasTint(satu.tint),
                  )}
                >
                  <Ikon3D nama={satu.ikon} ukuran={40} />
                </span>
                <span className="text-[13px] leading-tight font-semibold">{satu.label}</span>
                {!tersedia && <span className="sr-only">: {satu.tidakTersedia}</span>}
              </>
            );
            const kelas = cn(
              'flex w-full flex-col items-center gap-2 rounded-2xl py-1 text-center text-lapangan-teks focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
              !tersedia && 'opacity-45',
            );
            return (
              <li key={satu.label}>
                {tersedia && satu.href ? (
                  <Link href={satu.href} className={kelas}>
                    {isi}
                  </Link>
                ) : (
                  <button
                    type="button"
                    className={kelas}
                    aria-disabled={!tersedia}
                    title={satu.tidakTersedia}
                    onClick={() => {
                      if (tersedia) satu.onClick?.();
                      else if (satu.tidakTersedia) toast(satu.tidakTersedia);
                    }}
                  >
                    {isi}
                  </button>
                )}
              </li>
            );
          })}
        </ul>
      </nav>

      <dl className="grid grid-cols-2 gap-2.5">
        <div className="rounded-[14px] bg-lapangan-latar px-3 py-2.5">
          <dt className="text-xs font-semibold text-lapangan-teks-3">Servis terakhir</dt>
          <dd className="text-sm font-bold">
            {aset.ServisTerakhirPada ? tanggalPendek(aset.ServisTerakhirPada) : 'Belum ada'}
          </dd>
        </div>
        <div className="rounded-[14px] bg-lapangan-latar px-3 py-2.5">
          <dt className="text-xs font-semibold text-lapangan-teks-3">
            {aset.MerekTipe ? 'Merek & tipe' : 'Kategori'}
          </dt>
          <dd className="truncate text-sm font-bold">{aset.MerekTipe ?? aset.Kategori ?? '—'}</dd>
        </div>
      </dl>

      {aset.Inspeksi && (
        <LembarInspeksi
          buka={inspeksiBuka}
          onBukaBerubah={setInspeksiBuka}
          inspeksi={aset.Inspeksi}
          namaAset={aset.Nama}
        />
      )}
    </div>
  );
}

function LembarInspeksi({
  buka,
  onBukaBerubah,
  inspeksi,
  namaAset,
}: {
  buka: boolean;
  onBukaBerubah: (buka: boolean) => void;
  inspeksi: NonNullable<AsetDitemukanTeknisi['Inspeksi']>;
  namaAset: string;
}) {
  const [hasil, setHasil] = useState<string | null>(null);
  const [temuan, setTemuan] = useState('');
  const [mengirim, setMengirim] = useState(false);

  const kirim = async () => {
    if (!hasil) return;
    setMengirim(true);
    try {
      await http.post(ruteLapangan.teknisi.laksanakanInspeksi(inspeksi.Id), {
        Hasil: hasil,
        Temuan: temuan.trim() || null,
      });
      toast.success(`Hasil inspeksi ${inspeksi.Nomor} tersimpan.`);
      onBukaBerubah(false);
      router.reload();
    } catch (galat) {
      const data: unknown = isAxiosError(galat) ? galat.response?.data : null;
      toast.error(
        data && typeof data === 'object' && 'pesan' in data && typeof data.pesan === 'string'
          ? data.pesan
          : 'Hasil inspeksi belum tersimpan. Coba lagi saat ada sinyal.',
      );
    } finally {
      setMengirim(false);
    }
  };

  return (
    <LembarBawah
      buka={buka}
      onBukaBerubah={onBukaBerubah}
      judul="Catat hasil inspeksi"
      deskripsi={`${inspeksi.Nomor} · ${namaAset}`}
      kaki={
        <TombolLapangan penuh disabled={!hasil || mengirim} onClick={() => void kirim()}>
          {mengirim ? 'Menyimpan…' : 'Simpan hasil'}
        </TombolLapangan>
      }
    >
      <div role="radiogroup" aria-label="Hasil inspeksi" className="grid grid-cols-3 gap-2">
        {HASIL_INSPEKSI.map((satu) => (
          <button
            key={satu.nilai}
            type="button"
            role="radio"
            aria-checked={hasil === satu.nilai}
            onClick={() => setHasil(satu.nilai)}
            className={cn(
              'h-[54px] rounded-[14px] text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
              hasil === satu.nilai
                ? 'bg-lapangan-navy-800 text-white'
                : 'bg-white text-lapangan-teks-2 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
            )}
          >
            {satu.label}
          </button>
        ))}
      </div>
      <IsianTiket label="Temuan (bila ada)">
        <AreaTiket
          value={temuan}
          onChange={(event) => setTemuan(event.target.value)}
          placeholder="Mis. segel APAR rusak"
        />
      </IsianTiket>
    </LembarBawah>
  );
}
