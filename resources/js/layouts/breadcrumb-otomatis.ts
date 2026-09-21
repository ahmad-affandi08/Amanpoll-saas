import type { JejakBreadcrumb } from '@/components/ui/breadcrumb';
import { semuaGrup } from '@/layouts/navigasi';

/**
 * Menurunkan breadcrumb dari peta navigasi (DESIGN.md 12).
 *
 * Ditulis sebagai turunan, bukan sebagai data per halaman, karena breadcrumb
 * yang ditulis tangan di tiap halaman pasti lambat laun berbeda dari menunya —
 * dan breadcrumb yang berbeda dari menu lebih menyesatkan daripada tidak ada
 * breadcrumb sama sekali.
 *
 * Ruas yang tidak dikenal peta navigasi tetap ditampilkan dari potongan URL-nya
 * supaya halaman detail tidak kehilangan konteks; pemanggil dapat menimpanya
 * dengan nama entitas yang sebenarnya.
 */

interface Simpul {
  label: string;
  href: string;
  indukLabel?: string;
}

let indeksTerbangun: Map<string, Simpul> | null = null;

function indeks(): Map<string, Simpul> {
  if (indeksTerbangun) {
    return indeksTerbangun;
  }

  const peta = new Map<string, Simpul>();

  for (const grup of semuaGrup) {
    for (const item of grup.items) {
      if (item.href) {
        peta.set(normalkan(item.href), { label: item.label, href: item.href });
      }

      for (const sub of item.subItems ?? []) {
        peta.set(normalkan(sub.href), {
          label: sub.label,
          href: sub.href,
          // Item induk tanpa href hanya menjadi label, bukan tautan: mengeklik
          // "Manajemen Aset" yang tidak punya halaman sendiri akan menyesatkan.
          indukLabel: item.href ? undefined : item.label,
        });
      }
    }
  }

  indeksTerbangun = peta;

  return peta;
}

function normalkan(href: string): string {
  const tanpaKueri = href.split('?')[0];
  const dipangkas = tanpaKueri.replace(/\/+$/, '');

  return dipangkas === '' ? '/' : dipangkas;
}

/** Mengubah potongan URL menjadi label yang layak dibaca. */
function labelDariRuas(ruas: string): string {
  const kata = decodeURIComponent(ruas).replace(/-/g, ' ');

  return kata.charAt(0).toUpperCase() + kata.slice(1);
}

/**
 * ULID dan UUID tidak pernah pantas muncul di breadcrumb; halaman detail
 * menimpanya dengan nama entitas lewat `timpaTerakhir`.
 */
function ruasIdentitas(ruas: string): boolean {
  return /^[0-9a-z]{26}$/i.test(ruas) || /^[0-9a-f-]{32,36}$/i.test(ruas);
}

export function breadcrumbUntuk(url: string, timpaTerakhir?: string): JejakBreadcrumb[] {
  const path = normalkan(url);

  if (path === '/') {
    return [{ label: 'Dashboard' }];
  }

  const peta = indeks();
  const ruas = path.split('/').filter((r) => r !== '');
  const jejak: JejakBreadcrumb[] = [{ label: 'Dashboard', href: '/' }];

  let dibangun = '';
  let indukTerakhir: string | undefined;

  ruas.forEach((satu, i) => {
    dibangun += `/${satu}`;
    const dikenal = peta.get(dibangun);
    const terakhir = i === ruas.length - 1;

    if (dikenal) {
      if (dikenal.indukLabel && dikenal.indukLabel !== indukTerakhir) {
        jejak.push({ label: dikenal.indukLabel });
        indukTerakhir = dikenal.indukLabel;
      }

      jejak.push({ label: dikenal.label, href: terakhir ? undefined : dikenal.href });

      return;
    }

    // Prefiks yang bukan halaman tersendiri (mis. "/platform" pada
    // "/platform/lokasi") dilewati supaya breadcrumb tidak memuat ruas yang
    // tidak dapat dibuka.
    const adaTurunan = [...peta.keys()].some((kunci) => kunci.startsWith(`${dibangun}/`));
    if (adaTurunan && !terakhir) {
      return;
    }

    if (ruasIdentitas(satu)) {
      jejak.push({ label: timpaTerakhir ?? 'Detail' });

      return;
    }

    jejak.push({ label: labelDariRuas(satu), href: terakhir ? undefined : dibangun });
  });

  if (timpaTerakhir && jejak.length > 0) {
    jejak[jejak.length - 1] = { label: timpaTerakhir };
  }

  return jejak;
}
