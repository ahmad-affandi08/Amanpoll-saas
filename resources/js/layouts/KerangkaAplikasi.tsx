import { Link, router, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import type { PageProps } from '@/types/global';
import { useHakLangganan } from '@/hooks/use-hak-langganan';
import { useIzin } from '@/hooks/use-izin';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { PenyediaSinkronisasiOffline, useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { LoncengNotifikasi } from '@/components/notifikasi/LoncengNotifikasi';
import { IndikatorSinkronisasi } from '@/components/shared/IndikatorSinkronisasi';
import { LogoLambang } from '@/components/shared/Logo';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { ruteAuth } from '@/features/Auth/api';
import { ruteDokumentasi } from '@/features/Dokumentasi/api';
import { ruteLapangan } from '@/features/Lapangan/api';
import { PencarianGlobal } from '@/features/Pencarian/components/PencarianGlobal';
import type { HalamanTujuan } from '@/features/Pencarian/types';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarProvider,
  SidebarTrigger,
  useSidebar,
} from '@/components/ui/sidebar';
import {
  Building2,
  Settings,
  CircleUserRound,
  BellRing,
  BookOpen,
  ChevronsUpDown,
  LogOut,
  RotateCw,
  Smartphone,
} from 'lucide-react';
import { MenuSidebar } from '@/layouts/MenuSidebar';
import { type GrupNav, semuaGrup } from '@/layouts/navigasi';

function getInisial(name?: string): string {
  if (!name) return 'AP';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

interface AppSidebarProps {
  grupTampil: GrupNav[];
  pathSekarang: string;
  auth: PageProps['auth'];
  boleh: (kodeIzin: string) => boolean;
  keluar: () => void;
  /** Hanya ada bagi pengguna campuran (peran lapangan dan peran meja), PRD 8.20. */
  bukaModeLapangan?: () => void;
}

function AppSidebar({ grupTampil, pathSekarang, auth, boleh, keluar, bukaModeLapangan }: AppSidebarProps) {
  const { state, isMobile } = useSidebar();
  const isCollapsed = state === 'collapsed' && !isMobile;

  return (
    <Sidebar collapsible="icon">
      {/* Header Organisasi / Workspace Switcher */}
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <SidebarMenuButton
                  size="lg"
                  className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground cursor-pointer"
                >
                  <div className="flex aspect-square size-8 shrink-0 items-center justify-center rounded-sm border border-border bg-card p-1">
                    <LogoLambang className="size-full object-contain" />
                  </div>
                  <div className="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                    <span className="truncate text-sm font-semibold text-foreground">Amanpoll</span>
                    <span
                      className="truncate text-xs leading-tight text-grafit-500"
                      title="Asset & Maintenance Management Multi-Industri"
                    >
                      Asset & Maintenance Management Multi-Industri
                    </span>
                  </div>
                  <ChevronsUpDown className="ml-auto size-4 text-grafit-500 group-data-[collapsible=icon]:hidden" />
                </SidebarMenuButton>
              </DropdownMenuTrigger>
              <DropdownMenuContent
                className="w-(--radix-dropdown-menu-trigger-width) min-w-56"
                align="start"
                side="bottom"
                sideOffset={4}
              >
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                  Organisasi Aktif
                </DropdownMenuLabel>
                <DropdownMenuItem className="gap-2 p-2 font-medium cursor-default">
                  <div className="flex size-7 items-center justify-center rounded-md border bg-permukaan-100">
                    <Building2 className="size-4 shrink-0 text-teknisi-700" />
                  </div>
                  <div className="grid flex-1 text-left text-xs">
                    <span className="font-semibold truncate text-foreground">
                      {auth.pengguna?.organisasi?.Nama || 'Amanpoll'}
                    </span>
                    <span className="text-muted-foreground text-[11px]">
                      Kode: {auth.pengguna?.organisasi?.Kode || '-'}
                    </span>
                  </div>
                  <Badge
                    variant="outline"
                    className="ml-auto text-[10px] bg-teknisi-50 text-teknisi-800 border-teknisi-200"
                  >
                    Aktif
                  </Badge>
                </DropdownMenuItem>
                {boleh('Pengaturan.Kelola') && (
                  <>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem asChild>
                      <Link href="/platform/organisasi" className="flex items-center gap-2 cursor-pointer">
                        <Settings className="size-4 text-muted-foreground" />
                        <span>Pengaturan Organisasi</span>
                      </Link>
                    </DropdownMenuItem>
                  </>
                )}
              </DropdownMenuContent>
            </DropdownMenu>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent>
        <MenuSidebar grup={grupTampil} pathSekarang={pathSekarang} />
      </SidebarContent>

      {/* Footer Profil Pengguna Interaktif */}
      <SidebarFooter>
        <SidebarMenu>
          <SidebarMenuItem>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <SidebarMenuButton
                  size="lg"
                  className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground cursor-pointer"
                >
                  <Avatar className="size-8 rounded-full">
                    {auth.pengguna?.AvatarUrl ? (
                      <AvatarImage src={auth.pengguna.AvatarUrl} alt={auth.pengguna.Nama} />
                    ) : null}
                    <AvatarFallback className="rounded-full bg-teknisi-100 text-xs font-semibold text-teknisi-800">
                      {getInisial(auth.pengguna?.Nama)}
                    </AvatarFallback>
                  </Avatar>
                  <div className="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                    <span className="truncate font-medium text-foreground">
                      {auth.pengguna?.Nama ?? 'Pengguna'}
                    </span>
                    <span className="truncate text-xs text-grafit-500">{auth.pengguna?.Email ?? ''}</span>
                  </div>
                  <ChevronsUpDown className="ml-auto size-4 text-grafit-500 group-data-[collapsible=icon]:hidden" />
                </SidebarMenuButton>
              </DropdownMenuTrigger>
              <DropdownMenuContent
                className="w-(--radix-dropdown-menu-trigger-width) min-w-56"
                side={isCollapsed ? 'right' : 'bottom'}
                align="end"
                sideOffset={isCollapsed ? 10 : 4}
              >
                <DropdownMenuLabel className="p-0 font-normal">
                  <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <Avatar className="size-8 rounded-full">
                      {auth.pengguna?.AvatarUrl ? (
                        <AvatarImage src={auth.pengguna.AvatarUrl} alt={auth.pengguna.Nama} />
                      ) : null}
                      <AvatarFallback className="rounded-full bg-teknisi-100 text-xs font-semibold text-teknisi-800">
                        {getInisial(auth.pengguna?.Nama)}
                      </AvatarFallback>
                    </Avatar>
                    <div className="grid flex-1 text-left text-sm leading-tight">
                      <span className="truncate font-semibold text-foreground">
                        {auth.pengguna?.Nama ?? 'Pengguna'}
                      </span>
                      <span className="truncate text-xs text-muted-foreground">
                        {auth.pengguna?.Email ?? ''}
                      </span>
                    </div>
                  </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                  <DropdownMenuItem asChild>
                    <Link href="/platform/profil" className="flex items-center gap-2 cursor-pointer">
                      <CircleUserRound className="size-4" />
                      <span>Profil Akun</span>
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem asChild>
                    <Link href="/notifikasi/preferensi" className="flex items-center gap-2 cursor-pointer">
                      <BellRing className="size-4" />
                      <span>Preferensi Notifikasi</span>
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem asChild>
                    <Link href={ruteDokumentasi.index} className="flex items-center gap-2 cursor-pointer">
                      <BookOpen className="size-4" />
                      <span>Dokumentasi</span>
                    </Link>
                  </DropdownMenuItem>
                  {bukaModeLapangan && (
                    <DropdownMenuItem
                      onClick={bukaModeLapangan}
                      className="flex items-center gap-2 cursor-pointer"
                    >
                      <Smartphone className="size-4" />
                      <span>Buka Mode Lapangan</span>
                    </DropdownMenuItem>
                  )}
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  variant="destructive"
                  onClick={keluar}
                  className="flex items-center gap-2 cursor-pointer"
                >
                  <LogOut className="size-4" />
                  <span>Keluar</span>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>
    </Sidebar>
  );
}

function KerangkaDalam({ children }: PropsWithChildren) {
  const page = usePage<PageProps>();
  const { auth } = page.props;
  const pathSekarang = page.url.split('?')[0];
  const { boleh } = useIzin();
  const { bolehFitur } = useHakLangganan();
  const konfirmasi = useKonfirmasi();
  const {
    bersihkanDataLokal,
    dorong,
    jumlahBelumTersinkron,
    adaPembaruanAplikasi,
    terapkanPembaruanAplikasi,
  } = useSinkronisasiOffline();

  /** Pengguna campuran berpindah ke Mode Lapangan; pilihannya diingat di perangkat ini (PRD 8.20). */
  const bukaModeLapangan = page.props.lapangan?.bisaBeralih
    ? () => router.post(ruteLapangan.tampilan, { Tampilan: 'lapangan' })
    : undefined;

  /** Logout membersihkan data offline milik organisasi ini (FASE 20.02). */
  const keluar = async () => {
    await dorong();

    if (jumlahBelumTersinkron > 0) {
      const lanjut = await konfirmasi({
        judul: 'Keluar dengan perubahan yang belum tersinkron?',
        deskripsi: `${jumlahBelumTersinkron} perubahan lapangan masih tersimpan di perangkat ini dan belum diterima server. Keluar sekarang akan menghapus data lokal beserta perubahan tersebut.`,
        ragam: 'bahaya',
        labelAksi: 'Tetap keluar',
      });

      if (!lanjut) return;
    }

    await bersihkanDataLokal();
    router.post(ruteAuth.logout);
  };

  const grupTampil = semuaGrup
    .map((grup) => ({
      ...grup,
      items: grup.items
        // Menu disaring oleh izin peran DAN oleh isi paket. Keduanya hanya
        // menyembunyikan; rutenya sendiri tetap dijaga di backend.
        .filter(
          (item) =>
            (!item.kodeIzin || boleh(item.kodeIzin)) && (!item.kodeFitur || bolehFitur(item.kodeFitur)),
        )
        .map((item) => {
          if (!item.subItems) return item;
          const subTersaring = item.subItems.filter(
            (sub) => (!sub.kodeIzin || boleh(sub.kodeIzin)) && (!sub.kodeFitur || bolehFitur(sub.kodeFitur)),
          );
          return { ...item, subItems: subTersaring };
        })
        .filter((item) => !item.subItems || item.subItems.length > 0),
    }))
    .filter((grup) => grup.items.length > 0);

  // Halaman yang dapat dicari sama persis dengan menu yang tampil: izin dan paket sudah tersaring di atas.
  const halamanTujuan: HalamanTujuan[] = grupTampil.flatMap((grup) =>
    grup.items.flatMap((item) => {
      const jalurGrup = grup.label ?? '';
      if (item.subItems) {
        return item.subItems.map((sub) => ({
          label: sub.label,
          href: sub.href,
          jalur: [jalurGrup, item.label].filter(Boolean).join(' › '),
        }));
      }
      return item.href ? [{ label: item.label, href: item.href, jalur: jalurGrup }] : [];
    }),
  );

  return (
    <SidebarProvider>
      <AppSidebar
        grupTampil={grupTampil}
        pathSekarang={pathSekarang}
        auth={auth}
        boleh={boleh}
        keluar={() => void keluar()}
        bukaModeLapangan={bukaModeLapangan}
      />
      <SidebarInset>
        <header className="sticky top-0 z-20 flex h-13 items-center justify-between gap-3 border-b border-border bg-background/95 px-4 backdrop-blur-sm sm:px-8">
          <div className="flex items-center gap-2">
            <SidebarTrigger />
            <PencarianGlobal halaman={halamanTujuan} />
          </div>
          <div className="flex min-w-0 items-center gap-3">
            {adaPembaruanAplikasi && (
              <button
                type="button"
                onClick={terapkanPembaruanAplikasi}
                title="Versi baru tersedia. Muat ulang untuk memakainya."
                className="inline-flex items-center gap-1.5 rounded-[5px] border border-info-600/25 bg-info-600/10 px-2 py-1 text-xs font-medium text-info-700 hover:bg-info-600/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              >
                <RotateCw className="size-3.5 shrink-0" />
                <span className="hidden sm:inline">Versi baru tersedia</span>
              </button>
            )}
            <IndikatorSinkronisasi />
            <LoncengNotifikasi />
          </div>
        </header>
        <div className="px-4 py-5 sm:px-8 sm:py-7">{children}</div>
      </SidebarInset>
    </SidebarProvider>
  );
}

/** Pembungkus luar hanya memasang penyedia sinkronisasi offline; kerangkanya sendiri ada di dalam. */
export default function KerangkaAplikasi({ children }: PropsWithChildren) {
  return (
    <PenyediaSinkronisasiOffline>
      <KerangkaDalam>{children}</KerangkaDalam>
    </PenyediaSinkronisasiOffline>
  );
}
