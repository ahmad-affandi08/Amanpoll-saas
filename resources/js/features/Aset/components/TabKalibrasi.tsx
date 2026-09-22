import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { http } from '@/lib/http';
import type { Aset, RiwayatKalibrasiAset } from '@/features/Aset/types';
import { ruteAset } from '@/features/Aset/api';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { hasilKalibrasiBadge } from '@/features/Kalibrasi/status';
import { BarisKosong, KartuAngka, KepalaBagian, tanggal } from '@/components/shared/riwayat';

export function TabKalibrasi({ aset }: { aset: Aset }) {
  const [data, setData] = useState<RiwayatKalibrasiAset | null>(null);
  const [memuat, setMemuat] = useState(true);

  useEffect(() => {
    setMemuat(true);
    http
      .get(ruteAset.riwayatKalibrasi(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  }, [aset.Id]);

  if (memuat) {
    return <p className="py-4 text-sm text-muted-foreground">Memuat riwayat kalibrasi...</p>;
  }

  if (!data) {
    return <p className="py-4 text-sm text-muted-foreground">Riwayat kalibrasi tidak dapat dimuat.</p>;
  }

  const { ringkasan, rencana, pelaksanaan } = data;
  const hasil = hasilKalibrasiBadge(ringkasan.HasilTerakhir);

  return (
    <div className="space-y-8">
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <KartuAngka label="Pelaksanaan" nilai={ringkasan.JumlahPelaksanaan} />
        <KartuAngka label="Terakhir Dikalibrasi" nilai={tanggal(ringkasan.TerakhirPada)} />
        <KartuAngka label="Hasil Terakhir" nilai={<span className="text-base">{hasil.label}</span>} />
        <KartuAngka
          label="Jatuh Tempo Berikutnya"
          nilai={tanggal(ringkasan.JatuhTempoBerikutnya)}
          catatan={
            ringkasan.BerlakuSampai ? `Sertifikat berlaku s/d ${tanggal(ringkasan.BerlakuSampai)}` : undefined
          }
        />
      </div>

      {ringkasan.JumlahPelaksanaan === 0 && rencana.length === 0 && (
        <p className="rounded-[9px] border border-safety-600/25 bg-safety-600/5 p-4 text-sm text-grafit-700">
          Aset ini belum punya rencana kalibrasi. Bila kategorinya menuntut kalibrasi, aset ini terhitung
          tidak patuh di dasbor kepatuhan.
        </p>
      )}

      <section className="space-y-1">
        <KepalaBagian judul="Rencana Kalibrasi" ditampilkan={rencana.length} total={rencana.length} />
        {rencana.length === 0 ? (
          <BarisKosong teks="Belum ada rencana kalibrasi." />
        ) : (
          <ul className="divide-y divide-border">
            {rencana.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <Link
                    href={ruteKalibrasi.rencanaDetail(satu.Id)}
                    className="text-sm font-medium text-foreground hover:underline"
                  >
                    {satu.JenisKalibrasi ?? 'Tanpa jenis'}
                  </Link>
                  <div className="text-xs text-muted-foreground">
                    Tiap {satu.IntervalHari} hari
                    {satu.Penyedia && ` · ${satu.Penyedia}`}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  <Badge variant={satu.Aktif ? 'secondary' : 'outline'}>
                    {satu.Aktif ? 'Aktif' : 'Nonaktif'}
                  </Badge>
                  <span className="w-24 text-right text-xs text-muted-foreground">
                    {tanggal(satu.TanggalBerikutnya)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian
          judul="Pelaksanaan Kalibrasi"
          ditampilkan={pelaksanaan.data.length}
          total={pelaksanaan.total}
        />
        {pelaksanaan.data.length === 0 ? (
          <BarisKosong teks="Aset ini belum pernah dikalibrasi." />
        ) : (
          <ul className="divide-y divide-border">
            {pelaksanaan.data.map((satu) => {
              const ragam = hasilKalibrasiBadge(satu.Hasil);

              return (
                <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                  <div className="min-w-0">
                    <Link
                      href={ruteKalibrasi.pelaksanaanDetail(satu.Id)}
                      className="text-sm font-medium text-foreground hover:underline"
                    >
                      {satu.Nomor}
                    </Link>
                    <div className="text-xs text-muted-foreground">
                      {satu.JenisKalibrasi ?? 'Tanpa jenis'}
                      {satu.NomorSertifikat && ` · sertifikat ${satu.NomorSertifikat}`}
                      {satu.Penyedia && ` · ${satu.Penyedia}`}
                    </div>
                  </div>
                  <div className="flex shrink-0 items-center gap-2">
                    <span
                      className={cn('rounded-[4px] border px-2 py-0.5 text-xs font-medium', ragam.className)}
                    >
                      {ragam.label}
                    </span>
                    <span className="w-24 text-right text-xs text-muted-foreground">
                      {tanggal(satu.TanggalKalibrasi)}
                    </span>
                  </div>
                </li>
              );
            })}
          </ul>
        )}
      </section>
    </div>
  );
}
