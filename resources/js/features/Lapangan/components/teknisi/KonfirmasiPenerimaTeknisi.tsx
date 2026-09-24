import { router } from '@inertiajs/react';
import { isAxiosError } from 'axios';
import { PenLine, QrCode, RefreshCw } from 'lucide-react';
import { useEffect, useRef, useState, type Ref } from 'react';
import { toast } from 'sonner';
import { http } from '@/lib/http';
import { PadTandaTangan, type KendaliPadTandaTangan } from '@/components/shared/PadTandaTangan';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { ruteLapangan } from '@/features/Lapangan/api';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { IsianTiket, MasukanTiket } from '@/features/Lapangan/components/IsianTiket';
import { Kartu } from '@/features/Lapangan/components/Kartu';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import type {
  KonfirmasiPenerima,
  QrKonfirmasiPenerima,
  StatusKonfirmasiPenerima,
  TiketTeknisiLengkap,
} from '@/features/Lapangan/types';
import { jamPendek } from '@/features/Lapangan/waktu';
import type { useFotoTertunda } from '@/features/Lapangan/components/teknisi/sesiKerja';

type FotoHook = ReturnType<typeof useFotoTertunda>;

/** Jeda membaca status konfirmasi: rapat saat QR tampil, longgar saat hanya menunggu. */
const JEDA_SAAT_QR_MS = 4_000;
const JEDA_MENUNGGU_MS = 20_000;

interface PropsIsianTamu {
  nama: string;
  jabatan: string;
  onNama: (nama: string) => void;
  onJabatan: (jabatan: string) => void;
  padRef: Ref<KendaliPadTandaTangan>;
  onPadBerubah: (ada: boolean) => void;
  galatNama?: string | null;
}

/**
 * Isian cara 3 (PRD 8.22): penerima tanpa akun menandatangani di HP teknisi. Nama wajib,
 * jabatan boleh kosong. Tanpa sinyal pun tersimpan di HP dan terkirim otomatis.
 */
export function IsianTandaTanganTamu({
  nama,
  jabatan,
  onNama,
  onJabatan,
  padRef,
  onPadBerubah,
  galatNama,
}: PropsIsianTamu) {
  return (
    <>
      <IsianTiket label="Nama penerima" galat={galatNama ?? null}>
        <MasukanTiket
          value={nama}
          onChange={(event) => onNama(event.target.value)}
          placeholder="Nama yang menerima pekerjaan"
          maxLength={150}
          autoComplete="off"
        />
      </IsianTiket>
      <IsianTiket label="Jabatan atau keterangan (boleh dikosongkan)">
        <MasukanTiket
          value={jabatan}
          onChange={(event) => onJabatan(event.target.value)}
          placeholder="Contoh: penyewa unit 3A"
          maxLength={150}
          autoComplete="off"
        />
      </IsianTiket>
      <PadTandaTangan
        ref={padRef}
        varian="lapangan"
        label="Kotak tanda tangan penerima"
        onBerubah={onPadBerubah}
      />
    </>
  );
}

/** Membaca status konfirmasi berkala selama ada sinyal dan pekerjaan menunggu konfirmasi. */
function usePantauKonfirmasi(
  tiketId: string,
  aktif: boolean,
  jeda: number,
  onStatus: (status: StatusKonfirmasiPenerima) => void,
) {
  const terakhir = useRef(onStatus);
  terakhir.current = onStatus;

  useEffect(() => {
    if (!aktif) return;
    let batal = false;
    const baca = () => {
      http
        .get<StatusKonfirmasiPenerima>(ruteLapangan.teknisi.konfirmasiPenerima(tiketId))
        .then(({ data }) => {
          if (!batal) terakhir.current(data);
        })
        .catch(() => undefined);
    };
    const jam = window.setInterval(baca, jeda);
    return () => {
      batal = true;
      window.clearInterval(jam);
    };
  }, [tiketId, aktif, jeda]);
}

interface PropsKartuKonfirmasi {
  tiket: TiketTeknisiLengkap;
  konfirmasiAwal: KonfirmasiPenerima | null;
  fotoHook: FotoHook;
  /** Laporan "selesai" sudah diterima server (bukan hanya tersimpan di HP). */
  sudahTerkirim: boolean;
}

/**
 * Kartu "Konfirmasi penerima" di layar Selesai (PRD 8.22). Teknisi tidak pernah menunggu
 * konfirmasi untuk menyelesaikan pekerjaan; kartu ini menawarkan cara penerima menjawab:
 * pindai QR (butuh sinyal) atau tanda tangan di HP ini (boleh tanpa sinyal). Pelapor keluhan
 * asal sudah diminta lewat aplikasinya sendiri.
 */
export function KartuKonfirmasiPenerimaTeknisi({
  tiket,
  konfirmasiAwal,
  fotoHook,
  sudahTerkirim,
}: PropsKartuKonfirmasi) {
  const { daring } = useSinkronisasiOffline();
  const [konfirmasi, setKonfirmasi] = useState<KonfirmasiPenerima | null>(konfirmasiAwal);
  const [bukaQr, setBukaQr] = useState(false);
  const [bukaTamu, setBukaTamu] = useState(false);
  const draf = fotoHook.foto.find((satu) => satu.Kategori === 'TandaTangan');

  useEffect(() => setKonfirmasi(konfirmasiAwal), [konfirmasiAwal]);

  usePantauKonfirmasi(
    tiket.Id,
    daring && sudahTerkirim && !konfirmasi,
    bukaQr ? JEDA_SAAT_QR_MS : JEDA_MENUNGGU_MS,
    (status) => {
      if (status.Konfirmasi) {
        setKonfirmasi(status.Konfirmasi);
        setBukaQr(false);
        toast.success(`${status.Konfirmasi.NamaPenerima} menerima pekerjaan ini.`);
        return;
      }
      if (status.Status === 'Dikerjakan' && status.Terakhir?.Hasil === 'MasihBermasalah') {
        setBukaQr(false);
        toast.error(`${status.Terakhir.NamaPenerima}: masih bermasalah. ${status.Terakhir.Alasan ?? ''}`);
        router.reload();
      }
    },
  );

  if (konfirmasi) {
    return (
      <PitaInfo
        nada="hijau"
        ikon="handshake"
        judul={`Diterima ${konfirmasi.NamaPenerima}`}
        teks={`${konfirmasi.LabelMetode} · pukul ${jamPendek(konfirmasi.DikonfirmasiPada)}`}
      />
    );
  }

  if (draf) {
    return (
      <PitaInfo
        nada="kuning"
        ikon="mobile_phone"
        judul={`Tanda tangan ${draf.NamaPenerima ?? 'penerima'} tersimpan di HP`}
        teks="Terkirim otomatis saat ada sinyal. Tidak perlu diulang."
      />
    );
  }

  return (
    <Kartu pad>
      <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
        <h2 className="text-[17px] font-bold tracking-[-0.01em]">Konfirmasi penerima</h2>
        <ChipStatus warna="kuning">Menunggu konfirmasi penerima</ChipStatus>
      </div>
      <p className="mt-1.5 text-sm leading-[1.45] text-lapangan-teks-2">
        {tiket.DariKeluhan
          ? 'Pelapor sudah diminta mengonfirmasi dari aplikasinya. Penerima di lokasi juga bisa menjawab sekarang.'
          : 'Minta penerima di lokasi menerima pekerjaan ini.'}
      </p>
      <div className="mt-3 flex flex-col gap-2.5">
        <TombolLapangan ragam="lembut" ukuran="kecil" penuh onClick={() => setBukaQr(true)}>
          <QrCode aria-hidden />
          Tampilkan QR untuk dipindai
        </TombolLapangan>
        <TombolLapangan ragam="garis" ukuran="kecil" penuh onClick={() => setBukaTamu(true)}>
          <PenLine aria-hidden />
          Tanda tangan di HP ini
        </TombolLapangan>
      </div>

      <LembarQr
        buka={bukaQr}
        onBukaBerubah={setBukaQr}
        tiketId={tiket.Id}
        sudahTerkirim={sudahTerkirim}
        onPilihTandaTangan={() => {
          setBukaQr(false);
          setBukaTamu(true);
        }}
      />
      <LembarTandaTanganTamu
        buka={bukaTamu}
        onBukaBerubah={setBukaTamu}
        tiketId={tiket.Id}
        fotoHook={fotoHook}
      />
    </Kartu>
  );
}

function sisaWaktu(sampai: string, sekarang: number): string | null {
  const detik = Math.max(0, Math.round((new Date(sampai).getTime() - sekarang) / 1000));
  if (detik === 0) return null;
  return `${Math.floor(detik / 60)}:${String(detik % 60).padStart(2, '0')}`;
}

function pesanGalat(galat: unknown, cadangan: string): string {
  if (isAxiosError(galat) && galat.response?.data && typeof galat.response.data === 'object') {
    const data = galat.response.data as { pesan?: unknown };
    if (typeof data.pesan === 'string') return data.pesan;
  }
  return cadangan;
}

function LembarQr({
  buka,
  onBukaBerubah,
  tiketId,
  sudahTerkirim,
  onPilihTandaTangan,
}: {
  buka: boolean;
  onBukaBerubah: (buka: boolean) => void;
  tiketId: string;
  sudahTerkirim: boolean;
  onPilihTandaTangan: () => void;
}) {
  const { daring } = useSinkronisasiOffline();
  const [qr, setQr] = useState<QrKonfirmasiPenerima | null>(null);
  const [galat, setGalat] = useState<string | null>(null);
  const [memuat, setMemuat] = useState(false);
  const [sekarang, setSekarang] = useState(() => Date.now());

  const muat = () => {
    setMemuat(true);
    setGalat(null);
    http
      .post<QrKonfirmasiPenerima>(ruteLapangan.teknisi.konfirmasiPenerimaQr(tiketId))
      .then(({ data }) => {
        setQr(data);
        setSekarang(Date.now());
      })
      .catch((kesalahan: unknown) => setGalat(pesanGalat(kesalahan, 'QR belum bisa dibuat. Coba lagi.')))
      .finally(() => setMemuat(false));
  };

  useEffect(() => {
    if (buka && daring && sudahTerkirim && !qr) muat();
    if (!buka) setQr(null);
  }, [buka, daring, sudahTerkirim]);

  useEffect(() => {
    if (!buka || !qr) return;
    const jam = window.setInterval(() => setSekarang(Date.now()), 1000);
    return () => window.clearInterval(jam);
  }, [buka, qr]);

  const sisa = qr ? sisaWaktu(qr.BerlakuSampai, sekarang) : null;
  const bisaQr = daring && sudahTerkirim;

  return (
    <LembarBawah
      buka={buka}
      onBukaBerubah={onBukaBerubah}
      judul="Pindai untuk menerima"
      deskripsi="Penerima memindai QR ini dengan kamera HP-nya, masuk dengan akunnya, lalu menekan Terima pekerjaan."
      kaki={
        bisaQr ? (
          <TombolLapangan ragam="garis" penuh disabled={memuat} onClick={muat}>
            <RefreshCw aria-hidden />
            Perbarui QR
          </TombolLapangan>
        ) : (
          <TombolLapangan penuh onClick={onPilihTandaTangan}>
            <PenLine aria-hidden />
            Tanda tangan di HP ini
          </TombolLapangan>
        )
      }
    >
      {!bisaQr ? (
        <IlustrasiMomen
          ringkas
          jenis="offline"
          judul={daring ? 'Laporan belum terkirim' : 'Butuh sinyal untuk QR'}
          teks={
            daring
              ? 'QR bisa ditampilkan setelah laporan selesai sampai ke server. Sementara itu, minta tanda tangan di HP ini.'
              : 'Tanpa sinyal, minta penerima menandatangani di HP ini. Tanda tangannya terkirim otomatis saat online.'
          }
        />
      ) : galat ? (
        <IlustrasiMomen ringkas jenis="galat" judul="QR belum bisa ditampilkan" teks={galat} />
      ) : (
        <div className="flex flex-col items-center gap-3">
          <div className="flex size-[248px] items-center justify-center rounded-[20px] bg-white p-3 text-center shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]">
            {qr && sisa ? (
              // SVG buatan server (bacon/bacon-qr-code) dari tautan bertanda tangan; tanpa masukan pengguna.
              <div
                role="img"
                aria-label="QR konfirmasi penerima"
                className="size-full [&_svg]:size-full"
                dangerouslySetInnerHTML={{ __html: qr.Svg }}
              />
            ) : (
              <span className="text-sm font-semibold text-lapangan-teks-3">
                {memuat ? 'Membuat QR…' : 'QR kedaluwarsa. Perbarui dulu.'}
              </span>
            )}
          </div>
          {qr && sisa && (
            <p className="text-[13px] font-semibold text-lapangan-teks-3">
              Berlaku <span className="font-bold text-lapangan-teks tabular-nums">{sisa}</span> lagi · layar
              ini berganti sendiri begitu penerima menjawab
            </p>
          )}
        </div>
      )}
    </LembarBawah>
  );
}

function LembarTandaTanganTamu({
  buka,
  onBukaBerubah,
  tiketId,
  fotoHook,
}: {
  buka: boolean;
  onBukaBerubah: (buka: boolean) => void;
  tiketId: string;
  fotoHook: FotoHook;
}) {
  const { daring } = useSinkronisasiOffline();
  const pad = useRef<KendaliPadTandaTangan>(null);
  const [nama, setNama] = useState('');
  const [jabatan, setJabatan] = useState('');
  const [adaGambar, setAdaGambar] = useState(false);
  const [menyimpan, setMenyimpan] = useState(false);

  const simpan = async () => {
    const gambar = await pad.current?.ambilBlob();
    if (!gambar || nama.trim() === '') return;
    setMenyimpan(true);
    try {
      await fotoHook.tambah(tiketId, 'TandaTangan', gambar, `Tanda tangan ${nama.trim()}`, {
        NamaPenerima: nama.trim(),
        JabatanPenerima: jabatan.trim() || null,
      });
      if (daring) await fotoHook.unggahSemua();
      toast.success(daring ? 'Tanda tangan penerima terkirim.' : 'Tanda tangan tersimpan di HP.');
      onBukaBerubah(false);
    } finally {
      setMenyimpan(false);
    }
  };

  return (
    <LembarBawah
      buka={buka}
      onBukaBerubah={onBukaBerubah}
      judul="Tanda tangan penerima"
      deskripsi="Untuk penerima tanpa akun: tamu, penyewa, atau pihak luar."
      kaki={
        <TombolLapangan
          penuh
          disabled={!adaGambar || nama.trim() === '' || menyimpan}
          onClick={() => void simpan()}
        >
          {menyimpan ? 'Menyimpan…' : 'Simpan tanda tangan'}
        </TombolLapangan>
      }
    >
      <IsianTandaTanganTamu
        nama={nama}
        jabatan={jabatan}
        onNama={setNama}
        onJabatan={setJabatan}
        padRef={pad}
        onPadBerubah={setAdaGambar}
      />
    </LembarBawah>
  );
}
