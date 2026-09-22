import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { http } from '@/lib/http';
import { BarisKosong, KartuAngka, KepalaBagian } from '@/components/shared/riwayat';
import type { AktivitasPengguna, Pengguna } from '@/features/Pengguna/types';
import { rutePengguna } from '@/features/Pengguna/api';

/** Catatan akses butuh jam, bukan hanya tanggal; itulah yang membedakan dua percobaan masuk. */
function waktu(nilai: string | null): string {
  if (!nilai) {
    return '—';
  }

  return new Date(nilai).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function TabAktivitas({ pengguna }: { pengguna: Pengguna }) {
  const [data, setData] = useState<AktivitasPengguna | null>(null);
  const [memuat, setMemuat] = useState(true);

  useEffect(() => {
    setMemuat(true);
    http
      .get(rutePengguna.aktivitas(pengguna.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  }, [pengguna.Id]);

  if (memuat) {
    return <p className="py-4 text-sm text-muted-foreground">Memuat aktivitas...</p>;
  }

  if (!data) {
    return <p className="py-4 text-sm text-muted-foreground">Aktivitas tidak dapat dimuat.</p>;
  }

  const { ringkasan, akses, perangkat } = data;

  return (
    <div className="space-y-8">
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <KartuAngka label="Terakhir Masuk" nilai={waktu(ringkasan.TerakhirMasukPada)} />
        <KartuAngka label="Catatan Akses" nilai={ringkasan.JumlahAkses} />
        <KartuAngka
          label="Akses Gagal"
          nilai={ringkasan.JumlahAksesGagal}
          catatan={ringkasan.JumlahAksesGagal > 0 ? 'periksa bila tidak dikenali' : undefined}
        />
        <KartuAngka label="Perangkat Terdaftar" nilai={ringkasan.JumlahPerangkat} />
      </div>

      <section className="space-y-1">
        <KepalaBagian judul="Riwayat Akses" ditampilkan={akses.data.length} total={akses.total} />
        {akses.data.length === 0 ? (
          <BarisKosong teks="Belum ada catatan akses untuk pengguna ini." />
        ) : (
          <ul className="divide-y divide-border">
            {akses.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <div className="text-sm text-foreground">{satu.Jenis}</div>
                  <div className="font-mono text-xs text-muted-foreground">
                    {satu.AlamatIp ?? 'tanpa alamat IP'}
                    {satu.AlasanGagal && ` · ${satu.AlasanGagal}`}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <Badge variant={satu.Berhasil ? 'sukses' : 'bahaya'}>
                    {satu.Berhasil ? 'Berhasil' : 'Gagal'}
                  </Badge>
                  <span className="w-40 text-right text-xs text-muted-foreground">
                    {waktu(satu.DibuatPada)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian judul="Perangkat" ditampilkan={perangkat.length} total={ringkasan.JumlahPerangkat} />
        {perangkat.length === 0 ? (
          <BarisKosong teks="Pengguna ini belum pernah mendaftarkan perangkat." />
        ) : (
          <ul className="divide-y divide-border">
            {perangkat.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <div className="text-sm text-foreground">{satu.NamaPerangkat ?? 'Tanpa nama'}</div>
                  <div className="text-xs text-muted-foreground">
                    {satu.Platform ?? 'platform tidak dikenal'}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <Badge variant={satu.Status === 'Aktif' ? 'sukses' : 'netral'}>{satu.Status}</Badge>
                  <span className="w-40 text-right text-xs text-muted-foreground">
                    sinkron {waktu(satu.TerakhirSinkronPada)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
