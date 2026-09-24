import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { http } from '@/lib/http';
import { formatUang } from '@/lib/uang';
import { BarisKosong, KepalaBagian, tanggal } from '@/components/shared/riwayat';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import type { Penyedia, RiwayatLayananPenyedia } from '@/features/Penyedia/types';
import { rutePenyedia } from '@/features/Penyedia/api';
import { ruteKontrak } from '@/features/Kontrak/api';
import { ruteAset } from '@/features/Aset/api';

export function TabLayanan({ penyedia }: { penyedia: Penyedia }) {
  const [data, setData] = useState<RiwayatLayananPenyedia | null>(null);
  const [memuat, setMemuat] = useState(true);

  useEffect(() => {
    setMemuat(true);
    http
      .get(rutePenyedia.riwayatLayanan(penyedia.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  }, [penyedia.Id]);

  if (memuat) {
    return <p className="py-4 text-sm text-muted-foreground">Memuat riwayat layanan...</p>;
  }

  if (!data) {
    return <p className="py-4 text-sm text-muted-foreground">Riwayat layanan tidak dapat dimuat.</p>;
  }

  const { ringkasan, kontrak, aset, kalibrasi } = data;

  return (
    <div className="space-y-5">
      <DeretStatistik kolom={4}>
        <KartuStatistik
          menyatu
          label="Kontrak Aktif"
          nilai={ringkasan.JumlahKontrakAktif}
          keterangan={`dari ${ringkasan.JumlahKontrak} kontrak`}
        />
        <KartuStatistik menyatu label="Nilai Kontrak Aktif" nilai={formatUang(ringkasan.NilaiKontrakAktif)} />
        <KartuStatistik menyatu label="Aset Dipasok" nilai={ringkasan.JumlahAset} />
        <KartuStatistik menyatu label="Kalibrasi Dikerjakan" nilai={ringkasan.JumlahKalibrasi} />
      </DeretStatistik>

      <section className="space-y-1">
        <KepalaBagian judul="Kontrak" ditampilkan={kontrak.data.length} total={kontrak.total} />
        {kontrak.data.length === 0 ? (
          <BarisKosong teks="Belum ada kontrak dengan penyedia ini." />
        ) : (
          <ul className="divide-y divide-border">
            {kontrak.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <Link
                    href={ruteKontrak.detail(satu.Id)}
                    className="text-sm font-medium text-foreground hover:underline"
                  >
                    {satu.Nama}
                  </Link>
                  <div className="font-mono text-xs text-muted-foreground">
                    {satu.Nomor} · {satu.Jenis}
                    {satu.TingkatLayanan && ` · SLA ${satu.TingkatLayanan}`}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <Badge variant={satu.Status === 'Aktif' ? 'sukses' : 'netral'}>{satu.Status}</Badge>
                  {satu.Nilai !== null && (
                    <span className="text-sm tabular-nums text-foreground">
                      {formatUang(satu.Nilai, satu.MataUang)}
                    </span>
                  )}
                  <span className="w-44 text-right text-xs text-muted-foreground">
                    {tanggal(satu.MulaiPada)} s/d {tanggal(satu.BerakhirPada)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian judul="Aset Dipasok" ditampilkan={aset.data.length} total={aset.total} />
        {aset.data.length === 0 ? (
          <BarisKosong teks="Belum ada aset yang tercatat dibeli dari penyedia ini." />
        ) : (
          <ul className="divide-y divide-border">
            {aset.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <Link
                    href={ruteAset.detail(satu.Id)}
                    className="text-sm font-medium text-foreground hover:underline"
                  >
                    {satu.Nama}
                  </Link>
                  <div className="font-mono text-xs text-muted-foreground">
                    {satu.KodeAset}
                    {satu.Kategori && ` · ${satu.Kategori}`}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <Badge variant="outline">{satu.Kondisi}</Badge>
                  <Badge variant="secondary">{satu.Status}</Badge>
                  {satu.HargaPerolehan !== null && (
                    <span className="text-sm tabular-nums text-foreground">
                      {formatUang(satu.HargaPerolehan)}
                    </span>
                  )}
                  <span className="w-24 text-right text-xs text-muted-foreground">
                    {tanggal(satu.TanggalPerolehan)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian judul="Kalibrasi" ditampilkan={kalibrasi.data.length} total={kalibrasi.total} />
        {kalibrasi.data.length === 0 ? (
          <BarisKosong teks="Penyedia ini belum pernah mengerjakan kalibrasi." />
        ) : (
          <ul className="divide-y divide-border">
            {kalibrasi.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  {satu.AsetId ? (
                    <Link
                      href={ruteAset.detail(satu.AsetId)}
                      className="text-sm font-medium text-foreground hover:underline"
                    >
                      {satu.Aset ?? satu.Nomor}
                    </Link>
                  ) : (
                    <span className="text-sm text-foreground">{satu.Aset ?? satu.Nomor}</span>
                  )}
                  <div className="font-mono text-xs text-muted-foreground">
                    {satu.Nomor}
                    {satu.JenisKalibrasi && ` · ${satu.JenisKalibrasi}`}
                    {satu.NomorSertifikat && ` · sertifikat ${satu.NomorSertifikat}`}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <Badge variant={satu.Hasil === 'Lolos' ? 'sukses' : 'bahaya'}>{satu.Hasil}</Badge>
                  <span className="w-24 text-right text-xs text-muted-foreground">
                    {tanggal(satu.TanggalKalibrasi)}
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
