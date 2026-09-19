import { Link, router, usePage } from '@inertiajs/react';
import { PropsWithChildren, useState } from 'react';
import type { PageProps } from '@/types/global';
import { useIzin } from '@/hooks/use-izin';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet';
import { NotificationBell } from '@/components/notifikasi/NotificationBell';
import { LogoMark } from '@/components/shared/LogoMark';
import {
  LayoutDashboard, Box, FolderTree, Tag, Layers, Truck, FileCheck, Building2, Network,
  MapPin, Settings, Hash, CalendarDays, Tags, Columns3, Workflow, MessageSquareText, Users,
  ShieldCheck, KeyRound, ScrollText, CircleUserRound, BellRing, Menu, type LucideIcon,
} from 'lucide-react';

interface ItemNav {
  label: string;
  href: string;
  icon: LucideIcon;
  kodeIzin?: string | null;
}

interface GrupNav {
  label: string | null;
  items: ItemNav[];
}

const navUtama: GrupNav = {
  label: null,
  items: [
    { label: 'Dashboard', href: '/', icon: LayoutDashboard },
    { label: 'Persetujuan Saya', href: '/persetujuan/permintaan', icon: FileCheck },
  ],
};

const navAset: GrupNav = {
  label: 'Aset',
  items: [
    { label: 'Daftar Aset', href: '/aset', icon: Box },
    { label: 'Kategori Aset', href: '/aset-master/kategori', icon: FolderTree, kodeIzin: 'Aset.Buat' },
    { label: 'Merek', href: '/aset-master/merek', icon: Tag, kodeIzin: 'Aset.Buat' },
    { label: 'Model Aset', href: '/aset-master/model', icon: Layers, kodeIzin: 'Aset.Buat' },
  ],
};

const navPenyedia: GrupNav = {
  label: 'Penyedia & Kontrak',
  items: [
    { label: 'Penyedia', href: '/penyedia', icon: Truck, kodeIzin: 'Penyedia.Kelola' },
  ],
};

const navStruktur: GrupNav = {
  label: 'Struktur & Konfigurasi',
  items: [
    { label: 'Organisasi', href: '/platform/organisasi', icon: Building2, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Unit Organisasi', href: '/platform/unit-organisasi', icon: Network, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Lokasi', href: '/platform/lokasi', icon: MapPin, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Konfigurasi', href: '/platform/konfigurasi', icon: Settings, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Nomor Dokumen', href: '/platform/nomor-dokumen', icon: Hash, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Hari Libur', href: '/platform/hari-libur', icon: CalendarDays, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Tag', href: '/kolaborasi/tag', icon: Tags, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Kolom Kustom', href: '/kolaborasi/kolom-kustom', icon: Columns3, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Alur Persetujuan', href: '/persetujuan/alur', icon: Workflow, kodeIzin: 'Persetujuan.Kelola' },
    { label: 'Templat Notifikasi', href: '/notifikasi/templat', icon: MessageSquareText, kodeIzin: 'Pengaturan.Kelola' },
  ],
};

const navAdministrasi: GrupNav = {
  label: 'Administrasi',
  items: [
    { label: 'Pengguna', href: '/platform/pengguna', icon: Users, kodeIzin: 'Pengguna.Kelola' },
    { label: 'Peran & Izin', href: '/platform/peran', icon: ShieldCheck, kodeIzin: 'Pengguna.Kelola' },
    { label: 'Kunci API', href: '/platform/kunci-api', icon: KeyRound, kodeIzin: 'Integrasi.Kelola' },
    { label: 'Log Audit', href: '/integrasi-audit/audit', icon: ScrollText, kodeIzin: 'Audit.Lihat' },
    { label: 'Profil', href: '/platform/profil', icon: CircleUserRound },
    { label: 'Preferensi Notifikasi', href: '/notifikasi/preferensi', icon: BellRing },
  ],
};

const semuaGrup: GrupNav[] = [navUtama, navAset, navPenyedia, navStruktur, navAdministrasi];

function tautanAktif(pathSekarang: string, href: string): boolean {
  if (href === '/') return pathSekarang === '/';
  return pathSekarang === href || pathSekarang.startsWith(`${href}/`);
}

function ItemTautan({ item, aktif }: { item: ItemNav; aktif: boolean }) {
  const Icon = item.icon;
  return (
    <Link
      href={item.href}
      className={
        aktif
          ? 'flex items-center gap-2.5 rounded-[7px] bg-white/10 px-3 py-2 text-sm font-medium text-white'
          : 'flex items-center gap-2.5 rounded-[7px] px-3 py-2 text-sm text-white/70 transition-colors hover:bg-white/5 hover:text-white'
      }
    >
      <Icon size={18} strokeWidth={1.75} className="shrink-0" />
      <span className="truncate">{item.label}</span>
    </Link>
  );
}

function IsiNavigasi({ grupTampil, pathSekarang }: { grupTampil: GrupNav[]; pathSekarang: string }) {
  return (
    <nav className="space-y-5">
      {grupTampil.map((grup, i) => (
        <div key={grup.label ?? `utama-${i}`}>
          {grup.label && (
            <p className="mb-1 px-3 text-[11px] font-medium tracking-wide text-white/40 uppercase">{grup.label}</p>
          )}
          <div className="space-y-0.5">
            {grup.items.map((item) => (
              <ItemTautan key={item.href} item={item} aktif={tautanAktif(pathSekarang, item.href)} />
            ))}
          </div>
        </div>
      ))}
    </nav>
  );
}

export default function AppLayout({ children }: PropsWithChildren) {
  const page = usePage<PageProps>();
  const { auth } = page.props;
  const pathSekarang = page.url.split('?')[0];
  const { boleh } = useIzin();
  const [drawerBuka, setDrawerBuka] = useState(false);
  const keluar = () => router.post('/logout');

  const grupTampil = semuaGrup
    .map((grup) => ({ ...grup, items: grup.items.filter((item) => !item.kodeIzin || boleh(item.kodeIzin)) }))
    .filter((grup) => grup.items.length > 0);

  return (
    <div className="min-h-screen bg-background">
      <div className="flex min-h-screen">
        <aside className="hidden w-[264px] shrink-0 bg-teknisi-900 px-3 py-5 lg:block">
          <Link href="/" className="mb-6 flex items-center gap-2.5 px-2">
            <LogoMark className="size-8 shrink-0" />
            <span className="text-lg font-semibold tracking-tight text-white">Amanpoll</span>
          </Link>
          <IsiNavigasi grupTampil={grupTampil} pathSekarang={pathSekarang} />
        </aside>

        <Sheet open={drawerBuka} onOpenChange={setDrawerBuka}>
          <SheetContent side="left" className="w-[264px] max-w-[80vw] bg-teknisi-900 px-3 py-5 [&_[data-slot=sheet-close]]:text-white/70">
            <SheetTitle className="sr-only">Navigasi</SheetTitle>
            <Link href="/" className="mb-6 flex items-center gap-2.5 px-2" onClick={() => setDrawerBuka(false)}>
              <LogoMark className="size-8 shrink-0" />
              <span className="text-lg font-semibold tracking-tight text-white">Amanpoll</span>
            </Link>
            <div onClick={() => setDrawerBuka(false)}>
              <IsiNavigasi grupTampil={grupTampil} pathSekarang={pathSekarang} />
            </div>
          </SheetContent>
        </Sheet>

        <main className="min-w-0 flex-1">
          <header className="flex h-14 items-center justify-between border-b border-border bg-card px-4 sm:px-6">
            <Button
              variant="ghost"
              size="icon"
              className="lg:hidden"
              onClick={() => setDrawerBuka(true)}
              aria-label="Buka navigasi"
            >
              <Menu size={20} />
            </Button>
            <div className="hidden lg:block" />
            <div className="flex items-center gap-3">
              <NotificationBell />
              <span className="hidden text-sm text-muted-foreground sm:inline">{auth.pengguna?.Nama ?? ''}</span>
              <Button variant="outline" size="sm" onClick={keluar}>Keluar</Button>
            </div>
          </header>
          <div className="p-4 sm:p-6">{children}</div>
        </main>
      </div>
    </div>
  );
}
