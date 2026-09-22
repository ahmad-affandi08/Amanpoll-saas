import {
  LayoutDashboard,
  Box,
  Truck,
  FileCheck,
  Building2,
  Warehouse,
  Wrench,
  CalendarClock,
  ShieldCheck,
  Gauge,
  ChartColumn,
  ClipboardList,
  FileSignature,
  ShoppingCart,
  SmartphoneNfc,
  type LucideIcon,
} from 'lucide-react';

/** Peta navigasi Amanpoll (DESIGN.md 11). */

export interface SubItemNav {
  label: string;
  href: string;
  kodeIzin?: string | null;
  /** Kode fitur paket; menu disembunyikan saat paket tidak memuatnya. */
  kodeFitur?: string | null;
}

export interface ItemNav {
  label: string;
  href?: string;
  icon: LucideIcon;
  kodeIzin?: string | null;
  kodeFitur?: string | null;
  subItems?: SubItemNav[];
}

export interface GrupNav {
  label: string | null;
  items: ItemNav[];
}

const navUtama: GrupNav = {
  label: 'Platform',
  items: [
    { label: 'Dashboard', href: '/', icon: LayoutDashboard },
    { label: 'Persetujuan Saya', href: '/persetujuan/permintaan', icon: FileCheck },
    { label: 'Mode Teknisi (Offline)', href: '/offline/teknisi', icon: SmartphoneNfc },
    {
      label: 'Laporan & Dasbor',
      icon: ChartColumn,
      kodeFitur: 'modul.pelaporan_lanjutan',
      subItems: [
        { label: 'Laporan Tersimpan', href: '/pelaporan/laporan' },
        { label: 'Dasbor Kustom', href: '/pelaporan/dasbor' },
      ],
    },
  ],
};

const navOperasionalAset: GrupNav = {
  label: 'Operasional & Aset',
  items: [
    {
      label: 'Manajemen Aset',
      icon: Box,
      subItems: [
        { label: 'Daftar Aset', href: '/aset' },
        { label: 'Mutasi Aset', href: '/mutasi-aset', kodeIzin: 'Aset.Ubah' },
        { label: 'Serah Terima', href: '/serah-terima-aset', kodeIzin: 'Aset.Ubah' },
        { label: 'Penghapusan', href: '/penghapusan-aset', kodeIzin: 'Aset.Hapus' },
        { label: 'Kategori Aset', href: '/aset-master/kategori', kodeIzin: 'Aset.Buat' },
        { label: 'Merek', href: '/aset-master/merek', kodeIzin: 'Aset.Buat' },
        { label: 'Model Aset', href: '/aset-master/model', kodeIzin: 'Aset.Buat' },
      ],
    },
    {
      label: 'Pemeliharaan',
      icon: Wrench,
      subItems: [
        { label: 'Keluhan', href: '/pemeliharaan/keluhan' },
        { label: 'Perintah Kerja', href: '/pemeliharaan/perintah-kerja' },
        {
          label: 'Tingkat Layanan (SLA)',
          href: '/pemeliharaan/tingkat-layanan',
          kodeIzin: 'Pemeliharaan.Kelola',
        },
        {
          label: 'Kategori Keluhan',
          href: '/pemeliharaan/kategori-keluhan',
          kodeIzin: 'Pemeliharaan.Kelola',
        },
        { label: 'Kode Kegagalan', href: '/pemeliharaan/kode-kegagalan', kodeIzin: 'PerintahKerja.Kelola' },
      ],
    },
    {
      label: 'Preventif & Inspeksi',
      icon: CalendarClock,
      subItems: [
        {
          label: 'Rencana Preventif',
          href: '/preventif-inspeksi/rencana-pemeliharaan',
          kodeIzin: 'Pemeliharaan.Kelola',
        },
        { label: 'Inspeksi Berkala', href: '/preventif-inspeksi/inspeksi', kodeIzin: 'Pemeliharaan.Kelola' },
        {
          label: 'Daftar Periksa',
          href: '/preventif-inspeksi/templat-daftar-periksa',
          kodeIzin: 'Pemeliharaan.Kelola',
        },
      ],
    },
    {
      label: 'Kalibrasi',
      icon: Gauge,
      subItems: [
        { label: 'Dasbor & Kepatuhan', href: '/kalibrasi', kodeIzin: 'Kalibrasi.Kelola' },
        { label: 'Rencana Kalibrasi', href: '/kalibrasi/rencana', kodeIzin: 'Kalibrasi.Kelola' },
        { label: 'Pelaksanaan Kalibrasi', href: '/kalibrasi/pelaksanaan', kodeIzin: 'Kalibrasi.Kelola' },
        { label: 'Jenis Kalibrasi', href: '/kalibrasi/jenis', kodeIzin: 'Kalibrasi.Kelola' },
      ],
    },
  ],
};

const navRantaiPasok: GrupNav = {
  label: 'Persediaan & Rekanan',
  items: [
    {
      label: 'Perencanaan & Pengadaan',
      icon: ClipboardList,
      subItems: [
        { label: 'Anggaran', href: '/perencanaan-pengadaan/anggaran', kodeIzin: 'Pengadaan.Kelola' },
        { label: 'Usulan Aset', href: '/perencanaan-pengadaan/usulan-aset', kodeIzin: 'Pengadaan.Kelola' },
        {
          label: 'Rencana Pengadaan',
          href: '/perencanaan-pengadaan/rencana-pengadaan',
          kodeIzin: 'Pengadaan.Kelola',
        },
      ],
    },
    {
      label: 'Pengadaan',
      icon: ShoppingCart,
      subItems: [
        {
          label: 'Permintaan Pembelian',
          href: '/perencanaan-pengadaan/permintaan-pembelian',
          kodeIzin: 'Pengadaan.Kelola',
        },
        {
          label: 'Permintaan Penawaran',
          href: '/perencanaan-pengadaan/permintaan-penawaran',
          kodeIzin: 'Pengadaan.Kelola',
        },
        {
          label: 'Pesanan Pembelian',
          href: '/perencanaan-pengadaan/pesanan-pembelian',
          kodeIzin: 'Pengadaan.Kelola',
        },
        {
          label: 'Penerimaan Pembelian',
          href: '/perencanaan-pengadaan/penerimaan-pembelian',
          kodeIzin: 'Pengadaan.Kelola',
        },
        {
          label: 'Tagihan Penyedia',
          href: '/perencanaan-pengadaan/tagihan-penyedia',
          kodeIzin: 'Pengadaan.Kelola',
        },
      ],
    },
    {
      label: 'Persediaan',
      icon: Warehouse,
      subItems: [
        { label: 'Gudang', href: '/gudang', kodeIzin: 'Stok.Kelola' },
        { label: 'Kategori Suku Cadang', href: '/kategori-suku-cadang', kodeIzin: 'Stok.Kelola' },
        { label: 'Suku Cadang', href: '/suku-cadang', kodeIzin: 'Stok.Kelola' },
        { label: 'Stok Gudang', href: '/stok-suku-cadang', kodeIzin: 'Stok.Kelola' },
        { label: 'Mutasi Stok', href: '/mutasi-stok', kodeIzin: 'Stok.Kelola' },
        { label: 'Reservasi', href: '/reservasi-suku-cadang', kodeIzin: 'Stok.Kelola' },
      ],
    },
    {
      label: 'Penyedia',
      href: '/penyedia',
      icon: Truck,
      kodeIzin: 'Penyedia.Kelola',
    },
    {
      label: 'Kontrak',
      href: '/kontrak',
      icon: FileSignature,
      kodeIzin: 'Kontrak.Kelola',
    },
    {
      label: 'Kepatuhan',
      icon: ShieldCheck,
      subItems: [
        { label: 'Kepatuhan Aset', href: '/kepatuhan', kodeIzin: 'Kepatuhan.Kelola' },
        { label: 'Sertifikasi Aset', href: '/kepatuhan/sertifikasi', kodeIzin: 'Kepatuhan.Kelola' },
      ],
    },
  ],
};

const navPengaturan: GrupNav = {
  label: 'Sistem & Konfigurasi',
  items: [
    {
      label: 'Struktur & Platform',
      icon: Building2,
      subItems: [
        { label: 'Organisasi', href: '/platform/organisasi', kodeIzin: 'Pengaturan.Kelola' },
        { label: 'Unit Organisasi', href: '/platform/unit-organisasi', kodeIzin: 'Pengaturan.Kelola' },
        { label: 'Lokasi', href: '/platform/lokasi', kodeIzin: 'Pengaturan.Kelola' },
        { label: 'Konfigurasi Sistem', href: '/platform/konfigurasi', kodeIzin: 'Pengaturan.Kelola' },
        {
          label: 'Integrasi',
          href: '/integrasi',
          kodeIzin: 'Integrasi.Kelola',
          kodeFitur: 'modul.integrasi',
        },
        { label: 'Nomor Dokumen', href: '/platform/nomor-dokumen', kodeIzin: 'Pengaturan.Kelola' },
        { label: 'Hari Libur', href: '/platform/hari-libur', kodeIzin: 'Pengaturan.Kelola' },
        { label: 'Tag Kolaborasi', href: '/kolaborasi/tag', kodeIzin: 'Pengaturan.Kelola' },
        { label: 'Kolom Kustom', href: '/kolaborasi/kolom-kustom', kodeIzin: 'Pengaturan.Kelola' },
        { label: 'Alur Persetujuan', href: '/persetujuan/alur', kodeIzin: 'Persetujuan.Kelola' },
        { label: 'Templat Notifikasi', href: '/notifikasi/templat', kodeIzin: 'Pengaturan.Kelola' },
      ],
    },
    {
      label: 'Administrasi',
      icon: ShieldCheck,
      subItems: [
        { label: 'Pengguna', href: '/platform/pengguna', kodeIzin: 'Pengguna.Kelola' },
        { label: 'Peran & Izin', href: '/platform/peran', kodeIzin: 'Pengguna.Kelola' },
        {
          label: 'Kunci API',
          href: '/platform/kunci-api',
          kodeIzin: 'Integrasi.Kelola',
          kodeFitur: 'modul.integrasi',
        },
        { label: 'Log Audit', href: '/integrasi-audit/audit', kodeIzin: 'Audit.Lihat' },
        { label: 'Langganan', href: '/langganan', kodeIzin: 'Pengaturan.Kelola' },
      ],
    },
  ],
};

export const semuaGrup: GrupNav[] = [navUtama, navOperasionalAset, navRantaiPasok, navPengaturan];
