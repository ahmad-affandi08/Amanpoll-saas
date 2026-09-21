import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Switch } from '@/components/ui/switch';
import { http } from '@/lib/http';
import type { PreferensiBaris } from '@/features/Notifikasi/types';
import { rutePreferensiNotifikasi } from '@/features/PreferensiNotifikasi/api';
import { PageHeader } from '@/components/shared/PageHeader';

export default function PreferensiNotifikasiIndex() {
  const [data, setData] = useState<PreferensiBaris[]>([]);
  const [memuat, setMemuat] = useState(true);

  const muat = () => {
    setMemuat(true);
    http
      .get(rutePreferensiNotifikasi.data)
      .then((res) => setData(res.data.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, []);

  const ubah = (baris: PreferensiBaris, aktif: boolean) => {
    setData((prev) =>
      prev.map((b) =>
        b.JenisPeristiwa === baris.JenisPeristiwa && b.Kanal === baris.Kanal ? { ...b, Aktif: aktif } : b,
      ),
    );
    router.post(
      rutePreferensiNotifikasi.index,
      {
        JenisPeristiwa: baris.JenisPeristiwa,
        Kanal: baris.Kanal,
        Aktif: aktif,
      },
      { preserveScroll: true, preserveState: true },
    );
  };

  const peristiwaUnik = Array.from(new Set(data.map((b) => b.JenisPeristiwa)));

  return (
    <AppLayout>
      <Head title="Preferensi Notifikasi" />
      <PageHeader
        judul="Preferensi Notifikasi"
        deskripsi="Atur peristiwa mana yang ingin Anda terima melalui tiap kanal notifikasi."
      />

      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}

      {!memuat && (
        <div className="overflow-hidden rounded-md border border-border">
          <table className="w-full text-sm">
            <thead className="bg-muted/50">
              <tr>
                <th className="px-4 py-2 text-left font-medium text-foreground">Peristiwa</th>
                <th className="px-4 py-2 text-center font-medium text-foreground">In-App</th>
                <th className="px-4 py-2 text-center font-medium text-foreground">Email</th>
              </tr>
            </thead>
            <tbody>
              {peristiwaUnik.map((jenisPeristiwa) => {
                const barisInApp = data.find(
                  (b) => b.JenisPeristiwa === jenisPeristiwa && b.Kanal === 'InApp',
                );
                const barisEmail = data.find(
                  (b) => b.JenisPeristiwa === jenisPeristiwa && b.Kanal === 'Email',
                );
                return (
                  <tr key={jenisPeristiwa} className="border-t border-border">
                    <td className="px-4 py-3 text-foreground">
                      {barisInApp?.Label ?? barisEmail?.Label ?? jenisPeristiwa}
                    </td>
                    <td className="px-4 py-3 text-center">
                      {barisInApp && (
                        <Switch checked={barisInApp.Aktif} onCheckedChange={(v) => ubah(barisInApp, v)} />
                      )}
                    </td>
                    <td className="px-4 py-3 text-center">
                      {barisEmail && (
                        <Switch checked={barisEmail.Aktif} onCheckedChange={(v) => ubah(barisEmail, v)} />
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </AppLayout>
  );
}
