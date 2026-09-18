import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import type { PageProps } from '@/types/global';

const menu = [
  ['Dashboard', '/'], ['Aset', '/aset'], ['Perintah Kerja', '/pemeliharaan'],
  ['Kalibrasi', '/kalibrasi'], ['Persediaan', '/persediaan'],
  ['Pengadaan', '/perencanaan-pengadaan'], ['Laporan', '/pelaporan'],
];

export default function AppLayout({ children }: PropsWithChildren) {
  const { auth } = usePage<PageProps>().props;
  return (
    <div className="min-h-screen bg-zinc-50">
      <div className="flex min-h-screen">
        <aside className="hidden w-64 border-r bg-white p-5 lg:block">
          <div className="mb-8 text-xl font-semibold">Amanpoll</div>
          <nav className="space-y-1">
            {menu.map(([label, href]) => (
              <Link key={href} href={href} className="block rounded-md px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100">
                {label}
              </Link>
            ))}
          </nav>
        </aside>
        <main className="min-w-0 flex-1">
          <header className="flex h-16 items-center justify-between border-b bg-white px-6">
            <div className="font-medium">Asset & Maintenance Management</div>
            <div className="text-sm text-zinc-600">{auth.pengguna?.Nama ?? ''}</div>
          </header>
          <div className="p-6">{children}</div>
        </main>
      </div>
    </div>
  );
}
