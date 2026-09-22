import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { http } from '@/lib/http';
import { BarisKosong, KartuAngka, KepalaBagian, durasi, tanggal } from '@/components/shared/riwayat';
import type { BebanKerjaPengguna, Pengguna } from '@/features/Pengguna/types';
import { rutePengguna } from '@/features/Pengguna/api';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { ruteAset } from '@/features/Aset/api';

export function TabBebanKerja({ pengguna }: { pengguna: Pengguna }) {
  const [data, setData] = useState<BebanKerjaPengguna | null>(null);
  const [memuat, setMemuat] = useState(true);

  useEffect(() => {
    setMemuat(true);
    http
      .get(rutePengguna.bebanKerja(pengguna.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  }, [pengguna.Id]);

  if (memuat) {
    return <p className="py-4 text-sm text-muted-foreground">Memuat beban kerja...</p>;
  }

  if (!data) {
    return <p className="py-4 text-sm text-muted-foreground">Beban kerja tidak dapat dimuat.</p>;
  }

  const { ringkasan, penugasan, waktuKerja, tanggungJawabAset } = data;

  return (
    <div className="space-y-8">
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <KartuAngka
          label="Penugasan Berjalan"
          nilai={ringkasan.JumlahPenugasanBerjalan}
          catatan={`dari ${ringkasan.JumlahPenugasan} penugasan`}
        />
        <KartuAngka label="Total Waktu Kerja" nilai={durasi(ringkasan.TotalMenitKerja)} />
        <KartuAngka label="Aset Ditanggung" nilai={ringkasan.JumlahAsetDitanggung} />
        <KartuAngka label="Catatan Waktu" nilai={waktuKerja.total} />
      </div>

      <section className="space-y-1">
        <KepalaBagian judul="Penugasan" ditampilkan={penugasan.data.length} total={penugasan.total} />
        {penugasan.data.length === 0 ? (
          <BarisKosong teks="Pengguna ini belum pernah ditugaskan pada perintah kerja." />
        ) : (
          <ul className="divide-y divide-border">
            {penugasan.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <Link
                    href={rutePerintahKerja.detail(satu.PerintahKerjaId)}
                    className="text-sm font-medium text-foreground hover:underline"
                  >
                    {satu.Judul ?? satu.Nomor ?? 'Perintah kerja'}
                  </Link>
                  <div className="font-mono text-xs text-muted-foreground">
                    {satu.Nomor} · sebagai {satu.PeranTugas}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  {satu.StatusPerintahKerja && <Badge variant="outline">{satu.StatusPerintahKerja}</Badge>}
                  <Badge variant={satu.SelesaiPada ? 'sukses' : 'perhatian'}>{satu.Status}</Badge>
                  <span className="w-24 text-right text-xs text-muted-foreground">
                    {tanggal(satu.SelesaiPada ?? satu.DitugaskanPada)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian judul="Waktu Kerja" ditampilkan={waktuKerja.data.length} total={waktuKerja.total} />
        {waktuKerja.data.length === 0 ? (
          <BarisKosong teks="Belum ada waktu kerja yang tercatat." />
        ) : (
          <ul className="divide-y divide-border">
            {waktuKerja.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <Link
                    href={rutePerintahKerja.detail(satu.PerintahKerjaId)}
                    className="font-mono text-sm text-foreground hover:underline"
                  >
                    {satu.Nomor ?? 'Perintah kerja'}
                  </Link>
                  <div className="text-xs text-muted-foreground">
                    {tanggal(satu.MulaiPada)}
                    {satu.SelesaiPada ? ` s/d ${tanggal(satu.SelesaiPada)}` : ' · belum ditutup'}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <Badge variant="outline">{satu.JenisWaktu}</Badge>
                  <span className="w-28 text-right text-sm tabular-nums text-foreground">
                    {durasi(satu.DurasiMenit)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian
          judul="Tanggung Jawab Aset"
          ditampilkan={tanggungJawabAset.data.length}
          total={tanggungJawabAset.total}
        />
        {tanggungJawabAset.data.length === 0 ? (
          <BarisKosong teks="Pengguna ini belum pernah menjadi penanggung jawab aset." />
        ) : (
          <ul className="divide-y divide-border">
            {tanggungJawabAset.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <Link
                    href={ruteAset.detail(satu.AsetId)}
                    className="text-sm font-medium text-foreground hover:underline"
                  >
                    {satu.Aset ?? 'Aset'}
                  </Link>
                  <div className="font-mono text-xs text-muted-foreground">
                    {satu.KodeAset}
                    {satu.UnitOrganisasi && ` · ${satu.UnitOrganisasi}`}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  <Badge variant={satu.SelesaiPada ? 'netral' : 'sukses'}>
                    {satu.SelesaiPada ? 'Selesai' : 'Berjalan'}
                  </Badge>
                  <span className="w-44 text-right text-xs text-muted-foreground">
                    {tanggal(satu.MulaiPada)}
                    {satu.SelesaiPada ? ` s/d ${tanggal(satu.SelesaiPada)}` : ' s/d sekarang'}
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
