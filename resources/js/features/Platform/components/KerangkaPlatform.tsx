import type { PropsWithChildren } from 'react';
import { router, usePage } from '@inertiajs/react';
import { ChevronsUpDown, LogOut, ShieldCheck } from 'lucide-react';
import { LogoLambang } from '@/components/shared/Logo';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
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
import { MenuSidebar } from '@/layouts/MenuSidebar';
import type { GrupNav } from '@/layouts/navigasi';
import { rutePlatform } from '@/features/Platform/api';
import { grupNavPlatform } from '@/features/Platform/navigasi';

interface PropsPlatform {
  Nama?: string;
  Email?: string;
  SuperAdmin?: boolean;
  Izin?: string[];
  ModulAktif?: string[];
}

function inisial(nama?: string): string {
  if (!nama) return 'AP';
  const bagian = nama.trim().split(/\s+/);
  if (bagian.length === 1) return bagian[0].substring(0, 2).toUpperCase();
  return (bagian[0][0] + bagian[bagian.length - 1][0]).toUpperCase();
}

/** Menu mengikuti izin dan modul yang sama dengan backend, jadi konsol tidak menawarkan halaman yang akan ditolak. */
function saringMenu(grup: GrupNav[], boleh: (kode: string) => boolean, modulAktif: string[]): GrupNav[] {
  const tampil = (kodeIzin?: string | null, kodeModul?: string | null) =>
    (!kodeIzin || boleh(kodeIzin)) && (!kodeModul || modulAktif.includes(kodeModul));

  return grup
    .map((satu) => ({
      ...satu,
      items: satu.items
        .filter((item) => tampil(item.kodeIzin, item.kodeFitur))
        .map((item) =>
          item.subItems
            ? { ...item, subItems: item.subItems.filter((sub) => tampil(sub.kodeIzin, sub.kodeFitur)) }
            : item,
        )
        .filter((item) => !item.subItems || item.subItems.length > 0),
    }))
    .filter((satu) => satu.items.length > 0);
}

function ProfilAdmin({ platform }: { platform: PropsPlatform }) {
  const { state, isMobile } = useSidebar();
  const diciutkan = state === 'collapsed' && !isMobile;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <SidebarMenuButton
          size="lg"
          className="cursor-pointer data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
        >
          <Avatar className="h-8 w-8 rounded-lg">
            <AvatarFallback className="rounded-lg bg-teknisi-700 text-xs font-bold text-white">
              {inisial(platform.Nama)}
            </AvatarFallback>
          </Avatar>
          <div className="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
            <span className="truncate font-semibold text-white">{platform.Nama ?? 'Admin platform'}</span>
            <span className="truncate text-xs text-sidebar-foreground">{platform.Email ?? ''}</span>
          </div>
          <ChevronsUpDown className="ml-auto size-4 text-sidebar-foreground/70 group-data-[collapsible=icon]:hidden" />
        </SidebarMenuButton>
      </DropdownMenuTrigger>
      <DropdownMenuContent
        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg border border-border bg-card shadow-lg"
        side={diciutkan ? 'right' : 'bottom'}
        align="end"
        sideOffset={diciutkan ? 10 : 4}
      >
        <DropdownMenuLabel className="font-normal">
          <span className="block truncate text-sm font-semibold text-foreground">
            {platform.Nama ?? 'Admin platform'}
          </span>
          <span className="block truncate text-xs text-muted-foreground">
            {platform.SuperAdmin ? 'Super admin platform' : 'Admin platform'}
          </span>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem
          variant="destructive"
          onClick={() => router.post(rutePlatform.logout)}
          className="flex cursor-pointer items-center gap-2"
        >
          <LogOut className="size-4" />
          <span>Keluar</span>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

/**
 * Kerangka konsol platform. Memakai sidebar dan menu yang sama dengan dashboard tenant
 * (`MenuSidebar`), dibedakan oleh label "Konsol Platform" di kepala sidebar dan header.
 */
export function KerangkaPlatform({ children }: PropsWithChildren) {
  const { url, props } = usePage<{ platform?: PropsPlatform }>();
  const platform = props.platform ?? {};
  const pathSekarang = url.split('?')[0];
  const boleh = (kode: string) => platform.SuperAdmin === true || (platform.Izin ?? []).includes(kode);

  return (
    <SidebarProvider>
      <Sidebar collapsible="icon">
        <SidebarHeader>
          <SidebarMenu>
            <SidebarMenuItem>
              <SidebarMenuButton
                size="lg"
                className="cursor-default hover:bg-transparent active:bg-transparent"
              >
                <div className="flex aspect-square size-8 shrink-0 items-center justify-center rounded-lg bg-teknisi-800 p-1 text-sidebar-primary-foreground">
                  <LogoLambang className="size-full object-contain" />
                </div>
                <div className="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                  <span className="truncate text-base font-bold tracking-tight text-white">Amanpoll</span>
                  <span className="truncate text-[11px] leading-tight font-medium text-sidebar-foreground">
                    Konsol Platform
                  </span>
                </div>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
          <MenuSidebar
            grup={saringMenu(grupNavPlatform, boleh, platform.ModulAktif ?? [])}
            pathSekarang={pathSekarang}
          />
        </SidebarContent>

        <SidebarFooter>
          <SidebarMenu>
            <SidebarMenuItem>
              <ProfilAdmin platform={platform} />
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarFooter>
      </Sidebar>

      <SidebarInset>
        <header className="flex h-14 items-center gap-3 border-b border-border bg-card px-4 sm:px-6">
          <SidebarTrigger />
          <span className="flex items-center gap-2 text-sm font-medium text-grafit-700">
            <ShieldCheck aria-hidden="true" className="size-4 text-primary" />
            Konsol Platform
          </span>
        </header>
        <div className="p-4 sm:p-6">{children}</div>
      </SidebarInset>
    </SidebarProvider>
  );
}
