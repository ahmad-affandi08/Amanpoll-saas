import { usePage } from '@inertiajs/react';
import { Breadcrumb } from '@/components/ui/breadcrumb';
import { breadcrumbUntuk } from '@/layouts/breadcrumb-otomatis';

/** Breadcrumb mandiri untuk halaman yang kepala halamannya berupa banner kartu sendiri (DESIGN.md 12). */
export function BreadcrumbHalaman({ label }: { label?: string }) {
  const { url } = usePage();

  return <Breadcrumb jejak={breadcrumbUntuk(url, label)} className="mb-3" />;
}
