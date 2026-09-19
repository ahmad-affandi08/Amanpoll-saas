import { Link, router, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import type { PageProps } from '@/types/global';
import { useIzin } from '@/hooks/use-izin';
import { Button } from '@/components/ui/button';
import { NotificationBell } from '@/components/notifikasi/NotificationBell';

const menu = [
  ['Dashboard', '/'], ['Aset', '/aset'], ['Perintah Kerja', '/pemeliharaan'],
  ['Kalibrasi', '/kalibrasi'], ['Persediaan', '/persediaan'],
  ['Pengadaan', '/perencanaan-pengadaan'], ['Laporan', '/pelaporan'],
  ['Persetujuan Saya', '/persetujuan/permintaan'],
];

const menuStruktur: Array<[string, string, string | null]> = [
  ['Organisasi', '/platform/organisasi', 'Pengaturan.Kelola'],
  ['Unit Organisasi', '/platform/unit-organisasi', 'Pengaturan.Kelola'],
  ['Lokasi', '/platform/lokasi', 'Pengaturan.Kelola'],
  ['Penyedia', '/penyedia', 'Penyedia.Kelola'],
  ['Konfigurasi', '/platform/konfigurasi', 'Pengaturan.Kelola'],
  ['Nomor Dokumen', '/platform/nomor-dokumen', 'Pengaturan.Kelola'],
  ['Hari Libur', '/platform/hari-libur', 'Pengaturan.Kelola'],
  ['Tag', '/kolaborasi/tag', 'Pengaturan.Kelola'],
  ['Kolom Kustom', '/kolaborasi/kolom-kustom', 'Pengaturan.Kelola'],
  ['Alur Persetujuan', '/persetujuan/alur', 'Persetujuan.Kelola'],
  ['Templat Notifikasi', '/notifikasi/templat', 'Pengaturan.Kelola'],
];

const menuAdministrasi: Array<[string, string, string | null]> = [
  ['Pengguna', '/platform/pengguna', 'Pengguna.Kelola'],
  ['Peran & Izin', '/platform/peran', 'Pengguna.Kelola'],
  ['Kunci API', '/platform/kunci-api', 'Integrasi.Kelola'],
  ['Log Audit', '/integrasi-audit/audit', 'Audit.Lihat'],
  ['Profil', '/platform/profil', null],
  ['Preferensi Notifikasi', '/notifikasi/preferensi', null],
];

export default function AppLayout({ children }: PropsWithChildren) {
  const { auth } = usePage<PageProps>().props;
  const { boleh } = useIzin();
  const keluar = () => router.post('/logout');

  return (
    <div className="min-h-screen bg-background">
      <div className="flex min-h-screen">
        <aside className="hidden w-64 border-r border-border bg-card p-5 lg:block">
          <div className="mb-8 text-xl font-semibold text-foreground">Amanpoll</div>
          <nav className="space-y-1">
            {menu.map(([label, href]) => (
              <Link key={href} href={href} className="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground">
                {label}
              </Link>
            ))}
          </nav>
          <div className="mt-6 border-t border-border pt-4">
            <p className="mb-1 px-3 text-xs font-semibold uppercase text-muted-foreground">Struktur & Konfigurasi</p>
            <nav className="space-y-1">
              {menuStruktur
                .filter(([, , kodeIzin]) => kodeIzin === null || boleh(kodeIzin))
                .map(([label, href]) => (
                  <Link key={href} href={href} className="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground">
                    {label}
                  </Link>
                ))}
            </nav>
          </div>
          <div className="mt-6 border-t border-border pt-4">
            <p className="mb-1 px-3 text-xs font-semibold uppercase text-muted-foreground">Administrasi</p>
            <nav className="space-y-1">
              {menuAdministrasi
                .filter(([, , kodeIzin]) => kodeIzin === null || boleh(kodeIzin))
                .map(([label, href]) => (
                  <Link key={href} href={href} className="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground">
                    {label}
                  </Link>
                ))}
            </nav>
          </div>
        </aside>
        <main className="min-w-0 flex-1">
          <header className="flex h-16 items-center justify-between border-b border-border bg-card px-6">
            <div className="font-medium text-foreground">Asset & Maintenance Management</div>
            <div className="flex items-center gap-3">
              <NotificationBell />
              <span className="text-sm text-muted-foreground">{auth.pengguna?.Nama ?? ''}</span>
              <Button variant="outline" size="sm" onClick={keluar}>Keluar</Button>
            </div>
          </header>
          <div className="p-6">{children}</div>
        </main>
      </div>
    </div>
  );
}
