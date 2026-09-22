import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { formatUang } from '@/lib/uang';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import type {
  PemakaianSukuCadangBaris,
  ReservasiSukuCadangBaris,
  StokSukuCadangRingkas,
} from '@/features/Persediaan/types';

function tanggal(nilai: string | null): string {
  if (!nilai) {
    return '—';
  }

  return new Date(nilai).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function Kotak({ children, judul, kanan }: { children: React.ReactNode; judul: string; kanan?: string }) {
  return (
    <div className="rounded-[9px] border border-border bg-card p-4">
      <div className="mb-3 flex flex-wrap items-baseline justify-between gap-2">
        <h2 className="text-sm font-semibold text-foreground">{judul}</h2>
        {kanan && <span className="text-xs text-muted-foreground">{kanan}</span>}
      </div>
      {children}
    </div>
  );
}

export function PanelStok({
  stok,
  satuan,
  stokMinimum,
}: {
  stok: { baris: StokSukuCadangRingkas[]; TotalTersedia: number; TotalDitahan: number; TotalBersih: number };
  satuan: string;
  stokMinimum: number;
}) {
  const dibawahMinimum = stok.TotalBersih <= stokMinimum;

  return (
    <Kotak judul="Stok per Gudang" kanan={`${stok.baris.length} penempatan`}>
      <div className="mb-4 grid gap-3 sm:grid-cols-3">
        <div>
          <p className="text-xs text-muted-foreground">Fisik</p>
          <p className="text-lg font-semibold tabular-nums text-foreground">
            {stok.TotalTersedia} <span className="text-xs font-normal">{satuan}</span>
          </p>
        </div>
        <div>
          <p className="text-xs text-muted-foreground">Ditahan reservasi</p>
          <p className="text-lg font-semibold tabular-nums text-foreground">
            {stok.TotalDitahan} <span className="text-xs font-normal">{satuan}</span>
          </p>
        </div>
        <div>
          <p className="text-xs text-muted-foreground">Tersedia bersih</p>
          <p
            className={
              dibawahMinimum
                ? 'text-lg font-semibold tabular-nums text-destructive'
                : 'text-lg font-semibold tabular-nums text-foreground'
            }
          >
            {stok.TotalBersih} <span className="text-xs font-normal">{satuan}</span>
            {dibawahMinimum && (
              <Badge variant="bahaya" className="ml-2 align-middle">
                Di bawah minimum
              </Badge>
            )}
          </p>
        </div>
      </div>

      {stok.baris.length === 0 ? (
        <KeadaanKosong
          judul="Belum ada stok."
          deskripsi="Saldo muncul setelah mutasi stok penerimaan pertama diposting."
        />
      ) : (
        <ul className="divide-y divide-border border-t border-border">
          {stok.baris.map((satu) => (
            <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
              <div className="min-w-0">
                <div className="text-sm font-medium text-foreground">{satu.Gudang ?? 'Tanpa gudang'}</div>
                <div className="text-xs text-muted-foreground">
                  {satu.LokasiGudang ?? 'Tanpa lokasi rak'}
                  {satu.NomorBatch && ` · batch ${satu.NomorBatch}`}
                </div>
              </div>
              <div className="flex shrink-0 items-center gap-4 text-sm tabular-nums">
                <span className="text-muted-foreground">
                  fisik <span className="text-foreground">{satu.JumlahTersedia}</span>
                </span>
                <span className="text-muted-foreground">
                  ditahan <span className="text-foreground">{satu.JumlahDitahan}</span>
                </span>
                <span className="w-20 text-right font-semibold text-foreground">{satu.JumlahBersih}</span>
              </div>
            </li>
          ))}
        </ul>
      )}
    </Kotak>
  );
}

export function PanelReservasi({
  reservasi,
  satuan,
}: {
  reservasi: ReservasiSukuCadangBaris[];
  satuan: string;
}) {
  return (
    <Kotak judul="Reservasi Aktif" kanan={`${reservasi.length} reservasi`}>
      {reservasi.length === 0 ? (
        <p className="text-sm text-muted-foreground">Tidak ada stok yang sedang ditahan.</p>
      ) : (
        <ul className="divide-y divide-border">
          {reservasi.map((satu) => (
            <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
              <div className="min-w-0">
                {satu.PerintahKerjaId ? (
                  <Link
                    href={rutePerintahKerja.detail(satu.PerintahKerjaId)}
                    className="text-sm font-medium text-foreground hover:underline"
                  >
                    {satu.NomorPerintahKerja ?? 'Perintah kerja'}
                  </Link>
                ) : (
                  <span className="text-sm text-foreground">Tanpa perintah kerja</span>
                )}
                <div className="text-xs text-muted-foreground">
                  {satu.Gudang ?? 'Tanpa gudang'}
                  {satu.KadaluarsaPada && ` · kedaluwarsa ${tanggal(satu.KadaluarsaPada)}`}
                </div>
              </div>
              <span className="shrink-0 text-sm tabular-nums text-foreground">
                {satu.Jumlah} {satuan}
              </span>
            </li>
          ))}
        </ul>
      )}
    </Kotak>
  );
}

export function PanelPemakaian({
  pemakaian,
  satuan,
}: {
  pemakaian: { total: number; data: PemakaianSukuCadangBaris[] };
  satuan: string;
}) {
  return (
    <Kotak
      judul="Riwayat Pemakaian"
      kanan={
        pemakaian.data.length < pemakaian.total
          ? `Menampilkan ${pemakaian.data.length} terbaru dari ${pemakaian.total}`
          : `${pemakaian.total} pemakaian`
      }
    >
      {pemakaian.data.length === 0 ? (
        <p className="text-sm text-muted-foreground">Suku cadang ini belum pernah dipakai.</p>
      ) : (
        <ul className="divide-y divide-border">
          {pemakaian.data.map((satu) => (
            <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
              <div className="min-w-0">
                {satu.PerintahKerjaId ? (
                  <Link
                    href={rutePerintahKerja.detail(satu.PerintahKerjaId)}
                    className="text-sm font-medium text-foreground hover:underline"
                  >
                    {satu.JudulPerintahKerja ?? satu.NomorPerintahKerja ?? 'Perintah kerja'}
                  </Link>
                ) : (
                  <span className="text-sm text-foreground">Tanpa perintah kerja</span>
                )}
                <div className="text-xs text-muted-foreground">
                  {satu.NomorPerintahKerja && `${satu.NomorPerintahKerja} · `}
                  {satu.Gudang ?? 'Tanpa gudang'}
                  {satu.DipakaiOleh && ` · ${satu.DipakaiOleh}`}
                </div>
              </div>
              <div className="flex shrink-0 items-center gap-4 text-sm">
                {satu.HargaSatuan !== null && (
                  <span className="text-xs text-muted-foreground">{formatUang(satu.HargaSatuan)}</span>
                )}
                <span className="tabular-nums text-foreground">
                  {satu.Jumlah} {satuan}
                </span>
                <span className="w-24 text-right text-xs text-muted-foreground">
                  {tanggal(satu.DipakaiPada)}
                </span>
              </div>
            </li>
          ))}
        </ul>
      )}
    </Kotak>
  );
}
