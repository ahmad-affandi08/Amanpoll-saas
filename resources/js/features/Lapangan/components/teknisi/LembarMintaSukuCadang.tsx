import { router } from '@inertiajs/react';
import { isAxiosError } from 'axios';
import { Info, LoaderCircle, Minus, Plus, Search, Warehouse, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { http } from '@/lib/http';
import { cn } from '@/lib/utils';
import { ruteLapangan } from '@/features/Lapangan/api';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { WadahIkon3D } from '@/features/Lapangan/components/Ikon3D';
import { IsianTiket, MasukanTiket } from '@/features/Lapangan/components/IsianTiket';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import type { SukuCadangDicariTeknisi } from '@/features/Lapangan/types';

interface Pilihan {
  sukuCadang: SukuCadangDicariTeknisi;
  gudangId: string;
  jumlah: number;
}

interface PropsLembarMintaSukuCadang {
  buka: boolean;
  onBukaBerubah: (buka: boolean) => void;
  perintahKerjaId: string;
  daring: boolean;
}

const JEDA_CARI_MS = 300;

function angka(nilai: number): string {
  return Number.isInteger(nilai)
    ? String(nilai)
    : nilai.toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

/** Pesan galat dari endpoint domain (validasi 422 atau aturan bisnis `pesan`). */
function pesanGalat(galat: unknown): string {
  if (isAxiosError(galat)) {
    const data: unknown = galat.response?.data;
    if (data && typeof data === 'object') {
      if ('pesan' in data && typeof data.pesan === 'string') return data.pesan;
      if ('errors' in data && data.errors && typeof data.errors === 'object') {
        const pertama = Object.values(data.errors)[0];
        if (Array.isArray(pertama) && typeof pertama[0] === 'string') return pertama[0];
      }
    }
    if (!galat.response) return 'Tidak ada sinyal. Coba lagi saat online.';
  }
  return 'Permintaan belum terkirim. Coba lagi.';
}

/**
 * Lembar "Minta suku cadang" (DESIGN §36.6 layar 09): cari, lihat stok bersih per gudang,
 * pilih gudang ambil dan jumlah. Dikirim ke endpoint reservasi dasbor (policy `operate`):
 * stok hanya ditahan, baru berkurang saat petugas gudang menyerahkan barang.
 */
export function LembarMintaSukuCadang({
  buka,
  onBukaBerubah,
  perintahKerjaId,
  daring,
}: PropsLembarMintaSukuCadang) {
  const [kata, setKata] = useState('');
  const [hasil, setHasil] = useState<SukuCadangDicariTeknisi[] | null>(null);
  const [memuat, setMemuat] = useState(false);
  const [galatCari, setGalatCari] = useState<string | null>(null);
  const [pilihan, setPilihan] = useState<Record<string, Pilihan>>({});
  const [mengirim, setMengirim] = useState(false);
  const permintaanKe = useRef(0);

  useEffect(() => {
    if (!buka || !daring) return;
    const ke = ++permintaanKe.current;
    setMemuat(true);
    const pewaktu = window.setTimeout(() => {
      http
        .get<{ data: SukuCadangDicariTeknisi[] }>(ruteLapangan.teknisi.cariSukuCadang(perintahKerjaId), {
          params: { cari: kata },
        })
        .then(({ data }) => {
          if (ke !== permintaanKe.current) return;
          setHasil(data.data);
          setGalatCari(null);
        })
        .catch((galat: unknown) => {
          if (ke === permintaanKe.current) setGalatCari(pesanGalat(galat));
        })
        .finally(() => {
          if (ke === permintaanKe.current) setMemuat(false);
        });
    }, JEDA_CARI_MS);
    return () => window.clearTimeout(pewaktu);
  }, [buka, daring, kata, perintahKerjaId]);

  const daftarPilihan = Object.values(pilihan);

  const pilih = (sukuCadang: SukuCadangDicariTeknisi, gudangId?: string) => {
    const gudang = gudangId ?? sukuCadang.Stok.find((satu) => satu.TersediaBersih > 0)?.GudangId;
    if (!gudang) return;
    setPilihan((lama) => ({
      ...lama,
      [sukuCadang.Id]: { sukuCadang, gudangId: gudang, jumlah: lama[sukuCadang.Id]?.jumlah ?? 1 },
    }));
  };

  const ubahJumlah = (sukuCadangId: string, selisih: number) => {
    setPilihan((lama) => {
      const satu = lama[sukuCadangId];
      if (!satu) return lama;
      const stok = satu.sukuCadang.Stok.find((s) => s.GudangId === satu.gudangId)?.TersediaBersih ?? 0;
      const jumlah = satu.jumlah + selisih;
      if (jumlah <= 0) {
        const { [sukuCadangId]: _dibuang, ...sisa } = lama;
        return sisa;
      }
      return { ...lama, [sukuCadangId]: { ...satu, jumlah: Math.min(jumlah, Math.max(stok, 1)) } };
    });
  };

  const kirim = async () => {
    setMengirim(true);
    let berhasil = 0;
    for (const satu of daftarPilihan) {
      try {
        await http.post(ruteLapangan.teknisi.reservasiSukuCadang(perintahKerjaId), {
          GudangId: satu.gudangId,
          SukuCadangId: satu.sukuCadang.Id,
          Jumlah: satu.jumlah,
        });
        berhasil++;
        setPilihan((lama) => {
          const { [satu.sukuCadang.Id]: _terkirim, ...sisa } = lama;
          return sisa;
        });
      } catch (galat) {
        toast.error(`${satu.sukuCadang.Nama}: ${pesanGalat(galat)}`);
      }
    }
    setMengirim(false);
    if (berhasil > 0) {
      toast.success(`${berhasil} permintaan dikirim ke gudang.`);
      router.reload({ only: ['permintaanSukuCadang'] });
      if (berhasil === daftarPilihan.length) onBukaBerubah(false);
    }
  };

  const jumlahItem = daftarPilihan.length;

  return (
    <LembarBawah
      buka={buka}
      onBukaBerubah={onBukaBerubah}
      judul="Minta suku cadang"
      kaki={
        daring ? (
          <TombolLapangan penuh disabled={jumlahItem === 0 || mengirim} onClick={() => void kirim()}>
            {mengirim
              ? 'Mengirim…'
              : jumlahItem === 0
                ? 'Pilih suku cadang'
                : `Minta ke gudang · ${jumlahItem} item`}
          </TombolLapangan>
        ) : undefined
      }
    >
      {!daring ? (
        <IlustrasiMomen
          ringkas
          jenis="offline"
          judul="Butuh sinyal"
          teks="Stok gudang harus dicek langsung. Lanjutkan pekerjaan lain dulu, lalu minta suku cadang saat online."
        />
      ) : (
        <>
          <IsianTiket
            label="Cari suku cadang"
            ikon={Search}
            kanan={
              kata ? (
                <button
                  type="button"
                  onClick={() => setKata('')}
                  aria-label="Kosongkan pencarian"
                  className="flex size-11 items-center justify-center rounded-full text-lapangan-teks-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
                >
                  <X aria-hidden className="size-5" />
                </button>
              ) : undefined
            }
          >
            <MasukanTiket
              value={kata}
              onChange={(event) => setKata(event.target.value)}
              placeholder="Nama, kode, atau nomor bagian"
              enterKeyHint="search"
              autoComplete="off"
            />
          </IsianTiket>

          <p
            aria-live="polite"
            className="flex items-center gap-2 px-0.5 text-[13px] font-semibold text-lapangan-teks-3"
          >
            {memuat && <LoaderCircle aria-hidden className="size-4 animate-spin" />}
            {galatCari ?? (hasil ? `${hasil.length} hasil · stok per gudang` : 'Mencari…')}
          </p>

          {memuat && !hasil ? (
            <div className="flex flex-col gap-3" aria-hidden>
              {[0, 1].map((i) => (
                <div key={i} className="h-32 animate-pulse rounded-[18px] bg-lapangan-latar" />
              ))}
            </div>
          ) : hasil && hasil.length === 0 ? (
            <IlustrasiMomen
              ringkas
              ikon="nut_and_bolt"
              judul="Tidak ditemukan"
              teks="Coba nama lain atau nomor bagian yang tertera di barangnya."
            />
          ) : (
            <ul className="flex flex-col gap-3">
              {(hasil ?? []).map((satu) => {
                const dipilih = pilihan[satu.Id];
                const adaStok = satu.Stok.some((stok) => stok.TersediaBersih > 0);
                const gudangTerpilih = satu.Stok.find((stok) => stok.GudangId === dipilih?.gudangId);

                return (
                  <li
                    key={satu.Id}
                    className={cn(
                      'rounded-[18px] p-3.5',
                      dipilih
                        ? 'bg-lapangan-biru-50/50 shadow-[inset_0_0_0_2px_var(--color-lapangan-biru-500)]'
                        : 'shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
                    )}
                  >
                    <div className="flex items-start gap-3">
                      <WadahIkon3D nama="electric_plug" tint="kuning" />
                      <div className="min-w-0 flex-1">
                        <b className="block text-[15px] leading-snug font-bold">{satu.Nama}</b>
                        <span className="block text-[13px] text-lapangan-teks-3">
                          {[satu.Kode, satu.NomorBagian].filter(Boolean).join(' · ')}
                        </span>
                      </div>
                      {!dipilih && (
                        <TombolLapangan
                          ragam="garis"
                          ukuran="kecil"
                          disabled={!adaStok}
                          onClick={() => pilih(satu)}
                          aria-label={`Tambah ${satu.Nama}`}
                        >
                          {adaStok ? 'Tambah' : 'Habis'}
                        </TombolLapangan>
                      )}
                    </div>
                    {satu.Stok.length > 0 ? (
                      <div
                        role="radiogroup"
                        aria-label={`Gudang untuk ${satu.Nama}`}
                        className="mt-3 flex flex-wrap gap-1.5"
                      >
                        {satu.Stok.map((stok) => {
                          const habis = stok.TersediaBersih <= 0;
                          const aktif = dipilih?.gudangId === stok.GudangId;
                          return (
                            <button
                              key={stok.GudangId}
                              type="button"
                              role="radio"
                              aria-checked={aktif}
                              disabled={habis}
                              onClick={() => pilih(satu, stok.GudangId)}
                              className={cn(
                                'inline-flex h-11 items-center gap-1.5 rounded-[10px] px-2.5 text-[12.5px] font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
                                aktif
                                  ? 'bg-lapangan-navy-800 text-white'
                                  : habis
                                    ? 'bg-white text-lapangan-merah-700 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]'
                                    : 'bg-white text-lapangan-teks-2 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
                              )}
                            >
                              <Warehouse aria-hidden className="size-[15px]" />
                              {stok.NamaGudang}
                              <b
                                className={cn(
                                  'font-bold',
                                  aktif
                                    ? 'text-white'
                                    : habis
                                      ? 'text-lapangan-merah-700'
                                      : 'text-lapangan-teks',
                                )}
                              >
                                {habis ? 'habis' : angka(stok.TersediaBersih)}
                              </b>
                            </button>
                          );
                        })}
                      </div>
                    ) : (
                      <p className="mt-2 text-[13px] font-semibold text-lapangan-merah-700">
                        Belum ada stok di gudang mana pun.
                      </p>
                    )}
                    {dipilih && (
                      <div className="mt-3 flex items-center justify-between gap-3">
                        <span className="text-sm font-semibold text-lapangan-teks-2">
                          Ambil di {gudangTerpilih?.NamaGudang ?? 'gudang'}
                        </span>
                        <span className="inline-flex h-11 items-center rounded-xl bg-lapangan-latar">
                          <button
                            type="button"
                            onClick={() => ubahJumlah(satu.Id, -1)}
                            aria-label={dipilih.jumlah === 1 ? `Batalkan ${satu.Nama}` : 'Kurangi jumlah'}
                            className="flex size-11 items-center justify-center rounded-xl text-lapangan-navy-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
                          >
                            <Minus aria-hidden className="size-5" />
                          </button>
                          <b
                            aria-live="polite"
                            className="min-w-8 text-center text-[17px] font-bold tabular-nums"
                          >
                            {dipilih.jumlah}
                          </b>
                          <button
                            type="button"
                            onClick={() => ubahJumlah(satu.Id, 1)}
                            disabled={dipilih.jumlah >= (gudangTerpilih?.TersediaBersih ?? 0)}
                            aria-label="Tambah jumlah"
                            className="flex size-11 items-center justify-center rounded-xl bg-lapangan-navy-800 text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500 disabled:opacity-40"
                          >
                            <Plus aria-hidden className="size-5" />
                          </button>
                        </span>
                      </div>
                    )}
                  </li>
                );
              })}
            </ul>
          )}

          <p className="flex items-start gap-2.5 text-[13px] leading-normal text-lapangan-teks-2">
            <Info aria-hidden className="mt-px size-[18px] shrink-0 text-lapangan-biru-600" />
            Petugas gudang yang menyiapkan barang. Stok baru berkurang setelah barang diserahkan.
          </p>
        </>
      )}
    </LembarBawah>
  );
}
