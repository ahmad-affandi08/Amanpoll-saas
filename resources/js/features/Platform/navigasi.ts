import { Boxes, ChartColumn, Globe, KeyRound, Megaphone, Package, TrendingUp, Users } from 'lucide-react';
import type { GrupNav, SubItemNav } from '@/layouts/navigasi';
import { HALAMAN_PEMASARAN } from '@/features/Pemasaran/navigasi';
import { rutePlatform } from '@/features/Platform/api';

type JalurPemasaran = (typeof HALAMAN_PEMASARAN)[number]['href'];

/**
 * Submenu pemasaran diambil dari daftar halaman yang sama dengan kartu pintasan Ringkasan.
 * Di konsol, `kodeFitur` berarti modul pemasaran platform, bukan fitur paket tenant.
 */
function halaman(...jalur: JalurPemasaran[]): SubItemNav[] {
  return jalur.flatMap((href) => {
    const satu = HALAMAN_PEMASARAN.find((item) => item.href === href);
    return satu ? [{ label: satu.label, href: satu.href, kodeIzin: satu.izin, kodeFitur: satu.modul }] : [];
  });
}

/**
 * Peta menu konsol platform, memakai bentuk menu yang sama dengan dashboard tenant
 * (DESIGN.md 11). `kodeIzin` hanya menyembunyikan menu; rutenya tetap dijaga backend.
 */
export const grupNavPlatform: GrupNav[] = [
  {
    label: 'Bisnis',
    items: [
      { label: 'Paket', href: rutePlatform.paket, icon: Package },
      { label: 'Langganan', href: rutePlatform.langganan, icon: Boxes },
    ],
  },
  {
    label: 'Growth & Marketing',
    items: [
      {
        label: 'Ringkasan',
        href: '/admin-platform/pemasaran',
        tepat: true,
        icon: TrendingUp,
        kodeIzin: 'platform.pemasaran.lihat',
      },
      {
        label: 'Analitik',
        icon: ChartColumn,
        subItems: halaman('/admin-platform/pemasaran/growth', '/admin-platform/pemasaran/eksperimen'),
      },
      {
        label: 'Prospek & Penjualan',
        icon: Users,
        subItems: halaman(
          '/admin-platform/pemasaran/prospek',
          '/admin-platform/pemasaran/prospek/aturan-skor',
          '/admin-platform/pemasaran/trial',
          '/admin-platform/pemasaran/demo',
          '/admin-platform/pemasaran/referral',
        ),
      },
      {
        label: 'Situs & Konten',
        icon: Globe,
        subItems: halaman(
          '/admin-platform/pemasaran/halaman',
          '/admin-platform/pemasaran/formulir',
          '/admin-platform/pemasaran/konten',
          '/admin-platform/pemasaran/sosial',
          '/admin-platform/pemasaran/redirect',
        ),
      },
      {
        label: 'Kampanye & Pesan',
        icon: Megaphone,
        subItems: halaman(
          '/admin-platform/pemasaran/kampanye',
          '/admin-platform/pemasaran/otomasi',
          '/admin-platform/pemasaran/email/template',
          '/admin-platform/pemasaran/email/sequence',
          '/admin-platform/pemasaran/whatsapp',
          '/admin-platform/pemasaran/email/konsen',
        ),
      },
    ],
  },
  {
    label: 'Pengaturan',
    items: [
      {
        label: 'Layanan Luar',
        href: rutePlatform.penyediaLayanan,
        icon: KeyRound,
        kodeIzin: 'platform.penyedia-layanan.kelola',
      },
    ],
  },
];
