import { Link, router, usePage } from '@inertiajs/react';
import { ChevronRight, CircleCheck, MapPin, ScanLine, Search, Send } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { penyimpananTersedia } from '@/lib/penyimpanan-offline';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { Ikon3D, WadahIkon3D } from '@/features/Lapangan/components/Ikon3D';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { AreaTiket, IsianTiket } from '@/features/Lapangan/components/IsianTiket';
import { JudulBagian, Kartu, KartuApung } from '@/features/Lapangan/components/Kartu';
import { PemindaiQr } from '@/features/Lapangan/components/PemindaiQr';
import { PerhentianAppbar, type LangkahPerhentian } from '@/features/Lapangan/components/Perhentian';
import { Sobekan } from '@/features/Lapangan/components/Tiket';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { kodeDariPindaian } from '@/features/Lapangan/components/pelapor/pindai';
import { BarisPilihAset, ikonAset, kondisiAset } from '@/features/Lapangan/components/pelapor/BarisAset';
import { FotoPilihan, usePratinjauFoto } from '@/features/Lapangan/components/pelapor/FotoPilihan';
import { LayarTerkirim } from '@/features/Lapangan/components/pelapor/LayarTerkirim';
import { LembarLokasi } from '@/features/Lapangan/components/pelapor/LembarLokasi';
import {
  PILIHAN_URGENSI,
  pilihanMasalah,
  susunDeskripsi,
  susunJudul,
} from '@/features/Lapangan/components/pelapor/masalah';
import { PilihanChip } from '@/features/Lapangan/components/pelapor/PilihanChip';
import { namaDepan, tampilanStatus } from '@/features/Lapangan/components/pelapor/status';
import { TombolDikte } from '@/features/Lapangan/components/pelapor/TombolDikte';
import { waktuLengkap } from '@/features/Lapangan/components/pelapor/waktu';
import { ikonKategori } from '@/features/Lapangan/ikon';
import type {
  AsetPelapor,
  KategoriLaporan,
  LokasiPelapor,
  PropsLaporPelapor,
  UrgensiLaporan,
} from '@/features/Lapangan/types';

/** Foto yang boleh disertakan; sama dengan batas server (`LapanganPelaporLaporController::MAKS_FOTO`). */
const MAKS_FOTO = 4;

type Langkah = 'alat' | 'ditemukan' | 'masalah' | 'tinjau' | 'tersimpan';

interface MuatanLaporan extends Record<string, string> {
  KategoriKeluhanId: string;
  AsetId: string;
  LokasiId: string;
  Judul: string;
  Deskripsi: string;
  Urgensi: UrgensiLaporan;
  KunciLaporan: string;
}

interface KerangkaLangkah {
  judul: string;
  subjudul: string;
  kembali: string | (() => void);
  ikonKembali?: 'panah' | 'tutup';
  panjang: boolean;
  indeks: number;
}

function langkahAppbar(indeks: number): LangkahPerhentian[] {
  return ['Alat', 'Masalah', 'Kirim'].map((label, i) => ({
    label,
    keadaan: i < indeks ? 'lewat' : i === indeks ? 'kini' : 'nanti',
  }));
}

/** Kategori keluhan yang paling cocok dengan jenis aset (ikon 3D yang sama). */
function tebakKategori(aset: AsetPelapor, kategori: KategoriLaporan[]): string | null {
  const ikon = ikonAset(aset).ikon;
  if (ikon === 'toolbox') return null;
  return kategori.find((satu) => ikonKategori(satu.Nama).ikon === ikon)?.Id ?? null;
}

/**
 * Lapor kerusakan dalam tiga langkah (DESIGN.md 36.7 layar 04–08): pilih alat (atau
 * pindai QR, atau lapor lokasi saja), apa masalahnya, tinjau & kirim. Tanpa sinyal,
 * laporan masuk antrean offline dan dikirim otomatis; `KunciLaporan` membuat kiriman
 * yang terulang tidak menjadi dua keluhan.
 */
export default function LaporPelapor() {
  const { props } = usePage<PropsLaporPelapor>();
  const [langkah, setLangkah] = useState<Langkah>(props.asetTerpilih ? 'ditemukan' : 'alat');
  const [aset, setAset] = useState<AsetPelapor | null>(props.asetTerpilih);
  const [dariPindai, setDariPindai] = useState(false);
  const [kategoriId, setKategoriId] = useState<string | null>(
    props.kategoriAwal && props.kategori.some((k) => k.Id === props.kategoriAwal)
      ? props.kategoriAwal
      : props.asetTerpilih
        ? tebakKategori(props.asetTerpilih, props.kategori)
        : null,
  );
  const [masalah, setMasalah] = useState<string | null>(null);
  const [cerita, setCerita] = useState('');
  const [urgensi, setUrgensi] = useState<UrgensiLaporan>('MenggangguKerja');
  const [foto, setFoto] = useState<File[]>([]);
  const [pindai, setPindai] = useState(false);
  const [bukaLokasi, setBukaLokasi] = useState(false);
  const [galat, setGalat] = useState<Record<string, string>>({});
  const [kunciLaporan] = useState(() => crypto.randomUUID());
  const [dikirimPada, setDikirimPada] = useState<string | null>(null);
  const menungguPindai = useRef(false);

  // Hasil pindai di langkah 1 kembali sebagai prop `asetTerpilih` dari server.
  useEffect(() => {
    if (!menungguPindai.current) return;
    menungguPindai.current = false;
    if (props.asetTerpilih) {
      setAset(props.asetTerpilih);
      setDariPindai(true);
      setKategoriId((sekarang) => sekarang ?? tebakKategori(props.asetTerpilih!, props.kategori));
      setLangkah('ditemukan');
    }
  }, [props.asetTerpilih, props.asetTidakDitemukan]);

  const lokasi: LokasiPelapor | null = props.lokasi;
  const subjek = aset?.Nama ?? lokasi?.Label ?? 'lokasi ini';
  const kategoriTerpilih = props.kategori.find((satu) => satu.Id === kategoriId) ?? null;
  const pilihanKategori = aset ? props.kategori : props.kategori.filter((satu) => !satu.AsetWajib);

  const muatan: MuatanLaporan = {
    KategoriKeluhanId: kategoriId ?? '',
    AsetId: aset?.Id ?? '',
    LokasiId: aset?.LokasiId ?? lokasi?.Id ?? '',
    Judul: susunJudul(subjek, masalah, cerita, aset === null),
    Deskripsi: susunDeskripsi(masalah, cerita),
    Urgensi: urgensi,
    KunciLaporan: kunciLaporan,
  };

  const gantiLokasi = (baru: LokasiPelapor) => {
    setBukaLokasi(false);
    if (baru.Id === lokasi?.Id) return;
    router.get(
      ruteLapangan.pelapor.laporDengan({ lokasi: baru.Id, kategori: kategoriId ?? undefined }),
      {},
      { preserveState: true, preserveScroll: true, replace: true, only: ['lokasi', 'aset'] },
    );
  };

  const hasilPindai = (teks: string) => {
    setPindai(false);
    menungguPindai.current = true;
    router.get(
      ruteLapangan.pelapor.laporDengan({ kode: kodeDariPindaian(teks), kategori: kategoriId ?? undefined }),
      {},
      {
        preserveState: true,
        replace: true,
        only: ['asetTerpilih', 'asetTidakDitemukan', 'lokasi', 'aset'],
        onNetworkError: () => {
          menungguPindai.current = false;
          setGalat({
            Pindai: 'Butuh sinyal untuk mencari alat dari QR. Pilih dari daftar atau laporkan lokasi saja.',
          });
          return false;
        },
      },
    );
  };

  const pilihAset = (dipilih: AsetPelapor) => {
    setAset(dipilih);
    setDariPindai(false);
    setKategoriId((sekarang) => sekarang ?? tebakKategori(dipilih, props.kategori));
    setLangkah('ditemukan');
  };

  const laporLokasiSaja = () => {
    if (!lokasi) {
      setBukaLokasi(true);
      return;
    }
    setAset(null);
    if (kategoriTerpilih?.AsetWajib) setKategoriId(null);
    setLangkah('masalah');
  };

  const masalahLengkap = Boolean(kategoriId) && (Boolean(masalah) || cerita.trim() !== '');
  const [cobaLanjut, setCobaLanjut] = useState(false);

  const kerangka = ((): KerangkaLangkah => {
    switch (langkah) {
      case 'alat':
        return {
          judul: 'Lapor kerusakan',
          subjudul: 'Langkah 1 dari 3 · Pilih alat',
          ikonKembali: 'tutup',
          kembali: ruteLapangan.pelapor.beranda,
          panjang: true,
          indeks: 0,
        };
      case 'ditemukan':
        return {
          judul: 'Alat ditemukan',
          subjudul: 'Langkah 1 dari 3 · Pilih alat',
          kembali: () => setLangkah('alat'),
          panjang: true,
          indeks: 0,
        };
      case 'masalah':
        return {
          judul: 'Apa masalahnya?',
          subjudul: `Langkah 2 dari 3 · ${subjek}`,
          kembali: () => setLangkah(aset ? 'ditemukan' : 'alat'),
          panjang: false,
          indeks: 1,
        };
      default:
        return {
          judul: 'Tinjau laporan',
          subjudul: 'Langkah 3 dari 3 · Cek sebelum kirim',
          kembali: () => setLangkah('masalah'),
          panjang: false,
          indeks: 2,
        };
    }
  })();

  if (langkah === 'tersimpan') {
    return (
      <KerangkaLapangan
        judulHalaman="Laporan tersimpan"
        varian="polos"
        latar="putih"
        mode="Pelapor"
        bilahAksi={
          <>
            <TombolLapangan asChild ragam="garis" className="px-[18px]">
              <Link href={ruteLapangan.pelapor.beranda}>Beranda</Link>
            </TombolLapangan>
            <TombolLapangan asChild className="flex-1">
              <Link href={ruteLapangan.pelapor.laporan}>Laporan Saya</Link>
            </TombolLapangan>
          </>
        }
      >
        <LayarTerkirim
          nama={namaDepan(props.kontak.Nama) || 'kamu'}
          nomor={null}
          dikirimPada={dikirimPada}
          targetDitinjau={null}
          subjek={subjek}
          ikonSubjek={aset ? ikonAset(aset).ikon : 'round_pushpin'}
        />
      </KerangkaLapangan>
    );
  }

  let bilahAksi: ReactNode;
  if (langkah === 'ditemukan') {
    bilahAksi = (
      <>
        <TombolLapangan
          ragam="garis"
          className="px-4"
          onClick={() => (dariPindai ? setPindai(true) : setLangkah('alat'))}
        >
          {dariPindai && <ScanLine aria-hidden />}
          {dariPindai ? 'Pindai ulang' : 'Ganti alat'}
        </TombolLapangan>
        <TombolLapangan className="flex-1" onClick={() => setLangkah('masalah')}>
          Ya, lanjut
        </TombolLapangan>
      </>
    );
  } else if (langkah === 'masalah') {
    bilahAksi = (
      <TombolLapangan
        penuh
        onClick={() => {
          setCobaLanjut(true);
          if (masalahLengkap) setLangkah('tinjau');
        }}
      >
        Lanjut
      </TombolLapangan>
    );
  } else if (langkah === 'tinjau') {
    bilahAksi = (
      <TombolKirim
        muatan={muatan}
        foto={foto}
        onGalat={(errors) => setGalat(errors)}
        onTersimpanOffline={() => {
          setDikirimPada(new Date().toISOString());
          setLangkah('tersimpan');
        }}
      />
    );
  }

  return (
    <KerangkaLapangan
      judulHalaman={kerangka.judul}
      varian="appbar"
      mode="Pelapor"
      judul={kerangka.judul}
      subjudul={kerangka.subjudul}
      kembali={kerangka.kembali}
      ikonKembali={kerangka.ikonKembali}
      panjang={kerangka.panjang}
      langkah={<PerhentianAppbar langkah={langkahAppbar(kerangka.indeks)} label="Langkah lapor" />}
      bilahAksi={bilahAksi}
      navBawah={false}
    >
      {langkah === 'alat' && (
        <LangkahAlat
          props={props}
          galatPindai={
            galat.Pindai ??
            (props.asetTidakDitemukan
              ? 'Alat tidak ditemukan di areamu. Pilih dari daftar atau laporkan lokasi saja.'
              : null)
          }
          onPindai={() => {
            setGalat({});
            setPindai(true);
          }}
          onGantiLokasi={() => setBukaLokasi(true)}
          onPilih={pilihAset}
          onLokasiSaja={laporLokasiSaja}
        />
      )}
      {langkah === 'ditemukan' && aset && (
        <LangkahDitemukan aset={aset} dariPindai={dariPindai} onTetapLapor={() => setLangkah('masalah')} />
      )}
      {langkah === 'masalah' && (
        <LangkahMasalah
          kategori={pilihanKategori}
          kategoriId={kategoriId}
          onKategori={(id) => {
            setKategoriId(id);
            setMasalah(null);
          }}
          petunjukMasalah={[kategoriTerpilih?.Nama, aset?.Kategori, aset?.Nama]}
          masalah={masalah}
          onMasalah={setMasalah}
          cerita={cerita}
          onCerita={setCerita}
          urgensi={urgensi}
          onUrgensi={setUrgensi}
          foto={foto}
          onFoto={setFoto}
          tampilGalat={cobaLanjut && !masalahLengkap}
        />
      )}
      {langkah === 'tinjau' && (
        <LangkahTinjau
          aset={aset}
          lokasi={lokasi}
          kategori={kategoriTerpilih}
          masalah={masalah}
          cerita={cerita}
          judul={muatan.Judul}
          urgensi={urgensi}
          foto={foto}
          kontak={props.kontak}
          galat={galat}
          onUbahAlat={() => setLangkah('alat')}
          onUbahMasalah={() => setLangkah('masalah')}
        />
      )}

      <LembarLokasi
        buka={bukaLokasi}
        onBukaBerubah={setBukaLokasi}
        pilihan={props.pilihanLokasi}
        terpilihId={lokasi?.Id ?? null}
        onPilih={gantiLokasi}
      />
      {pindai && (
        <PemindaiQr
          judul="Pindai QR alat"
          petunjuk="Arahkan ke stiker QR di badan alat"
          labelKetik="Ketik kode alat"
          onTutup={() => setPindai(false)}
          onHasil={hasilPindai}
        />
      )}
    </KerangkaLapangan>
  );
}

interface PropsLangkahAlat {
  props: PropsLaporPelapor;
  galatPindai: string | null;
  onPindai: () => void;
  onGantiLokasi: () => void;
  onPilih: (aset: AsetPelapor) => void;
  onLokasiSaja: () => void;
}

/** Langkah 1 (layar 04): pindai QR, lokasi + cari, daftar aset di lokasi, lapor lokasi saja. */
function LangkahAlat({
  props,
  galatPindai,
  onPindai,
  onGantiLokasi,
  onPilih,
  onLokasiSaja,
}: PropsLangkahAlat) {
  const [cari, setCari] = useState('');
  const kata = cari.trim().toLowerCase();
  const tersaring = useMemo(
    () =>
      kata
        ? props.aset.filter((satu) =>
            [satu.Nama, satu.KodeAset, satu.Kategori, satu.LokasiNama].some((teks) =>
              teks?.toLowerCase().includes(kata),
            ),
          )
        : props.aset,
    [props.aset, kata],
  );

  const kartuLokasiSaja = (
    <button
      type="button"
      onClick={onLokasiSaja}
      className="flex min-h-14 items-center gap-3 rounded-[18px] bg-white/60 px-3.5 py-2.5 text-left outline-2 -outline-offset-2 outline-lapangan-teks-3/35 outline-dashed focus-visible:outline-solid focus-visible:outline-lapangan-biru-500"
    >
      <Ikon3D nama="round_pushpin" ukuran={34} />
      <span className="min-w-0 flex-1">
        <b className="block text-[14.5px] font-bold">Tidak tahu alatnya?</b>
        <span className="text-[13.5px] font-bold text-lapangan-oranye-teks">Laporkan lokasi saja</span>
      </span>
      <ChevronRight aria-hidden className="size-[18px] shrink-0 text-lapangan-teks-3" />
    </button>
  );

  return (
    <>
      {props.bolehLihatAset ? (
        <KartuApung>
          <button
            type="button"
            onClick={onPindai}
            className="flex w-full items-center gap-3.5 rounded-[20px] p-4 text-left focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50"
          >
            <WadahIkon3D nama="camera_with_flash" tint="oranye" ukuran="besar" className="rounded-[20px]" />
            <span className="min-w-0 flex-1">
              <b className="block text-lg leading-tight font-bold tracking-[-0.01em]">Pindai QR alat</b>
              <span className="block text-[13px] leading-[1.35] font-medium text-lapangan-teks-3">
                Stiker QR ada di badan alat. Cara paling cepat.
              </span>
            </span>
            <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-lapangan-oranye-700 text-white shadow-lapangan-oranye">
              <ScanLine aria-hidden className="size-5" />
            </span>
          </button>
        </KartuApung>
      ) : (
        <KartuApung pad>
          <IlustrasiMomen
            ringkas
            jenis="tanpa-izin"
            judul="Daftar alat tidak tersedia untukmu"
            teks="Kamu tetap bisa melapor. Pilih lokasinya, lalu ceritakan masalahnya."
            className="py-2"
          />
        </KartuApung>
      )}

      {galatPindai && <PitaInfo nada="merah" ikon="warning" judul={galatPindai} />}

      <Kartu className="overflow-hidden">
        <div className="flex items-center gap-3 px-4 py-3">
          <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-lapangan-biru-50 text-lapangan-biru-600">
            <MapPin aria-hidden className="size-[18px]" />
          </span>
          <div className="min-w-0 flex-1">
            <span className="block text-xs font-semibold text-lapangan-teks-3">Lokasi</span>
            <span
              className={cn(
                'block truncate text-base font-bold',
                !props.lokasi && 'font-medium text-lapangan-teks-3',
              )}
            >
              {props.lokasi?.Label ?? 'Belum dipilih'}
            </span>
          </div>
          <button
            type="button"
            onClick={onGantiLokasi}
            className="-my-2 min-h-11 px-1 text-sm font-bold text-lapangan-oranye-teks focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
          >
            {props.lokasi ? 'Ganti' : 'Pilih'}
          </button>
        </div>
        {props.bolehLihatAset && props.lokasi && (
          <>
            <div className="mx-4 h-[1.5px] bg-lapangan-garis-2" />
            <label className="flex items-center gap-3 px-4 py-3">
              <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-lapangan-biru-50 text-lapangan-biru-600">
                <Search aria-hidden className="size-[18px]" />
              </span>
              <span className="min-w-0 flex-1">
                <span className="block text-xs font-semibold text-lapangan-teks-3">Cari alat</span>
                <input
                  type="search"
                  value={cari}
                  onChange={(event) => setCari(event.target.value)}
                  placeholder="Contoh: printer, AC, lampu"
                  enterKeyHint="search"
                  className="block w-full bg-transparent p-0 text-base font-bold text-lapangan-teks outline-none placeholder:font-medium placeholder:text-lapangan-teks-3"
                />
              </span>
            </label>
          </>
        )}
      </Kartu>

      {props.bolehLihatAset && props.lokasi && (
        <>
          <JudulBagian
            judul={`Aset di ${props.lokasi.Nama}`}
            kanan={
              <span className="text-[13px] font-semibold text-lapangan-teks-3">{props.aset.length} aset</span>
            }
          />
          {tersaring.length > 0 ? (
            <Kartu className="overflow-hidden">
              {tersaring.map((satu) => (
                <BarisPilihAset key={satu.Id} aset={satu} onPilih={onPilih} />
              ))}
            </Kartu>
          ) : (
            <Kartu>
              <IlustrasiMomen
                ringkas
                ikon={kata ? 'magnifying_glass_tilted_left' : 'office_building'}
                judul={kata ? 'Alat tidak ditemukan' : 'Belum ada alat terdaftar di sini'}
                teks={
                  kata
                    ? 'Coba kata lain, atau laporkan lokasinya saja.'
                    : 'Laporkan lokasinya saja; tim teknik akan mencari alatnya.'
                }
              />
            </Kartu>
          )}
        </>
      )}

      {!props.lokasi && (
        <Kartu>
          <IlustrasiMomen
            ringkas
            ikon="round_pushpin"
            judul="Pilih lokasimu dulu"
            teks="Alat yang bisa dilaporkan tampil sesuai lokasi."
            aksi={
              <TombolLapangan ragam="navy" ukuran="kecil" onClick={onGantiLokasi}>
                Pilih lokasi
              </TombolLapangan>
            }
          />
        </Kartu>
      )}

      {kartuLokasiSaja}
    </>
  );
}

/** Langkah 1b (layar 05): aset terisi dari QR/daftar, dengan pencegahan laporan ganda. */
function LangkahDitemukan({
  aset,
  dariPindai,
  onTetapLapor,
}: {
  aset: AsetPelapor;
  dariPindai: boolean;
  onTetapLapor: () => void;
}) {
  const ikon = ikonAset(aset);
  const kondisi = kondisiAset(aset);
  const terbuka = aset.LaporanTerbuka[0];

  return (
    <>
      <article className="relative z-10 -mt-14 rounded-[20px] bg-white shadow-lapangan-apung">
        <div className="px-4 pt-[18px] pb-3 text-center">
          <ChipStatus warna="hijau" ikon={ScanLine}>
            {dariPindai ? 'QR terbaca' : 'Alat terpilih'}
          </ChipStatus>
          <div className="mt-2.5 flex justify-center">
            <span className="gradien-ilustrasi-lapangan flex size-[88px] items-center justify-center rounded-full shadow-[0_10px_20px_rgb(15_42_68_/_0.1)]">
              <Ikon3D nama={ikon.ikon} ukuran={62} segera />
            </span>
          </div>
          <h2 className="mt-2.5 text-[21px] leading-tight font-bold tracking-[-0.01em]">{aset.Nama}</h2>
          <p className="text-[13px] font-semibold text-lapangan-teks-3">{aset.KodeAset}</p>
        </div>
        <Sobekan />
        <div className="grid grid-cols-2 gap-3 px-4 pt-3 pb-4">
          <div className="min-w-0">
            <span className="block text-[13px] font-medium text-lapangan-teks-3">Lokasi</span>
            <b className="block text-[14.5px] leading-snug font-bold">
              {aset.LokasiLabel ?? aset.LokasiNama ?? '—'}
            </b>
          </div>
          <div className="min-w-0">
            <span className="block text-[13px] font-medium text-lapangan-teks-3">Kondisi terakhir</span>
            <b className="block text-[14.5px] leading-snug font-bold">{kondisi.label}</b>
          </div>
        </div>
      </article>

      {terbuka ? (
        <Kartu className="p-3.5 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]">
          <div className="flex items-start gap-3">
            <Ikon3D nama="warning" ukuran={36} />
            <div className="min-w-0 flex-1">
              <b className="block text-[14.5px] leading-[1.3] font-bold">
                {terbuka.MilikSaya ? 'Kamu sudah melaporkan alat ini' : `${aset.Nama} sudah dilaporkan`}
              </b>
              <span className="text-[13px] font-medium text-lapangan-teks-3">
                {terbuka.Nomor} · {tampilanStatus(terbuka.Status).label.toLowerCase()}
                {terbuka.MilikSaya && terbuka.NamaTeknisi ? ` · ${terbuka.NamaTeknisi}` : ''}
              </span>
              {!terbuka.MilikSaya && (
                <p className="mt-1 text-[13px] leading-[1.4] text-lapangan-teks-2">
                  Tim teknik sudah menerimanya. Lapor lagi hanya bila masalahnya berbeda.
                </p>
              )}
            </div>
          </div>
          <div className="mt-3 flex gap-2">
            <TombolLapangan asChild ragam="lembut" ukuran="kecil" className="flex-1">
              <Link
                href={
                  terbuka.MilikSaya
                    ? ruteLapangan.pelapor.laporanDetail(terbuka.Id)
                    : ruteLapangan.pelapor.pantau(terbuka.Id)
                }
              >
                Pantau laporan itu
              </Link>
            </TombolLapangan>
            <TombolLapangan ragam="garis" ukuran="kecil" onClick={onTetapLapor}>
              Tetap lapor
            </TombolLapangan>
          </div>
        </Kartu>
      ) : (
        <div className="flex items-center gap-3 rounded-2xl bg-lapangan-hijau-50 px-3.5 py-3 text-lapangan-hijau-700">
          <CircleCheck aria-hidden className="size-5 shrink-0" />
          <span className="text-sm leading-[1.35] font-bold">Belum ada laporan untuk alat ini.</span>
        </div>
      )}
    </>
  );
}

interface PropsLangkahMasalah {
  kategori: KategoriLaporan[];
  kategoriId: string | null;
  onKategori: (id: string) => void;
  petunjukMasalah: (string | null | undefined)[];
  masalah: string | null;
  onMasalah: (masalah: string) => void;
  cerita: string;
  onCerita: (cerita: string) => void;
  urgensi: UrgensiLaporan;
  onUrgensi: (urgensi: UrgensiLaporan) => void;
  foto: File[];
  onFoto: (foto: File[]) => void;
  tampilGalat: boolean;
}

/** Langkah 2 (layar 06): kategori, pilihan cepat, cerita + dikte, urgensi awam, foto. */
function LangkahMasalah(p: PropsLangkahMasalah) {
  const { daring } = useSinkronisasiOffline();
  const pilihan = pilihanMasalah(...p.petunjukMasalah);

  if (p.kategori.length === 0) {
    return (
      <IlustrasiMomen
        jenis="galat"
        judul="Jenis laporan belum disiapkan"
        teks="Admin gedung belum mengatur jenis keluhan. Hubungi mereka agar kamu bisa melapor dari sini."
      />
    );
  }

  return (
    <>
      <PilihanChip
        gulir
        label="Jenis masalah"
        terpilih={p.kategoriId}
        onPilih={p.onKategori}
        item={p.kategori.map((satu) => ({
          kunci: satu.Id,
          label: satu.Nama,
          ikon: <Ikon3D nama={ikonKategori(satu.Nama).ikon} ukuran={20} />,
        }))}
      />

      <section className="flex flex-col gap-2.5">
        <h2 className="text-[15px] font-bold tracking-[-0.01em]">Pilih yang paling mirip</h2>
        <PilihanChip
          label="Masalah yang paling mirip"
          terpilih={p.masalah}
          onPilih={p.onMasalah}
          item={pilihan.map((satu) => ({ kunci: satu, label: satu }))}
        />
      </section>

      <IsianTiket
        label="Ceritakan singkat (boleh dilewati)"
        kanan={
          <TombolDikte onTeks={(teks) => p.onCerita([p.cerita.trim(), teks].filter(Boolean).join(' '))} />
        }
        galat={
          p.tampilGalat
            ? p.kategoriId
              ? 'Pilih masalahnya atau ceritakan singkat.'
              : 'Pilih jenis masalahnya dulu.'
            : null
        }
      >
        <AreaTiket
          value={p.cerita}
          onChange={(event) => p.onCerita(event.target.value)}
          rows={2}
          maxLength={2000}
          placeholder="Contoh: mati sejak pagi, bunyi keras saat dinyalakan."
        />
      </IsianTiket>

      <section className="flex flex-col gap-2.5">
        <h2 className="text-[15px] font-bold tracking-[-0.01em]">Seberapa mendesak?</h2>
        <div role="radiogroup" aria-label="Seberapa mendesak" className="grid grid-cols-2 gap-2">
          {PILIHAN_URGENSI.map((satu) => {
            const pilih = satu.kunci === p.urgensi;
            return (
              <button
                key={satu.kunci}
                type="button"
                role="radio"
                aria-checked={pilih}
                onClick={() => p.onUrgensi(satu.kunci)}
                className={cn(
                  'flex h-12 items-center gap-2.5 rounded-[14px] px-3.5 text-left text-sm font-bold focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50',
                  pilih
                    ? 'bg-lapangan-navy-800 text-white shadow-[0_6px_14px_rgb(18_50_79_/_0.25)]'
                    : 'bg-white text-lapangan-teks shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
                )}
              >
                <i
                  aria-hidden
                  className={cn(
                    'size-2.5 shrink-0 rounded-full',
                    satu.titik,
                    pilih && 'ring-2 ring-white/80',
                  )}
                />
                {satu.label}
              </button>
            );
          })}
        </div>
      </section>

      <section className="flex flex-col gap-2.5">
        <div className="flex items-center justify-between">
          <h2 className="text-[15px] font-bold tracking-[-0.01em]">Foto</h2>
          <span className="text-[13px] font-medium text-lapangan-teks-3">
            {p.foto.length} dari {MAKS_FOTO}
          </span>
        </div>
        <FotoPilihan foto={p.foto} onUbah={p.onFoto} maks={MAKS_FOTO} />
        {!daring && p.foto.length > 0 && (
          <p className="text-[13px] text-lapangan-kuning-700">
            Sedang offline: laporan tetap terkirim nanti, tetapi foto perlu ditambahkan lagi dari Lacak
            laporan.
          </p>
        )}
      </section>
    </>
  );
}

interface PropsLangkahTinjau {
  aset: AsetPelapor | null;
  lokasi: LokasiPelapor | null;
  kategori: KategoriLaporan | null;
  masalah: string | null;
  cerita: string;
  judul: string;
  urgensi: UrgensiLaporan;
  foto: File[];
  kontak: { Nama: string; Telepon: string | null };
  galat: Record<string, string>;
  onUbahAlat: () => void;
  onUbahMasalah: () => void;
}

function TombolUbah({ onClick, label }: { onClick: () => void; label: string }) {
  return (
    <button
      type="button"
      onClick={onClick}
      aria-label={label}
      className="-my-2 min-h-11 shrink-0 px-1 text-sm font-bold text-lapangan-oranye-teks focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
    >
      Ubah
    </button>
  );
}

/** Langkah 3 (layar 07): ringkasan bergaya tiket dengan "Ubah" per bagian, kontak, notifikasi. */
function LangkahTinjau(p: PropsLangkahTinjau) {
  const pratinjau = usePratinjauFoto(p.foto);
  const urgensi = PILIHAN_URGENSI.find((satu) => satu.kunci === p.urgensi);
  const ikon = p.aset ? ikonAset(p.aset) : { ikon: 'round_pushpin' as const, tint: 'oranye' as const };
  const pesanGalat = Object.entries(p.galat).filter(([kunci]) => kunci !== 'Pindai');

  return (
    <>
      {pesanGalat.length > 0 && (
        <PitaInfo
          nada="merah"
          ikon="warning"
          judul="Laporan belum bisa dikirim"
          teks={pesanGalat.map(([, pesan]) => pesan).join(' ')}
        />
      )}

      <article className="rounded-[20px] bg-white shadow-lapangan-kartu">
        <div className="flex items-center gap-3 px-4 pt-4 pb-3">
          <WadahIkon3D nama={ikon.ikon} tint={ikon.tint} className="size-11 rounded-[14px]" />
          <div className="min-w-0 flex-1">
            <h2 className="truncate text-base leading-[1.3] font-bold tracking-[-0.01em]">
              {p.aset?.Nama ?? 'Lokasi saja'}
            </h2>
            <span className="block truncate text-[13px] font-medium text-lapangan-teks-3">
              {[p.aset?.KodeAset, p.aset?.LokasiLabel ?? p.lokasi?.Label].filter(Boolean).join(' · ')}
            </span>
          </div>
          <TombolUbah onClick={p.onUbahAlat} label="Ubah alat" />
        </div>
        <Sobekan />
        <div className="px-4 pt-2.5 pb-3">
          <div className="flex items-center justify-between gap-2">
            <span className="flex min-w-0 flex-wrap items-center gap-2">
              <span className="text-[17px] font-bold">
                {p.masalah && p.masalah !== 'Lainnya' ? p.masalah : 'Masalah'}
              </span>
              {p.kategori && <ChipStatus warna="abu">{p.kategori.Nama}</ChipStatus>}
            </span>
            <TombolUbah onClick={p.onUbahMasalah} label="Ubah masalah" />
          </div>
          {p.cerita.trim() && <p className="mt-1 text-sm text-lapangan-teks-2">“{p.cerita.trim()}”</p>}
          {!p.cerita.trim() && <p className="mt-1 text-sm text-lapangan-teks-2">{p.judul}</p>}
          {pratinjau.length > 0 && (
            <div className="mt-3 flex items-center gap-2">
              {pratinjau.map((url, i) => (
                <img key={url} src={url} alt={`Foto ${i + 1}`} className="size-14 rounded-xl object-cover" />
              ))}
              <span className="ml-1 text-[13px] font-medium text-lapangan-teks-3">
                {pratinjau.length} foto
              </span>
            </div>
          )}
        </div>
        <Sobekan />
        <div className="grid grid-cols-2 gap-3 px-4 pt-2.5 pb-4">
          <div>
            <span className="block text-[13px] font-medium text-lapangan-teks-3">Seberapa mendesak</span>
            <b className="flex items-center gap-1.5 text-[14.5px] font-bold">
              <i aria-hidden className={cn('size-2.5 rounded-full', urgensi?.titik)} />
              {urgensi?.label}
            </b>
          </div>
          <div className="text-right">
            <span className="block text-[13px] font-medium text-lapangan-teks-3">Waktu lapor</span>
            <b className="text-[14.5px] font-bold">{waktuLengkap(new Date())}</b>
          </div>
        </div>
      </article>

      <Kartu className="overflow-hidden">
        <div className="flex items-center gap-3 px-4 py-3.5">
          <WadahIkon3D nama="telephone_receiver" tint="hijau" className="size-11 rounded-[14px]" />
          <div className="min-w-0 flex-1">
            <span className="block text-[13px] font-medium text-lapangan-teks-3">
              Teknisi bisa menghubungi
            </span>
            <b className="block truncate text-[15px] font-bold">
              {p.kontak.Telepon ? `${p.kontak.Telepon} · ${namaDepan(p.kontak.Nama)}` : 'Nomor belum diisi'}
            </b>
          </div>
          <Link
            href={ruteLapangan.akun}
            className="-my-2 inline-flex min-h-11 items-center px-1 text-sm font-bold text-lapangan-oranye-teks"
          >
            {p.kontak.Telepon ? 'Ubah' : 'Isi'}
          </Link>
        </div>
        <div className="flex items-center gap-3 border-t-[1.5px] border-lapangan-garis-2 px-4 py-3.5">
          <WadahIkon3D nama="bell" tint="kuning" className="size-11 rounded-[14px]" />
          <div className="min-w-0 flex-1">
            <b className="block text-[15px] font-bold">Kabari saya</b>
            <span className="block text-[13px] font-medium text-lapangan-teks-3">
              Notifikasi tiap ada perubahan
            </span>
          </div>
          <ChipStatus warna="hijau">Aktif</ChipStatus>
        </div>
      </Kartu>
    </>
  );
}

interface PropsTombolKirim {
  muatan: MuatanLaporan;
  foto: File[];
  onGalat: (galat: Record<string, string>) => void;
  onTersimpanOffline: () => void;
}

/**
 * Kirim laporan: online lewat Inertia (bersama foto), tanpa sinyal lewat antrean
 * offline FASE 20 dengan `KunciLaporan` yang sama, sehingga kiriman online yang
 * jawabannya hilang lalu ikut diantrekan tidak menjadi dua keluhan.
 */
function TombolKirim({ muatan, foto, onGalat, onTersimpanOffline }: PropsTombolKirim) {
  const { antrikan } = useSinkronisasiOffline();
  const [mengirim, setMengirim] = useState(false);

  const simpanOffline = async () => {
    if (!penyimpananTersedia()) {
      onGalat({
        Kirim: 'Sinyal tidak ada dan HP ini tidak bisa menyimpan laporan. Coba lagi saat ada sinyal.',
      });
      return;
    }

    await antrikan({
      Operasi: 'Keluhan.Buat',
      EntitasId: '',
      VersiKlien: null,
      MuatanData: muatan,
      Label: `Laporan: ${muatan.Judul}`,
    });
    onTersimpanOffline();
  };

  const kirim = () => {
    onGalat({});
    if (!navigator.onLine) {
      void simpanOffline();
      return;
    }

    router.post(
      ruteLapangan.pelapor.lapor,
      { ...muatan, Foto: foto },
      {
        forceFormData: true,
        onStart: () => setMengirim(true),
        onError: (errors) => onGalat(errors),
        onNetworkError: () => {
          void simpanOffline();
          return false;
        },
        onFinish: () => setMengirim(false),
      },
    );
  };

  return (
    <TombolLapangan penuh onClick={kirim} disabled={mengirim}>
      <Send aria-hidden />
      {mengirim ? 'Mengirim…' : 'Kirim Laporan'}
    </TombolLapangan>
  );
}
