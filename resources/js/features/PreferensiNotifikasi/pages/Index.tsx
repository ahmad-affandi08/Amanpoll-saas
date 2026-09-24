import { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Switch } from '@/components/ui/switch';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { http } from '@/lib/http';
import type { KesiapanWhatsApp, PreferensiBaris } from '@/features/Notifikasi/types';
import { KANAL_NOTIFIKASI } from '@/features/Notifikasi/status';
import { ruteProfil } from '@/features/Profil/api';
import { rutePreferensiNotifikasi } from '@/features/PreferensiNotifikasi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

export default function PreferensiNotifikasiIndex() {
  const [data, setData] = useState<PreferensiBaris[]>([]);
  const [whatsApp, setWhatsApp] = useState<KesiapanWhatsApp | null>(null);
  const [memuat, setMemuat] = useState(true);

  const muat = () => {
    setMemuat(true);
    http
      .get(rutePreferensiNotifikasi.data)
      .then((res) => {
        setData(res.data.data);
        setWhatsApp(res.data.whatsapp);
      })
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
    <KerangkaAplikasi>
      <Head title="Preferensi Notifikasi" />
      <KepalaHalaman
        judul="Preferensi Notifikasi"
        deskripsi="Atur peristiwa mana yang ingin Anda terima melalui tiap kanal notifikasi."
        className="mb-5"
      />

      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}

      {!memuat && whatsApp && !whatsApp.PenyediaAktif && (
        <Alert variant="info" className="mb-4">
          <AlertTitle>WhatsApp belum aktif</AlertTitle>
          <AlertDescription>
            Penyedia WhatsApp belum diaktifkan oleh pengelola platform. Pilihan WhatsApp tetap tersimpan dan
            berlaku begitu penyedianya aktif.
          </AlertDescription>
        </Alert>
      )}

      {!memuat && whatsApp?.PenyediaAktif && !whatsApp.NomorValid && (
        <Alert variant="perhatian" className="mb-4">
          <AlertTitle>Nomor telepon belum diisi</AlertTitle>
          <AlertDescription>
            Notifikasi WhatsApp dikirim ke nomor telepon di profil Anda.{' '}
            <Link href={ruteProfil.index} className="font-medium underline">
              Isi nomor telepon
            </Link>{' '}
            agar pesannya sampai.
          </AlertDescription>
        </Alert>
      )}

      {!memuat && (
        <div className="overflow-hidden rounded-md border border-border bg-card">
          <table className="w-full text-sm">
            <thead className="bg-permukaan-50 text-[12.5px] text-grafit-500">
              <tr>
                <th className="h-10 px-4 text-left font-medium">Peristiwa</th>
                {KANAL_NOTIFIKASI.map((kanal) => (
                  <th key={kanal.value} className="h-10 px-4 text-center font-medium">
                    {kanal.label}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {peristiwaUnik.map((jenisPeristiwa) => {
                const barisPeristiwa = data.filter((b) => b.JenisPeristiwa === jenisPeristiwa);
                return (
                  <tr key={jenisPeristiwa} className="border-t border-border">
                    <td className="px-4 py-3 text-foreground">
                      {barisPeristiwa[0]?.Label ?? jenisPeristiwa}
                    </td>
                    {KANAL_NOTIFIKASI.map((kanal) => {
                      const baris = barisPeristiwa.find((b) => b.Kanal === kanal.value);
                      return (
                        <td key={kanal.value} className="px-4 py-3 text-center">
                          {baris && (
                            <Switch
                              checked={baris.Aktif}
                              onCheckedChange={(v) => ubah(baris, v)}
                              aria-label={`${baris.Label} lewat ${kanal.label}`}
                            />
                          )}
                        </td>
                      );
                    })}
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </KerangkaAplikasi>
  );
}
