import { Link, router, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import type { PageProps } from '@/types/global';
import { useEntitlement } from '@/hooks/use-entitlement';
import { useIzin } from '@/hooks/use-izin';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { PenyediaSinkronisasiOffline, useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { cn } from '@/lib/utils';
import { NotificationBell } from '@/components/notifikasi/NotificationBell';
import { IndikatorSinkronisasi } from '@/components/shared/IndikatorSinkronisasi';
import { LogoMark } from '@/components/shared/LogoMark';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ruteAuth } from '@/features/Auth/api';
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
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
  SidebarProvider,
  SidebarTrigger,
  useSidebar,
} from '@/components/ui/sidebar';
import {
  Building2,
  Settings,
  CircleUserRound,
  BellRing,
  ChevronRight,
  ChevronsUpDown,
  LogOut,
  RotateCw,
} from 'lucide-react';
import { type GrupNav, type ItemNav, semuaGrup } from '@/layouts/navigasi';

function tautanAktif(pathSekarang: string, href?: string): boolean {
  if (!href) return false;
  if (href === '/') return pathSekarang === '/';
  return pathSekarang === href || pathSekarang.startsWith(`${href}/`);
}

function apakahGrupItemAktif(pathSekarang: string, item: ItemNav): boolean {
  if (item.href && tautanAktif(pathSekarang, item.href)) return true;
  if (item.subItems) {
    return item.subItems.some((sub) => tautanAktif(pathSekarang, sub.href));
  }
  return false;
}

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
}

function AppSidebar({ grupTampil, pathSekarang, auth, boleh, keluar }: AppSidebarProps) {
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
                  <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-teknisi-800 text-sidebar-primary-foreground p-1 shrink-0">
                    <LogoMark className="size-full object-contain" />
                  </div>
                  <div className="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                    <span className="truncate text-base font-bold text-white tracking-tight">Amanpoll</span>
                    <span
                      className="truncate text-[11px] text-sidebar-foreground/75 font-medium leading-tight"
                      title="Asset & Maintenance Management Multi-Industri"
                    >
                      Asset & Maintenance Management Multi-Industri
                    </span>
                  </div>
                  <ChevronsUpDown className="ml-auto size-4 group-data-[collapsible=icon]:hidden text-sidebar-foreground/70" />
                </SidebarMenuButton>
              </DropdownMenuTrigger>
              <DropdownMenuContent
                className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg bg-card shadow-lg border border-border"
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

      {/* Konten Menu Utama & Submenu Collapsible */}
      <SidebarContent>
        {grupTampil.map((grup, i) => (
          <SidebarGroup key={grup.label ?? `utama-${i}`}>
            {grup.label && (
              <SidebarGroupLabel className="group-data-[collapsible=icon]:hidden">
                {grup.label}
              </SidebarGroupLabel>
            )}
            <SidebarGroupContent>
              <SidebarMenu>
                {grup.items.map((item) => {
                  const Icon = item.icon;
                  const isActive = apakahGrupItemAktif(pathSekarang, item);

                  // Kasus 1: Menu dengan Submenu Bertingkat
                  if (item.subItems && item.subItems.length > 0) {
                    // Ketika sidebar di-collapse ke mode ikon: tampilkan DropdownMenu popout di sebelah kanan
                    if (isCollapsed) {
                      return (
                        <SidebarMenuItem key={item.label}>
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <SidebarMenuButton
                                tooltip={item.label}
                                isActive={isActive}
                                className="cursor-pointer"
                              >
                                <Icon size={18} strokeWidth={1.75} />
                                <span className="group-data-[collapsible=icon]:hidden">{item.label}</span>
                              </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                              side="right"
                              align="start"
                              sideOffset={10}
                              className="min-w-52 rounded-xl bg-card p-1.5 shadow-xl border border-border"
                            >
                              <DropdownMenuLabel className="text-xs font-bold text-muted-foreground uppercase px-2.5 py-1.5 tracking-wider">
                                {item.label}
                              </DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              {item.subItems.map((subItem) => (
                                <DropdownMenuItem key={subItem.href} asChild>
                                  <Link
                                    href={subItem.href}
                                    className={cn(
                                      'flex items-center gap-2 px-2.5 py-2 text-xs font-medium rounded-md cursor-pointer transition-colors',
                                      tautanAktif(pathSekarang, subItem.href)
                                        ? 'bg-teknisi-700 text-white font-semibold'
                                        : 'text-foreground hover:bg-permukaan-100',
                                    )}
                                  >
                                    <span>{subItem.label}</span>
                                  </Link>
                                </DropdownMenuItem>
                              ))}
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </SidebarMenuItem>
                      );
                    }

                    // Ketika sidebar dalam mode expanded: tampilkan Collapsible inline accordion
                    return (
                      <Collapsible
                        key={item.label}
                        asChild
                        defaultOpen={isActive}
                        className="group/collapsible"
                      >
                        <SidebarMenuItem>
                          <CollapsibleTrigger asChild>
                            <SidebarMenuButton
                              tooltip={item.label}
                              isActive={isActive}
                              className="cursor-pointer"
                            >
                              <Icon size={18} strokeWidth={1.75} />
                              <span className="group-data-[collapsible=icon]:hidden">{item.label}</span>
                              <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90 group-data-[collapsible=icon]:hidden" />
                            </SidebarMenuButton>
                          </CollapsibleTrigger>
                          <CollapsibleContent>
                            <SidebarMenuSub>
                              {item.subItems.map((subItem) => (
                                <SidebarMenuSubItem key={subItem.href}>
                                  <SidebarMenuSubButton
                                    asChild
                                    isActive={tautanAktif(pathSekarang, subItem.href)}
                                  >
                                    <Link href={subItem.href}>
                                      <span>{subItem.label}</span>
                                    </Link>
                                  </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                              ))}
                            </SidebarMenuSub>
                          </CollapsibleContent>
                        </SidebarMenuItem>
                      </Collapsible>
                    );
                  }

                  // Kasus 2: Menu Tunggal / Biasa (Dashboard, Persetujuan Saya, Penyedia)
                  return (
                    <SidebarMenuItem key={item.href}>
                      <SidebarMenuButton
                        asChild
                        isActive={tautanAktif(pathSekarang, item.href)}
                        tooltip={item.label}
                      >
                        <Link href={item.href || '#'}>
                          <Icon size={18} strokeWidth={1.75} />
                          <span className="group-data-[collapsible=icon]:hidden">{item.label}</span>
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
                  <Avatar className="h-8 w-8 rounded-lg">
                    {auth.pengguna?.AvatarUrl ? (
                      <AvatarImage src={auth.pengguna.AvatarUrl} alt={auth.pengguna.Nama} />
                    ) : null}
                    <AvatarFallback className="rounded-lg bg-teknisi-700 text-white font-bold text-xs">
                      {getInisial(auth.pengguna?.Nama)}
                    </AvatarFallback>
                  </Avatar>
                  <div className="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                    <span className="truncate font-semibold text-white">
                      {auth.pengguna?.Nama ?? 'Pengguna'}
                    </span>
                    <span className="truncate text-xs text-sidebar-foreground/70">
                      {auth.pengguna?.Email ?? ''}
                    </span>
                  </div>
                  <ChevronsUpDown className="ml-auto size-4 group-data-[collapsible=icon]:hidden text-sidebar-foreground/70" />
                </SidebarMenuButton>
              </DropdownMenuTrigger>
              <DropdownMenuContent
                className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg bg-card shadow-lg border border-border"
                side={isCollapsed ? 'right' : 'bottom'}
                align="end"
                sideOffset={isCollapsed ? 10 : 4}
              >
                <DropdownMenuLabel className="p-0 font-normal">
                  <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <Avatar className="h-8 w-8 rounded-lg">
                      {auth.pengguna?.AvatarUrl ? (
                        <AvatarImage src={auth.pengguna.AvatarUrl} alt={auth.pengguna.Nama} />
                      ) : null}
                      <AvatarFallback className="rounded-lg bg-teknisi-700 text-white font-bold text-xs">
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
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  onClick={keluar}
                  className="flex items-center gap-2 text-destructive cursor-pointer focus:text-destructive focus:bg-destructive/10"
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

function KerangkaAplikasi({ children }: PropsWithChildren) {
  const page = usePage<PageProps>();
  const { auth } = page.props;
  const pathSekarang = page.url.split('?')[0];
  const { boleh } = useIzin();
  const { bolehFitur } = useEntitlement();
  const konfirmasi = useKonfirmasi();
  const {
    bersihkanDataLokal,
    dorong,
    jumlahBelumTersinkron,
    adaPembaruanAplikasi,
    terapkanPembaruanAplikasi,
  } = useSinkronisasiOffline();

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

  return (
    <SidebarProvider>
      <AppSidebar
        grupTampil={grupTampil}
        pathSekarang={pathSekarang}
        auth={auth}
        boleh={boleh}
        keluar={() => void keluar()}
      />
      <SidebarInset>
        <header className="flex h-14 items-center justify-between gap-3 border-b border-border bg-card px-4 sm:px-6">
          <div className="flex items-center gap-2">
            <SidebarTrigger />
          </div>
          <div className="flex min-w-0 items-center gap-3">
            {adaPembaruanAplikasi && (
              <button
                type="button"
                onClick={terapkanPembaruanAplikasi}
                title="Versi baru tersedia. Muat ulang untuk memakainya."
                className="inline-flex items-center gap-1.5 rounded-[5px] border border-info-600/25 bg-info-600/10 px-2 py-1 text-xs font-medium text-info-600"
              >
                <RotateCw className="size-3.5 shrink-0" />
                <span className="hidden sm:inline">Versi baru tersedia</span>
              </button>
            )}
            <IndikatorSinkronisasi />
            <NotificationBell />
          </div>
        </header>
        <div className="p-4 sm:p-6">{children}</div>
      </SidebarInset>
    </SidebarProvider>
  );
}

export default function AppLayout({ children }: PropsWithChildren) {
  return (
    <PenyediaSinkronisasiOffline>
      <KerangkaAplikasi>{children}</KerangkaAplikasi>
    </PenyediaSinkronisasiOffline>
  );
}
