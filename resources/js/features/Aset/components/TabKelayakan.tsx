import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import { http } from '@/lib/http';
import { formatUang } from '@/lib/uang';
import { ruteAset } from '@/features/Aset/api';
import type { Aset } from '@/features/Aset/types';

interface Kelayakan {
  HargaPerolehan: number;
  UsiaPakaiTahun: number;
  UsiaTeknisTahun: number;
  SisaUsiaManfaatTahun: number;
  PersentaseUsiaManfaat: number;
  HargaPerkiraanPengganti: number;
  Aic: number;
  AnggaranPemeliharaanTahunan: number;
  Mmel: number;
  BiayaPerbaikanKumulatif: number;
  LayakDiperbaiki: boolean;
  Alasan: string;
  Parameter: { LajuInflasi: number; FaktorMel: number; PersenPemeliharaanAic: number };
}

const persen = (nilai: number) => `${(nilai * 100).toFixed(1)}%`;

export function TabKelayakan({ aset }: { aset: Aset }) {
  const [data, setData] = useState<Kelayakan | null>(null);

  useEffect(() => {
    http.get<Kelayakan>(ruteAset.kelayakan(aset.Id)).then((res) => setData(res.data));
  }, [aset.Id]);

  if (!data) {
    return <div className="h-32 animate-pulse rounded-md bg-muted" />;
  }

  return (
    <div className="space-y-5">
      <DeretStatistik kolom={4}>
        <KartuStatistik
          menyatu
          label="AIC"
          nilai={formatUang(data.Aic)}
          keterangan="biaya investasi per tahun"
        />
        <KartuStatistik
          menyatu
          label="MMEL"
          nilai={formatUang(data.Mmel)}
          keterangan="batas biaya perbaikan"
        />
        <KartuStatistik
          menyatu
          label="Biaya Perbaikan Kumulatif"
          nilai={formatUang(data.BiayaPerbaikanKumulatif)}
          keterangan="sepanjang umur aset"
        />
        <KartuStatistik
          menyatu
          label="Sisa Usia Manfaat"
          nilai={`${data.SisaUsiaManfaatTahun} th`}
          keterangan={`${persen(data.PersentaseUsiaManfaat)} dari usia teknis`}
        />
      </DeretStatistik>

      <div className="rounded-md border border-border p-4">
        <div className="flex flex-wrap items-center gap-3">
          <Badge variant={data.LayakDiperbaiki ? 'sukses' : 'bahaya'}>
            {data.LayakDiperbaiki ? 'Layak diperbaiki' : 'Disarankan diganti'}
          </Badge>
          <span className="text-sm text-muted-foreground">{data.Alasan}</span>
        </div>
      </div>

      <div className="rounded-md border border-border p-5">
        <h3 className="mb-3 text-sm font-semibold text-foreground">Dasar perhitungan</h3>
        <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
          <Baris label="Harga perolehan (IIC)" nilai={formatUang(data.HargaPerolehan)} />
          <Baris label="Harga perkiraan pengganti" nilai={formatUang(data.HargaPerkiraanPengganti)} />
          <Baris label="Usia pakai" nilai={`${data.UsiaPakaiTahun} tahun`} />
          <Baris label="Usia teknis" nilai={`${data.UsiaTeknisTahun} tahun`} />
          <Baris label="Laju inflasi" nilai={persen(data.Parameter.LajuInflasi)} />
          <Baris label="Faktor MEL" nilai={data.Parameter.FaktorMel.toFixed(2)} />
          <Baris
            label="Anggaran pemeliharaan/tahun"
            nilai={`${formatUang(data.AnggaranPemeliharaanTahunan)} (${persen(
              data.Parameter.PersenPemeliharaanAic,
            )} × AIC)`}
          />
        </dl>
        <p className="mt-4 text-sm text-muted-foreground">
          AIC = harga perolehan × (1 + inflasi)<sup>usia pakai</sup> ÷ usia teknis. MMEL = faktor MEL ×
          persentase sisa usia manfaat × harga perkiraan pengganti. Ketiga parameternya diatur per organisasi;
          sesuaikan dengan acuan yang berlaku sebelum dipakai memutuskan penggantian.
        </p>
      </div>
    </div>
  );
}

function Baris({ label, nilai }: { label: string; nilai: string }) {
  return (
    <div className="flex justify-between gap-4 border-b border-border/60 py-1.5 last:border-0">
      <dt className="text-muted-foreground">{label}</dt>
      <dd className="text-right font-medium text-foreground">{nilai}</dd>
    </div>
  );
}
