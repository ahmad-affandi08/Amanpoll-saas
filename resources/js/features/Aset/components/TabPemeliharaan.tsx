import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { http } from '@/lib/http';
import type { Aset, RiwayatPemeliharaanAset } from '@/features/Aset/types';
import { ruteAset } from '@/features/Aset/api';
import { ruteKeluhan } from '@/features/Keluhan/api';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { BarisKosong, KartuAngka, KepalaBagian, durasi, tanggal } from '@/components/shared/riwayat';

export function TabPemeliharaan({ aset }: { aset: Aset }) {
  const [data, setData] = useState<RiwayatPemeliharaanAset | null>(null);
  const [memuat, setMemuat] = useState(true);

  useEffect(() => {
    setMemuat(true);
    http
      .get(ruteAset.riwayatPemeliharaan(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  }, [aset.Id]);

  if (memuat) {
    return <p className="py-4 text-sm text-muted-foreground">Memuat riwayat pemeliharaan...</p>;
  }

  if (!data) {
    return <p className="py-4 text-sm text-muted-foreground">Riwayat pemeliharaan tidak dapat dimuat.</p>;
  }

  const { ringkasan, keluhan, perintahKerja, inspeksi, waktuHenti } = data;

  return (
    <div className="space-y-8">
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <KartuAngka label="Keluhan" nilai={ringkasan.JumlahKeluhan} />
        <KartuAngka label="Perintah Kerja" nilai={ringkasan.JumlahPerintahKerja} />
        <KartuAngka label="Total Waktu Henti" nilai={durasi(ringkasan.TotalMenitHenti)} />
        <KartuAngka
          label="Terakhir Dikerjakan"
          nilai={tanggal(ringkasan.TerakhirDikerjakanPada)}
          catatan={ringkasan.JumlahInspeksi > 0 ? `${ringkasan.JumlahInspeksi} inspeksi tercatat` : undefined}
        />
      </div>

      <section className="space-y-1">
        <KepalaBagian judul="Keluhan" ditampilkan={keluhan.data.length} total={keluhan.total} />
        {keluhan.data.length === 0 ? (
          <BarisKosong teks="Aset ini belum pernah dikeluhkan." />
        ) : (
          <ul className="divide-y divide-border">
            {keluhan.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <Link
                    href={ruteKeluhan.detail(satu.Id)}
                    className="text-sm font-medium text-foreground hover:underline"
                  >
                    {satu.Judul}
                  </Link>
                  <div className="font-mono text-xs text-muted-foreground">
                    {satu.Nomor}
                    {satu.Kategori && ` · ${satu.Kategori}`}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  <Badge variant="outline">{satu.Prioritas}</Badge>
                  <Badge variant="secondary">{satu.Status}</Badge>
                  <span className="w-24 text-right text-xs text-muted-foreground">
                    {tanggal(satu.DilaporkanPada)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian
          judul="Perintah Kerja"
          ditampilkan={perintahKerja.data.length}
          total={perintahKerja.total}
        />
        {perintahKerja.data.length === 0 ? (
          <BarisKosong teks="Belum ada perintah kerja atas aset ini." />
        ) : (
          <ul className="divide-y divide-border">
            {perintahKerja.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <Link
                    href={rutePerintahKerja.detail(satu.Id)}
                    className="text-sm font-medium text-foreground hover:underline"
                  >
                    {satu.Judul}
                  </Link>
                  <div className="font-mono text-xs text-muted-foreground">
                    {satu.Nomor} · {satu.Jenis}
                    {!satu.Utama && ' · aset pendukung'}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  {satu.KondisiAkhir && <Badge variant="outline">{satu.KondisiAkhir}</Badge>}
                  <Badge variant="secondary">{satu.Status}</Badge>
                  <span className="w-24 text-right text-xs text-muted-foreground">
                    {tanggal(satu.DiselesaikanPada ?? satu.DijadwalkanMulaiPada)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian judul="Inspeksi" ditampilkan={inspeksi.data.length} total={inspeksi.total} />
        {inspeksi.data.length === 0 ? (
          <BarisKosong teks="Belum ada inspeksi atas aset ini." />
        ) : (
          <ul className="divide-y divide-border">
            {inspeksi.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <div className="font-mono text-sm text-foreground">{satu.Nomor}</div>
                  {satu.Temuan && <div className="truncate text-xs text-muted-foreground">{satu.Temuan}</div>}
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  {satu.Hasil && <Badge variant="outline">{satu.Hasil}</Badge>}
                  <Badge variant="secondary">{satu.Status}</Badge>
                  <span className="w-24 text-right text-xs text-muted-foreground">
                    {tanggal(satu.DilaksanakanPada ?? satu.DijadwalkanPada)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian judul="Waktu Henti" ditampilkan={waktuHenti.data.length} total={waktuHenti.total} />
        {waktuHenti.data.length === 0 ? (
          <BarisKosong teks="Aset ini belum pernah tercatat berhenti." />
        ) : (
          <ul className="divide-y divide-border">
            {waktuHenti.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <div className="text-sm text-foreground">{satu.Alasan ?? 'Tanpa keterangan'}</div>
                  <div className="text-xs text-muted-foreground">
                    {tanggal(satu.MulaiPada)}
                    {satu.SelesaiPada ? ` s/d ${tanggal(satu.SelesaiPada)}` : ' · belum selesai'}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  {satu.Jenis && <Badge variant="outline">{satu.Jenis}</Badge>}
                  <span className="w-28 text-right text-sm tabular-nums text-foreground">
                    {durasi(satu.DurasiMenit)}
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
