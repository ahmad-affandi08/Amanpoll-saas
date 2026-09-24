import {
  CameraOff,
  Flashlight,
  FlashlightOff,
  ImageIcon,
  Keyboard,
  LoaderCircle,
  Search,
  X,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { Ikon3D } from '@/features/Lapangan/components/Ikon3D';
import { IsianTiket, MasukanTiket } from '@/features/Lapangan/components/IsianTiket';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';

/** Asal kode yang dikirim `onHasil`. */
export type SumberKode = 'kamera' | 'gambar' | 'ketik';

type KeadaanPemindai = 'memuat' | 'memindai' | 'ditolak' | 'tanpa-kamera' | 'tidak-didukung' | 'galat';

interface HasilDeteksi {
  rawValue: string;
}

interface DetektorBarcode {
  detect(sumber: ImageBitmapSource): Promise<HasilDeteksi[]>;
}

interface KonstruktorDetektorBarcode {
  new (opsi?: { formats: string[] }): DetektorBarcode;
  getSupportedFormats?: () => Promise<string[]>;
}

/** `torch` belum ada di pustaka DOM TypeScript; peramban Android sudah mendukungnya. */
interface KemampuanSenter extends MediaTrackCapabilities {
  torch?: boolean;
}

interface BatasanSenter extends MediaTrackConstraintSet {
  torch?: boolean;
}

function ambilKonstruktorDetektor(): KonstruktorDetektorBarcode | null {
  if (typeof window === 'undefined' || !('BarcodeDetector' in window)) {
    return null;
  }
  const kandidat: unknown = window.BarcodeDetector;
  return typeof kandidat === 'function' ? (kandidat as KonstruktorDetektorBarcode) : null;
}

async function buatDetektor(): Promise<DetektorBarcode | null> {
  const Konstruktor = ambilKonstruktorDetektor();
  if (!Konstruktor) return null;

  try {
    const format = (await Konstruktor.getSupportedFormats?.()) ?? ['qr_code'];
    if (!format.includes('qr_code')) return null;
    return new Konstruktor({ formats: ['qr_code'] });
  } catch {
    return null;
  }
}

/** Kode yang sama tidak dikirim ulang dalam jeda ini selagi kamera masih mengarah ke label yang sama. */
const JEDA_KODE_SAMA_MS = 3000;
const JEDA_DETEKSI_MS = 180;

interface PropsPemindaiQr {
  /** Dipanggil dengan kode yang terbaca atau diketik (sudah di-trim). */
  onHasil: (kode: string, sumber: SumberKode) => void;
  /** Tombol X di kiri atas; tanpa prop ini tombolnya tidak tampil. */
  onTutup?: () => void;
  /** Bawaan "Pindai aset". */
  judul?: string;
  /** Bawaan "Arahkan ke label QR pada aset". */
  petunjuk?: string;
  /** Bawaan "Ketik kode aset". */
  labelKetik?: string;
  /** Contoh kode di isian ketik. Bawaan "AST-0002". */
  contohKode?: string;
  /**
   * `false` menjeda pendeteksian tanpa mematikan kamera, mis. selagi lembar "Aset ditemukan"
   * terbuka di atas kamera. Bawaan `true`.
   */
  aktif?: boolean;
  /** Isi di atas tombol ketik, mis. kartu "Terakhir dipindai". */
  bawah?: ReactNode;
  /** Dirender di atas kamera, mis. `<LembarBawah>` hasil pindai. */
  children?: ReactNode;
}

/**
 * Pemindai QR layar penuh (DESIGN.md 36.6 layar 13) memakai `BarcodeDetector` bawaan peramban
 * dan `getUserMedia` kamera belakang, tanpa pustaka tambahan. Senter tampil bila kamera
 * mendukungnya. Setiap keadaan punya jalan keluar ke isian kode: izin ditolak (dengan langkah
 * mengizinkan), kamera tidak ada, dan peramban yang belum mendukung BarcodeDetector.
 */
export function PemindaiQr({
  onHasil,
  onTutup,
  judul = 'Pindai aset',
  petunjuk = 'Arahkan ke label QR pada aset',
  labelKetik = 'Ketik kode aset',
  contohKode = 'AST-0002',
  aktif = true,
  bawah,
  children,
}: PropsPemindaiQr) {
  const video = useRef<HTMLVideoElement | null>(null);
  const aliran = useRef<MediaStream | null>(null);
  const detektor = useRef<DetektorBarcode | null>(null);
  const kodeTerakhir = useRef<{ kode: string; pada: number } | null>(null);
  const aktifRef = useRef(aktif);
  const onHasilRef = useRef(onHasil);
  const masukanGambar = useRef<HTMLInputElement | null>(null);

  const [keadaan, setKeadaan] = useState<KeadaanPemindai>('memuat');
  const [percobaan, setPercobaan] = useState(0);
  const [dukungSenter, setDukungSenter] = useState(false);
  const [senterNyala, setSenterNyala] = useState(false);
  const [bukaKetik, setBukaKetik] = useState(false);
  const [kodeKetik, setKodeKetik] = useState('');
  const [pesan, setPesan] = useState<string | null>(null);

  aktifRef.current = aktif;
  onHasilRef.current = onHasil;

  const kirimHasil = useCallback((kode: string, sumber: SumberKode) => {
    const bersih = kode.trim();
    if (bersih === '') return;

    const sekarang = Date.now();
    const terakhir = kodeTerakhir.current;
    if (
      sumber === 'kamera' &&
      terakhir &&
      terakhir.kode === bersih &&
      sekarang - terakhir.pada < JEDA_KODE_SAMA_MS
    ) {
      return;
    }
    kodeTerakhir.current = { kode: bersih, pada: sekarang };

    if (sumber === 'kamera' && 'vibrate' in navigator) {
      navigator.vibrate(60);
    }
    onHasilRef.current(bersih, sumber);
  }, []);

  useEffect(() => {
    let batal = false;
    let pewaktu: number | undefined;

    const hentikanAliran = () => {
      aliran.current?.getTracks().forEach((trek) => trek.stop());
      aliran.current = null;
    };

    const pindaiBerulang = () => {
      pewaktu = window.setTimeout(async () => {
        if (batal) return;
        const elemen = video.current;
        if (aktifRef.current && detektor.current && elemen && elemen.readyState >= 2) {
          try {
            const hasil = await detektor.current.detect(elemen);
            if (!batal && aktifRef.current && hasil[0]?.rawValue) {
              kirimHasil(hasil[0].rawValue, 'kamera');
            }
          } catch {
            // Bingkai yang gagal dibaca dilewati; bingkai berikutnya dicoba lagi.
          }
        }
        if (!batal) pindaiBerulang();
      }, JEDA_DETEKSI_MS);
    };

    const mulai = async () => {
      setKeadaan('memuat');
      setDukungSenter(false);
      setSenterNyala(false);

      detektor.current = await buatDetektor();
      if (batal) return;
      if (!detektor.current) {
        setKeadaan('tidak-didukung');
        return;
      }
      if (!navigator.mediaDevices?.getUserMedia) {
        setKeadaan('tanpa-kamera');
        return;
      }

      try {
        const media = await navigator.mediaDevices.getUserMedia({
          audio: false,
          video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
        });
        if (batal) {
          media.getTracks().forEach((trek) => trek.stop());
          return;
        }
        aliran.current = media;

        const trek = media.getVideoTracks()[0];
        const kemampuan: KemampuanSenter =
          typeof trek?.getCapabilities === 'function' ? trek.getCapabilities() : {};
        setDukungSenter(kemampuan.torch === true);

        if (video.current) {
          video.current.srcObject = media;
          await video.current.play().catch(() => undefined);
        }
        setKeadaan('memindai');
        pindaiBerulang();
      } catch (galat) {
        if (batal) return;
        const nama = galat instanceof DOMException ? galat.name : '';
        if (nama === 'NotAllowedError' || nama === 'SecurityError') {
          setKeadaan('ditolak');
        } else if (nama === 'NotFoundError' || nama === 'OverconstrainedError') {
          setKeadaan('tanpa-kamera');
        } else {
          setKeadaan('galat');
        }
      }
    };

    void mulai();

    return () => {
      batal = true;
      window.clearTimeout(pewaktu);
      hentikanAliran();
    };
  }, [percobaan, kirimHasil]);

  const alihkanSenter = async () => {
    const trek = aliran.current?.getVideoTracks()[0];
    if (!trek) return;
    const batasan: BatasanSenter = { torch: !senterNyala };
    try {
      await trek.applyConstraints({ advanced: [batasan] });
      setSenterNyala(!senterNyala);
    } catch {
      setDukungSenter(false);
    }
  };

  const pindaiGambar = async (berkas: File | undefined) => {
    if (!berkas || !detektor.current) return;
    setPesan(null);
    try {
      const gambar = await createImageBitmap(berkas);
      const hasil = await detektor.current.detect(gambar);
      gambar.close();
      if (hasil[0]?.rawValue) {
        kirimHasil(hasil[0].rawValue, 'gambar');
      } else {
        setPesan('QR tidak terbaca pada gambar itu. Coba foto yang lebih dekat, atau ketik kodenya.');
      }
    } catch {
      setPesan('Gambar tidak dapat dibaca. Ketik kodenya saja.');
    }
  };

  const kirimKetik = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (kodeKetik.trim() === '') return;
    setBukaKetik(false);
    kirimHasil(kodeKetik, 'ketik');
    setKodeKetik('');
  };

  const memindai = keadaan === 'memindai';
  const bisaGambar = detektor.current !== null && keadaan !== 'tidak-didukung';

  return (
    <div className="fixed inset-y-0 left-1/2 z-40 w-full max-w-[480px] -translate-x-1/2 overflow-hidden bg-black text-white">
      <video
        ref={video}
        muted
        playsInline
        autoPlay
        aria-hidden
        className={cn('absolute inset-0 size-full object-cover', !memindai && 'invisible')}
      />

      {memindai && (
        <div
          aria-hidden
          className="absolute top-[44%] left-1/2 size-[260px] -translate-x-1/2 -translate-y-1/2 rounded-[30px] shadow-[0_0_0_9999px_rgb(4_9_14_/_0.58)]"
        >
          <svg viewBox="0 0 260 260" className="absolute inset-0 size-full" fill="none">
            <path
              d="M2.5 54V32.5A30 30 0 0 1 32.5 2.5H54M206 2.5H227.5A30 30 0 0 1 257.5 32.5V54M257.5 206V227.5A30 30 0 0 1 227.5 257.5H206M54 257.5H32.5A30 30 0 0 1 2.5 227.5V206"
              stroke="white"
              strokeWidth="5"
              strokeLinecap="round"
            />
          </svg>
          <span className="absolute inset-x-3.5 top-[95px] h-[70px] animate-sapu-pindai-lapangan rounded-b border-b-[3px] border-lapangan-oranye-600 bg-linear-to-b from-transparent to-lapangan-oranye-600/25 motion-reduce:animate-none" />
        </div>
      )}

      {!memindai && (
        <div className="absolute inset-0 bg-linear-to-b from-lapangan-navy-800 to-lapangan-navy-900" />
      )}

      <div className="absolute inset-x-0 top-0 flex items-center justify-between gap-3 px-4 pt-[calc(env(safe-area-inset-top)+16px)]">
        {onTutup ? (
          <button
            type="button"
            onClick={onTutup}
            aria-label="Tutup pemindai"
            className="flex size-11 items-center justify-center rounded-full bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
          >
            <X aria-hidden className="size-5" />
          </button>
        ) : (
          <span className="size-11" />
        )}
        <h1 className="text-lg font-bold">{judul}</h1>
        {bisaGambar ? (
          <>
            <button
              type="button"
              onClick={() => masukanGambar.current?.click()}
              aria-label="Pindai dari foto"
              className="flex size-11 items-center justify-center rounded-full bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
            >
              <ImageIcon aria-hidden className="size-5" />
            </button>
            <input
              ref={masukanGambar}
              type="file"
              accept="image/*"
              className="sr-only"
              tabIndex={-1}
              aria-hidden
              onChange={(event) => {
                void pindaiGambar(event.target.files?.[0]);
                event.target.value = '';
              }}
            />
          </>
        ) : (
          <span className="size-11" />
        )}
      </div>

      {memindai && (
        <p className="absolute inset-x-0 top-[calc(44%-178px)] text-center text-[15px] font-semibold">
          <span className="rounded-full bg-black/35 px-3.5 py-[7px]">{petunjuk}</span>
        </p>
      )}

      {!memindai && (
        <div className="absolute inset-x-0 top-[calc(env(safe-area-inset-top)+84px)] bottom-[150px] flex items-center justify-center overflow-y-auto px-6">
          <KeadaanKamera keadaan={keadaan} onCobaLagi={() => setPercobaan((n) => n + 1)} />
        </div>
      )}

      <div className="absolute inset-x-0 bottom-0 flex flex-col gap-4 px-5 pb-[calc(env(safe-area-inset-bottom)+28px)]">
        {pesan && (
          <p
            role="alert"
            className="rounded-2xl bg-lapangan-merah-50 px-3.5 py-2.5 text-sm font-semibold text-lapangan-merah-700"
          >
            {pesan}
          </p>
        )}
        {bawah}
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() => setBukaKetik(true)}
            className="flex h-14 flex-1 items-center justify-center gap-2.5 rounded-2xl bg-white text-base font-bold text-lapangan-navy-800 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500"
          >
            <Keyboard aria-hidden className="size-[22px]" />
            {labelKetik}
          </button>
          {memindai && dukungSenter && (
            <button
              type="button"
              onClick={() => void alihkanSenter()}
              aria-pressed={senterNyala}
              aria-label={senterNyala ? 'Matikan senter' : 'Nyalakan senter'}
              className={cn(
                'flex size-14 items-center justify-center rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white',
                senterNyala ? 'bg-white text-lapangan-navy-800' : 'bg-white/15 text-white',
              )}
            >
              {senterNyala ? (
                <FlashlightOff aria-hidden className="size-[22px]" />
              ) : (
                <Flashlight aria-hidden className="size-[22px]" />
              )}
            </button>
          )}
        </div>
      </div>

      <LembarBawah
        buka={bukaKetik}
        onBukaBerubah={setBukaKetik}
        judul={labelKetik}
        deskripsi="Kode tertera di bawah QR pada label aset."
      >
        <form onSubmit={kirimKetik} className="flex flex-col gap-3.5">
          <IsianTiket label="Kode" ikon={Search}>
            <MasukanTiket
              value={kodeKetik}
              onChange={(event) => setKodeKetik(event.target.value)}
              placeholder={`Contoh: ${contohKode}`}
              autoFocus
              autoCapitalize="characters"
              autoComplete="off"
              spellCheck={false}
              enterKeyHint="search"
            />
          </IsianTiket>
          <TombolLapangan type="submit" penuh disabled={kodeKetik.trim() === ''}>
            Cari
          </TombolLapangan>
        </form>
      </LembarBawah>

      {children}
    </div>
  );
}

interface PropsKeadaanKamera {
  keadaan: KeadaanPemindai;
  onCobaLagi: () => void;
}

/** Isi tengah layar pemindai saat kamera belum atau tidak dapat dipakai. */
function KeadaanKamera({ keadaan, onCobaLagi }: PropsKeadaanKamera) {
  if (keadaan === 'memuat') {
    return (
      <div role="status" className="flex flex-col items-center gap-3 text-center">
        <LoaderCircle aria-hidden className="size-9 animate-spin text-white/80" />
        <p className="text-[15px] font-semibold">Menyalakan kamera…</p>
      </div>
    );
  }

  if (keadaan === 'memindai') {
    return null;
  }

  const isi: Record<Exclude<KeadaanPemindai, 'memuat' | 'memindai'>, { judul: string; teks: string }> = {
    ditolak: {
      judul: 'Izin kamera ditolak',
      teks: 'Pemindai butuh kamera untuk membaca QR. Izinkan lewat pengaturan peramban:',
    },
    'tanpa-kamera': {
      judul: 'Kamera tidak ditemukan',
      teks: 'Perangkat ini tidak punya kamera yang dapat dipakai. Ketik kode aset yang tertera di label.',
    },
    'tidak-didukung': {
      judul: 'Peramban belum bisa memindai',
      teks: 'Peramban ini belum mendukung pemindaian QR. Ketik kode aset yang tertera di bawah QR, atau buka dengan Chrome terbaru.',
    },
    galat: {
      judul: 'Kamera tidak dapat dibuka',
      teks: 'Kamera mungkin sedang dipakai aplikasi lain. Tutup aplikasi itu lalu coba lagi, atau ketik kodenya.',
    },
  };
  const { judul, teks } = isi[keadaan];

  return (
    <div role="alert" className="flex max-w-80 flex-col items-center text-center">
      <span className="flex size-28 items-center justify-center rounded-full bg-white/10">
        {keadaan === 'ditolak' || keadaan === 'galat' ? (
          <Ikon3D nama="camera" ukuran={72} segera />
        ) : keadaan === 'tanpa-kamera' ? (
          <CameraOff aria-hidden className="size-12 text-white/85" />
        ) : (
          <Ikon3D nama="magnifying_glass_tilted_left" ukuran={72} segera />
        )}
      </span>
      <h2 className="mt-5 text-xl font-bold">{judul}</h2>
      <p className="mt-2 text-[15px] leading-normal text-white/85">{teks}</p>
      {keadaan === 'ditolak' && (
        <ol className="mt-3 flex w-full flex-col gap-2 text-left text-sm text-white/90">
          {[
            'Ketuk ikon gembok atau pengaturan di sebelah alamat situs.',
            'Pilih Izin situs, lalu ubah Kamera menjadi Izinkan.',
            'Kembali ke sini dan ketuk Coba lagi.',
          ].map((langkah, i) => (
            <li key={langkah} className="flex gap-2.5 rounded-xl bg-white/10 px-3 py-2">
              <b className="flex size-5 shrink-0 items-center justify-center rounded-full bg-white text-xs text-lapangan-navy-800">
                {i + 1}
              </b>
              {langkah}
            </li>
          ))}
        </ol>
      )}
      {(keadaan === 'ditolak' || keadaan === 'galat') && (
        <TombolLapangan ragam="navy" ukuran="kecil" penuh className="mt-5 bg-white/15" onClick={onCobaLagi}>
          Coba lagi
        </TombolLapangan>
      )}
    </div>
  );
}
