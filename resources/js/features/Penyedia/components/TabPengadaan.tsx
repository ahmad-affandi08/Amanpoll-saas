import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { http } from '@/lib/http';
import { formatUang } from '@/lib/uang';
import { BarisKosong, KartuAngka, KepalaBagian, tanggal } from '@/components/shared/riwayat';
import type { Penyedia, RiwayatPengadaanPenyedia } from '@/features/Penyedia/types';
import { rutePenyedia } from '@/features/Penyedia/api';
import { ruteTagihanPenyedia } from '@/features/TagihanPenyedia/api';
import { rutePesananPembelian } from '@/features/PesananPembelian/api';

export function TabPengadaan({ penyedia }: { penyedia: Penyedia }) {
  const [data, setData] = useState<RiwayatPengadaanPenyedia | null>(null);
  const [memuat, setMemuat] = useState(true);

  useEffect(() => {
    setMemuat(true);
    http
      .get(rutePenyedia.riwayatPengadaan(penyedia.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  }, [penyedia.Id]);

  if (memuat) {
    return <p className="py-4 text-sm text-muted-foreground">Memuat riwayat pengadaan...</p>;
  }

  if (!data) {
    return <p className="py-4 text-sm text-muted-foreground">Riwayat pengadaan tidak dapat dimuat.</p>;
  }

  const { ringkasan, penawaran, pesanan, tagihan } = data;

  return (
    <div className="space-y-8">
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <KartuAngka
          label="Penawaran"
          nilai={ringkasan.JumlahPenawaran}
          catatan={`${ringkasan.JumlahPenawaranTerpilih} terpilih`}
        />
        <KartuAngka label="Pesanan Pembelian" nilai={ringkasan.JumlahPesanan} />
        <KartuAngka label="Nilai Pesanan" nilai={formatUang(ringkasan.NilaiPesanan)} />
        <KartuAngka
          label="Sisa Tagihan"
          nilai={formatUang(ringkasan.SisaTagihan)}
          catatan={`dari ${formatUang(ringkasan.NilaiTagihan)} ditagihkan`}
        />
      </div>

      <section className="space-y-1">
        <KepalaBagian judul="Penawaran" ditampilkan={penawaran.data.length} total={penawaran.total} />
        {penawaran.data.length === 0 ? (
          <BarisKosong teks="Penyedia ini belum pernah mengajukan penawaran." />
        ) : (
          <ul className="divide-y divide-border">
            {penawaran.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <div className="font-mono text-sm text-foreground">{satu.NomorPenawaran}</div>
                  <div className="text-xs text-muted-foreground">
                    {satu.PermintaanPenawaran
                      ? `atas ${satu.PermintaanPenawaran}`
                      : 'tanpa permintaan penawaran'}
                    {satu.BerlakuSampai && ` · berlaku sampai ${tanggal(satu.BerlakuSampai)}`}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <Badge variant="secondary">{satu.Status}</Badge>
                  <span className="text-sm tabular-nums text-foreground">
                    {formatUang(satu.Total, satu.MataUang)}
                  </span>
                  <span className="w-24 text-right text-xs text-muted-foreground">
                    {tanggal(satu.TanggalPenawaran)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian judul="Pesanan Pembelian" ditampilkan={pesanan.data.length} total={pesanan.total} />
        {pesanan.data.length === 0 ? (
          <BarisKosong teks="Belum ada pesanan pembelian ke penyedia ini." />
        ) : (
          <ul className="divide-y divide-border">
            {pesanan.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <Link
                    href={rutePesananPembelian.detail(satu.Id)}
                    className="font-mono text-sm text-foreground hover:underline"
                  >
                    {satu.Nomor}
                  </Link>
                  {satu.TanggalKirimRencana && (
                    <div className="text-xs text-muted-foreground">
                      rencana kirim {tanggal(satu.TanggalKirimRencana)}
                    </div>
                  )}
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <Badge variant="secondary">{satu.Status}</Badge>
                  <span className="text-sm tabular-nums text-foreground">
                    {formatUang(satu.Total, satu.MataUang)}
                  </span>
                  <span className="w-24 text-right text-xs text-muted-foreground">
                    {tanggal(satu.TanggalPesanan)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-1">
        <KepalaBagian judul="Tagihan" ditampilkan={tagihan.data.length} total={tagihan.total} />
        {tagihan.data.length === 0 ? (
          <BarisKosong teks="Belum ada tagihan dari penyedia ini." />
        ) : (
          <ul className="divide-y divide-border">
            {tagihan.data.map((satu) => (
              <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div className="min-w-0">
                  <Link
                    href={ruteTagihanPenyedia.detail(satu.Id)}
                    className="font-mono text-sm text-foreground hover:underline"
                  >
                    {satu.NomorTagihan}
                  </Link>
                  <div className="text-xs text-muted-foreground">
                    {satu.NomorPesanan ? `atas ${satu.NomorPesanan}` : 'tanpa pesanan pembelian'}
                    {satu.JatuhTempo && ` · jatuh tempo ${tanggal(satu.JatuhTempo)}`}
                  </div>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <Badge variant={satu.Sisa > 0 ? 'perhatian' : 'sukses'}>{satu.Status}</Badge>
                  <span className="text-sm tabular-nums text-foreground">{formatUang(satu.Total)}</span>
                  <span className="w-28 text-right text-xs tabular-nums text-muted-foreground">
                    {satu.Sisa > 0 ? `sisa ${formatUang(satu.Sisa)}` : 'lunas'}
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
