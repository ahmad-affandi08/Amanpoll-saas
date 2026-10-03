import { useState } from 'react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

/** Nada latar ubin ilustrasi; memakai token warna Amanpoll (DESIGN.md 3-6). */
export type NadaIlustrasi = 'teknisi' | 'info' | 'sukses' | 'safety' | 'bahaya' | 'netral';

interface TampilanNada {
  latar: string;
  warnaIkon: string;
}

const NADA: Record<NadaIlustrasi, TampilanNada> = {
  teknisi: { latar: 'bg-teknisi-500/12', warnaIkon: 'text-teknisi-700' },
  info: { latar: 'bg-info-600/10', warnaIkon: 'text-info-600' },
  sukses: { latar: 'bg-sukses-600/12', warnaIkon: 'text-sukses-600' },
  safety: { latar: 'bg-safety-500/15', warnaIkon: 'text-safety-600' },
  bahaya: { latar: 'bg-bahaya-600/10', warnaIkon: 'text-bahaya-600' },
  netral: { latar: 'bg-permukaan-100', warnaIkon: 'text-grafit-500' },
};

/**
 * Nada bawaan per berkas ilustrasi, supaya satu ilustrasi tampil dengan latar
 * yang sama di seluruh aplikasi tanpa setiap halaman menyetelnya sendiri.
 */
const NADA_ASET: Record<string, NadaIlustrasi> = {
  'aset-qr': 'teknisi',
  berhasil: 'sukses',
  'berkas-dokumen': 'info',
  'dashboard-analitik': 'teknisi',
  gudang: 'safety',
  hapus: 'bahaya',
  info: 'info',
  integrasi: 'teknisi',
  keluhan: 'safety',
  laporan: 'info',
  lokasi: 'sukses',
  notifikasi: 'safety',
  organisasi: 'teknisi',
  'pemeliharaan-jadwal': 'teknisi',
  pengguna: 'info',
  'penyedia-kontrak': 'sukses',
  'peran-izin': 'teknisi',
  peringatan: 'safety',
  'perintah-kerja': 'teknisi',
  persediaan: 'sukses',
  'persetujuan-kepatuhan': 'sukses',
  sinkronisasi: 'info',
  'suku-cadang': 'safety',
  teknisi: 'teknisi',
};

/** Rasio ilustrasi terhadap sisi ubin; ilustrasi sengaja melimpah keluar ubin. */
const RASIO_GAMBAR = 1.14;

/** Geseran ilustrasi ke kanan-bawah, relatif terhadap sisi ubin. */
const RASIO_GESER = 0.18;

/** Radius ubin relatif terhadap sisinya, mendekati bentuk squircle. */
const RASIO_RADIUS = 0.28;

export function nadaIlustrasi(sumber: string): NadaIlustrasi {
  const nama =
    sumber
      .split('/')
      .pop()
      ?.replace(/\.[^.]+$/, '') ?? '';

  return NADA_ASET[nama] ?? 'netral';
}

interface Props {
  /** Path berkas di `public/assets/3d`. */
  sumber: string;
  /** Ikon pengganti saat berkas ilustrasi belum tersedia. */
  ikonCadangan?: LucideIcon;
  /** Nada latar ubin; bawaannya diturunkan dari nama berkas. */
  nada?: NadaIlustrasi;
  /** `false` menampilkan ilustrasi tanpa ubin latar. */
  berlatar?: boolean;
  /** Sisi ubin; ilustrasi digambar sedikit lebih besar dan melimpah ke kanan-bawah. */
  ukuran?: number;
  /** Kelas tambahan untuk pembungkus (atau ilustrasi bila `berlatar` mati). */
  className?: string;
}

/** Ilustrasi 3D yang melimpah ke kanan-bawah ubin berwarna, dengan cadangan ikon. */
export function Ilustrasi3d({
  sumber,
  ikonCadangan: IkonCadangan,
  nada,
  berlatar = true,
  ukuran = 64,
  className,
}: Props) {
  const [gagal, setGagal] = useState(false);
  const tampilan = NADA[nada ?? nadaIlustrasi(sumber)];

  if (gagal && !IkonCadangan) {
    return null;
  }

  const sisiGambar = Math.round(ukuran * (berlatar ? RASIO_GAMBAR : 1));
  const kelasIsi = cn('object-contain', berlatar ? 'absolute' : 'shrink-0', berlatar ? undefined : className);
  const gayaIsi = berlatar
    ? {
        width: sisiGambar,
        height: sisiGambar,
        right: -Math.round(ukuran * RASIO_GESER),
        bottom: -Math.round(ukuran * RASIO_GESER),
      }
    : { width: sisiGambar, height: sisiGambar };

  const isi =
    gagal && IkonCadangan ? (
      <IkonCadangan aria-hidden className={cn(kelasIsi, tampilan.warnaIkon)} style={gayaIsi} />
    ) : (
      <img
        src={sumber}
        alt=""
        aria-hidden
        width={sisiGambar}
        height={sisiGambar}
        loading="lazy"
        onError={() => setGagal(true)}
        className={kelasIsi}
        style={gayaIsi}
      />
    );

  if (!berlatar) {
    return isi;
  }

  return (
    <div className={cn('relative shrink-0', className)} style={{ width: ukuran, height: ukuran }}>
      <div
        aria-hidden
        className={cn('absolute inset-0', tampilan.latar)}
        style={{ borderRadius: Math.round(ukuran * RASIO_RADIUS) }}
      />
      {isi}
    </div>
  );
}
