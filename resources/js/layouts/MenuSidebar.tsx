import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
  useSidebar,
} from '@/components/ui/sidebar';
import type { GrupNav, ItemNav } from '@/layouts/navigasi';

export function tautanAktif(pathSekarang: string, href?: string, tepat = false): boolean {
  if (!href) return false;
  if (href === '/' || tepat) return pathSekarang === href;
  return pathSekarang === href || pathSekarang.startsWith(`${href}/`);
}

/**
 * Satu submenu saja yang menyala: yang jalurnya paling panjang di antara yang cocok,
 * supaya `/prospek` tidak ikut menyala di halaman `/prospek/aturan-skor`.
 */
function hrefSubAktif(pathSekarang: string, item: ItemNav): string | undefined {
  return (item.subItems ?? [])
    .filter((sub) => tautanAktif(pathSekarang, sub.href, sub.tepat))
    .sort((a, b) => b.href.length - a.href.length)[0]?.href;
}

function apakahGrupItemAktif(pathSekarang: string, item: ItemNav): boolean {
  if (item.href && tautanAktif(pathSekarang, item.href, item.tepat)) return true;
  if (item.subItems) {
    return item.subItems.some((sub) => tautanAktif(pathSekarang, sub.href, sub.tepat));
  }
  return false;
}

interface PropsMenuSidebar {
  grup: GrupNav[];
  pathSekarang: string;
}

/**
 * Isi menu sidebar bergrup, dipakai bersama dashboard tenant dan konsol platform supaya
 * keduanya sama persis. Submenu membuka sebagai akordeon; saat sidebar diciutkan ke
 * ikon, submenu muncul sebagai menu melayang di kanan.
 */
export function MenuSidebar({ grup, pathSekarang }: PropsMenuSidebar) {
  const { state, isMobile } = useSidebar();
  const isCollapsed = state === 'collapsed' && !isMobile;

  return (
    <>
      {grup.map((satuGrup, i) => (
        <SidebarGroup key={satuGrup.label ?? `utama-${i}`}>
          {satuGrup.label && (
            <SidebarGroupLabel className="group-data-[collapsible=icon]:hidden">
              {satuGrup.label}
            </SidebarGroupLabel>
          )}
          <SidebarGroupContent>
            <SidebarMenu>
              {satuGrup.items.map((item) => {
                const Icon = item.icon;
                const isActive = apakahGrupItemAktif(pathSekarang, item);
                const subAktif = hrefSubAktif(pathSekarang, item);

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
                              <Icon size={16} strokeWidth={1.75} />
                              <span className="group-data-[collapsible=icon]:hidden">{item.label}</span>
                            </SidebarMenuButton>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent
                            side="right"
                            align="start"
                            sideOffset={10}
                            className="min-w-52 p-1"
                          >
                            <DropdownMenuLabel className="px-2 py-1.5 text-xs font-medium text-muted-foreground">
                              {item.label}
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            {item.subItems.map((subItem) => (
                              <DropdownMenuItem key={subItem.href} asChild>
                                <Link
                                  href={subItem.href}
                                  className={cn(
                                    'flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-[13px] transition-colors',
                                    subAktif === subItem.href
                                      ? 'bg-accent font-medium text-accent-foreground'
                                      : 'text-foreground',
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
                            <Icon size={16} strokeWidth={1.75} />
                            <span className="group-data-[collapsible=icon]:hidden">{item.label}</span>
                            <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90 group-data-[collapsible=icon]:hidden" />
                          </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                          <SidebarMenuSub>
                            {item.subItems.map((subItem) => (
                              <SidebarMenuSubItem key={subItem.href}>
                                <SidebarMenuSubButton asChild isActive={subAktif === subItem.href}>
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
                      isActive={tautanAktif(pathSekarang, item.href, item.tepat)}
                      tooltip={item.label}
                    >
                      <Link href={item.href || '#'}>
                        <Icon size={16} strokeWidth={1.75} />
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
    </>
  );
}
