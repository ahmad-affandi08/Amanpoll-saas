import { Link, router, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import type { PageProps } from '@/types/global';
import { useIzin } from '@/hooks/use-izin';
import { Button } from '@/components/ui/button';
import { NotificationBell } from '@/components/notifikasi/NotificationBell';
import { LogoMark } from '@/components/shared/LogoMark';
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarProvider,
  SidebarTrigger,
} from '@/components/ui/sidebar';
import {
  LayoutDashboard,
  Box,
  FolderTree,
  Tag,
  Layers,
  Truck,
  FileCheck,
  Building2,
  Network,
  MapPin,
  Settings,
  Hash,
  CalendarDays,
  Tags,
  Columns3,
  Workflow,
  MessageSquareText,
  Users,
  ShieldCheck,
  KeyRound,
  ScrollText,
  CircleUserRound,
  BellRing,
  ArrowLeftRight,
  Handshake,
  Trash2,
  Warehouse,
  PackageSearch,
  Boxes,
  ArrowRightLeft,
  BookmarkCheck,
  ClipboardList,
  Gauge,
  type LucideIcon,
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
    { label: 'Mutasi Aset', href: '/mutasi-aset', icon: ArrowLeftRight, kodeIzin: 'Aset.Ubah' },
    { label: 'Serah Terima', href: '/serah-terima-aset', icon: Handshake, kodeIzin: 'Aset.Ubah' },
    { label: 'Penghapusan', href: '/penghapusan-aset', icon: Trash2, kodeIzin: 'Aset.Hapus' },
    { label: 'Kategori Aset', href: '/aset-master/kategori', icon: FolderTree, kodeIzin: 'Aset.Buat' },
    { label: 'Merek', href: '/aset-master/merek', icon: Tag, kodeIzin: 'Aset.Buat' },
    { label: 'Model Aset', href: '/aset-master/model', icon: Layers, kodeIzin: 'Aset.Buat' },
  ],
};

const navPersediaan: GrupNav = {
  label: 'Persediaan',
  items: [
    { label: 'Gudang', href: '/gudang', icon: Warehouse, kodeIzin: 'Stok.Kelola' },
    {
      label: 'Kategori Suku Cadang',
      href: '/kategori-suku-cadang',
      icon: FolderTree,
      kodeIzin: 'Stok.Kelola',
    },
    { label: 'Suku Cadang', href: '/suku-cadang', icon: PackageSearch, kodeIzin: 'Stok.Kelola' },
    { label: 'Stok', href: '/stok-suku-cadang', icon: Boxes, kodeIzin: 'Stok.Kelola' },
    { label: 'Mutasi Stok', href: '/mutasi-stok', icon: ArrowRightLeft, kodeIzin: 'Stok.Kelola' },
    { label: 'Reservasi', href: '/reservasi-suku-cadang', icon: BookmarkCheck, kodeIzin: 'Stok.Kelola' },
  ],
};

const navOperasional: GrupNav = {
  label: 'Operasional',
  items: [
    { label: 'Keluhan', href: '/pemeliharaan/keluhan', icon: ClipboardList },
    {
      label: 'Tingkat Layanan',
      href: '/pemeliharaan/tingkat-layanan',
      icon: Gauge,
      kodeIzin: 'Pemeliharaan.Kelola',
    },
    {
      label: 'Kategori Keluhan',
      href: '/pemeliharaan/kategori-keluhan',
      icon: FolderTree,
      kodeIzin: 'Pemeliharaan.Kelola',
    },
  ],
};

const navPenyedia: GrupNav = {
  label: 'Penyedia & Kontrak',
  items: [{ label: 'Penyedia', href: '/penyedia', icon: Truck, kodeIzin: 'Penyedia.Kelola' }],
};

const navStruktur: GrupNav = {
  label: 'Struktur & Konfigurasi',
  items: [
    { label: 'Organisasi', href: '/platform/organisasi', icon: Building2, kodeIzin: 'Pengaturan.Kelola' },
    {
      label: 'Unit Organisasi',
      href: '/platform/unit-organisasi',
      icon: Network,
      kodeIzin: 'Pengaturan.Kelola',
    },
    { label: 'Lokasi', href: '/platform/lokasi', icon: MapPin, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Konfigurasi', href: '/platform/konfigurasi', icon: Settings, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Nomor Dokumen', href: '/platform/nomor-dokumen', icon: Hash, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Hari Libur', href: '/platform/hari-libur', icon: CalendarDays, kodeIzin: 'Pengaturan.Kelola' },
    { label: 'Tag', href: '/kolaborasi/tag', icon: Tags, kodeIzin: 'Pengaturan.Kelola' },
    {
      label: 'Kolom Kustom',
      href: '/kolaborasi/kolom-kustom',
      icon: Columns3,
      kodeIzin: 'Pengaturan.Kelola',
    },
    { label: 'Alur Persetujuan', href: '/persetujuan/alur', icon: Workflow, kodeIzin: 'Persetujuan.Kelola' },
    {
      label: 'Templat Notifikasi',
      href: '/notifikasi/templat',
      icon: MessageSquareText,
      kodeIzin: 'Pengaturan.Kelola',
    },
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

const semuaGrup: GrupNav[] = [
  navUtama,
  navAset,
  navOperasional,
  navPersediaan,
  navPenyedia,
  navStruktur,
  navAdministrasi,
];

function tautanAktif(pathSekarang: string, href: string): boolean {
  if (href === '/') return pathSekarang === '/';
  return pathSekarang === href || pathSekarang.startsWith(`${href}/`);
}

export default function AppLayout({ children }: PropsWithChildren) {
  const page = usePage<PageProps>();
  const { auth } = page.props;
  const pathSekarang = page.url.split('?')[0];
  const { boleh } = useIzin();
  const keluar = () => router.post('/logout');

  const grupTampil = semuaGrup
    .map((grup) => ({ ...grup, items: grup.items.filter((item) => !item.kodeIzin || boleh(item.kodeIzin)) }))
    .filter((grup) => grup.items.length > 0);

  return (
    <SidebarProvider>
      <Sidebar collapsible="icon">
        <SidebarHeader>
          <Link href="/" className="flex items-center gap-2.5 px-2 py-1">
            <LogoMark className="size-8 shrink-0" />
            <span className="truncate text-lg font-semibold tracking-tight text-white group-data-[collapsible=icon]:hidden">
              Amanpoll
            </span>
          </Link>
        </SidebarHeader>
        <SidebarContent>
          {grupTampil.map((grup, i) => (
            <SidebarGroup key={grup.label ?? `utama-${i}`}>
              {grup.label && <SidebarGroupLabel>{grup.label}</SidebarGroupLabel>}
              <SidebarGroupContent>
                <SidebarMenu>
                  {grup.items.map((item) => {
                    const Icon = item.icon;
                    return (
                      <SidebarMenuItem key={item.href}>
                        <SidebarMenuButton
                          asChild
                          isActive={tautanAktif(pathSekarang, item.href)}
                          tooltip={item.label}
                        >
                          <Link href={item.href}>
                            <Icon size={18} strokeWidth={1.75} />
                            <span>{item.label}</span>
                          </Link>
                        </SidebarMenuButton>
                      </SidebarMenuItem>
                    );
                  })}
                </SidebarMenu>
              </SidebarGroupContent>
            </SidebarGroup>
          ))}
        </SidebarContent>
      </Sidebar>
      <SidebarInset>
        <header className="flex h-14 items-center justify-between border-b border-border bg-card px-4 sm:px-6">
          <SidebarTrigger />
          <div className="flex items-center gap-3">
            <NotificationBell />
            <span className="hidden text-sm text-muted-foreground sm:inline">
              {auth.pengguna?.Nama ?? ''}
            </span>
            <Button variant="outline" size="sm" onClick={keluar}>
              Keluar
            </Button>
          </div>
        </header>
        <div className="p-4 sm:p-6">{children}</div>
      </SidebarInset>
    </SidebarProvider>
  );
}
