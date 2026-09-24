import type { LapanganBersama, PageProps } from '@/types/global';
import type { Notifikasi } from '@/features/Notifikasi/types';

/**
 * Prop Inertia bersama `lapangan` (didefinisikan di `@/types/global`, dibagikan `HandleInertiaRequests`):
 * `mode` (Teknisi menang bila memegang keduanya; `null` bukan pengguna lapangan), `murni`
 * (seluruh perannya bertanda Tampilan Lapangan), `bisaBeralih` (pengguna campuran).
 */
export type { LapanganBersama };

/** Mode Lapangan yang ditentukan server dari penanda `TampilanLapangan` pada peran (PRD 8.20). */
export type ModeLapangan = NonNullable<LapanganBersama['mode']>;

/** Props halaman Mode Lapangan. Baca `lapangan` dengan `?.` karena halaman lama mungkin dirender tanpa prop itu. */
export type PropsLapangan = PageProps;

/** Warna chip status (DESIGN.md 36.3). Teks -700 di atas tint -50; `putih` untuk chip di atas hero. */
export type WarnaChip = 'merah' | 'oranye' | 'kuning' | 'biru' | 'hijau' | 'abu' | 'putih';

/** Tint wadah ikon 3D (papan acuan: `.t-oranye`, `.t-hijau`, dst.). */
export type TintIkon = 'biru' | 'oranye' | 'hijau' | 'kuning' | 'merah' | 'ungu' | 'putih' | 'latar';

/** Keadaan satu perhentian (stasiun): sudah dilewati, sedang berlangsung, atau belum. */
export type KeadaanPerhentian = 'lewat' | 'kini' | 'nanti';

/** Kunci tab navigasi bawah. Teknisi: beranda·tugas·pindai·aset·akun; Pelapor: beranda·laporan·lapor·aset·akun. */
export type KunciNavLapangan = 'beranda' | 'tugas' | 'pindai' | 'laporan' | 'lapor' | 'aset' | 'akun';

/** Identitas pengguna untuk layar Akun (dikirim `LapanganAkunController` sebagai prop `akun`). */
export interface AkunLapangan {
  Id: string;
  Nama: string;
  Email: string;
  Telepon: string | null;
  Jabatan: string | null;
  NomorPegawai: string | null;
  AvatarUrl: string | null;
  /** Nama peran yang dipegang, urut abjad. */
  Peran: string[];
}

/** Props halaman Akun Mode Lapangan (`Lapangan/Akun`). Semua opsional: halaman jatuh ke prop bersama `auth`. */
export interface PropsHalamanAkun extends PropsLapangan {
  akun?: AkunLapangan;
  /** Angka ringkas di kartu apung (papan Pelapor layar 15), mis. `[{ Label: 'Laporan dikirim', Nilai: 14 }]`. */
  ringkasan?: { Label: string; Nilai: string | number }[];
}

/** Props halaman Notifikasi Mode Lapangan (`Lapangan/Notifikasi`, dari `LapanganNotifikasiController`). */
export interface PropsHalamanNotifikasi extends PropsLapangan {
  /** 50 notifikasi dalam aplikasi terbaru. */
  notifikasi?: Notifikasi[];
  jumlahBelumDibaca?: number;
}

// Teknisi (C)

// Pelapor (D)
