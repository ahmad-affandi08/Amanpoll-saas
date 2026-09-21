import { usePage } from '@inertiajs/react';
import { Breadcrumb } from '@/components/ui/breadcrumb';
import { breadcrumbUntuk } from '@/layouts/breadcrumb-otomatis';

/**
 * Breadcrumb mandiri untuk halaman yang kepala halamannya berupa banner kartu
 * sendiri (DESIGN.md 12).
 *
 * Halaman seperti pelaksanaan daftar periksa dan inspeksi menampilkan identitas
 * entitas di dalam kartu ringkasan bersama metadatanya. Memaksanya memakai
 * PageHeader akan meratakan susunan yang memang disengaja, jadi yang diambil
 * hanya breadcrumb-nya — tetap diturunkan dari peta navigasi yang sama.
 */
export function BreadcrumbHalaman({ label }: { label?: string }) {
  const { url } = usePage();

  return <Breadcrumb jejak={breadcrumbUntuk(url, label)} className="mb-3" />;
}
