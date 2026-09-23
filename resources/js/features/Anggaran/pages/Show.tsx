import { useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import { Send, Trash2 } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Anggaran, PosAnggaran, TransaksiAnggaran } from '@/features/Anggaran/types';
import { formatUang } from '@/lib/uang';
import { ruteAnggaran } from '@/features/Anggaran/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DialogPos } from '@/features/Anggaran/components/DialogPos';
import { DialogTransaksi } from '@/features/Anggaran/components/DialogTransaksi';
import { DialogUbahAnggaran } from '@/features/Anggaran/components/DialogUbahAnggaran';
import type { AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  anggaran: Anggaran;
  transaksi: TransaksiAnggaran[];
  dapatMenyesuaikan: boolean;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const VARIAN_STATUS = {
  Draft: 'netral',
  MenungguPersetujuan: 'perhatian',
  Aktif: 'sukses',
  Ditolak: 'bahaya',
  Ditutup: 'netral',
} as const;

export default function AnggaranShow({ anggaran, transaksi, dapatMenyesuaikan, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const posisi = anggaran.PosAnggaran ?? [];
  const ringkasan = useMemo(
    () =>
      posisi.reduce(
        (hasil, pos) => ({
          terpakai: hasil.terpakai + Number(pos.Terpakai),
          ditahan: hasil.ditahan + Number(pos.Ditahan),
          sisa: hasil.sisa + Number(pos.Sisa),
        }),
        { terpakai: 0, ditahan: 0, sisa: 0 },
      ),
    [posisi],
  );
  const dapatUbah = anggaran.Status === 'Draft' || anggaran.Status === 'Ditolak';

  async function ajukan(): Promise<void> {
    if (
      await konfirmasi({
        judul: `Ajukan anggaran ${anggaran.Kode}?`,
        deskripsi: `Struktur pos tidak dapat diubah setelah diajukan.`,
        ragam: 'perhatian',
      })
    )
      router.post(ruteAnggaran.ajukan(anggaran.Id));
  }
  async function hapusAnggaran(): Promise<void> {
    if (
      await konfirmasi({
        judul: `Hapus draft anggaran ${anggaran.Kode}?`,
        deskripsi: `Tindakan ini hanya berhasil bila belum ada pos.`,
        ragam: 'bahaya',
      })
    )
      router.delete(ruteAnggaran.detail(anggaran.Id));
  }
  async function hapusPos(pos: PosAnggaran): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Hapus pos "${pos.Kode} — ${pos.Nama}"?`,
      deskripsi: 'Pos yang masih memiliki pos anak atau transaksi anggaran tidak dapat dihapus.',
      ragam: 'bahaya',
    });
    if (lanjut) router.delete(ruteAnggaran.posDetail(pos.Id), { preserveScroll: true });
  }

  return (
    <KerangkaAplikasi>
      <Head title={`${anggaran.Kode} — Anggaran`} />
      <div className="space-y-6">
        <KepalaHalaman
          judul={anggaran.Nama}
          labelBreadcrumb={anggaran.Kode}
          lencana={
            <Badge variant={VARIAN_STATUS[anggaran.Status]}>
              {anggaran.Status === 'MenungguPersetujuan' ? 'Menunggu Persetujuan' : anggaran.Status}
            </Badge>
          }
          deskripsi={
            <>
              <span className="font-mono">
                {anggaran.Kode} · {anggaran.Tahun}
              </span>
              {' · '}
              {anggaran.NamaUnitOrganisasi ?? 'Scope seluruh organisasi'}
            </>
          }
          aksi={
            <>
              {dapatUbah && <DialogUbahAnggaran anggaran={anggaran} wajib={wajib.anggaran} />}
              {anggaran.Status === 'Draft' && (
                <Button size="sm" onClick={ajukan}>
                  <Send /> Ajukan
                </Button>
              )}
              {dapatUbah && (
                <Button size="sm" variant="outline" onClick={hapusAnggaran}>
                  <Trash2 /> Hapus
                </Button>
              )}
            </>
          }
        />

        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          {[
            ['Total Anggaran', formatUang(anggaran.Jumlah, anggaran.MataUang)],
            ['Realisasi', formatUang(ringkasan.terpakai, anggaran.MataUang)],
            ['Komitmen', formatUang(ringkasan.ditahan, anggaran.MataUang)],
            ['Sisa Pos', formatUang(ringkasan.sisa, anggaran.MataUang)],
          ].map(([label, value]) => (
            <Card key={label}>
              <CardHeader className="pb-2">
                <CardTitle className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                  {label}
                </CardTitle>
              </CardHeader>
              <CardContent className="font-mono text-lg font-semibold">{value}</CardContent>
            </Card>
          ))}
        </div>

        <section className="overflow-hidden rounded-[9px] border border-border bg-card">
          <div className="flex items-center justify-between border-b border-border p-4">
            <div>
              <h2 className="font-semibold">Pos Anggaran</h2>
              <p className="text-xs text-muted-foreground">
                Saldo proyeksi selalu direkonsiliasi dari transaksi ledger.
              </p>
            </div>
            {dapatUbah && <DialogPos anggaran={anggaran} semuaPos={posisi} wajib={wajib.pos} />}
          </div>
          {posisi.length === 0 ? (
            <div className="p-8 text-center text-sm text-muted-foreground">Belum ada pos anggaran.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-[860px] w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Pos</th>
                    <th className="px-4 py-3 text-right">Nilai</th>
                    <th className="px-4 py-3 text-right">Realisasi</th>
                    <th className="px-4 py-3 text-right">Komitmen</th>
                    <th className="px-4 py-3 text-right">Sisa</th>
                    <th className="px-4 py-3 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {posisi.map((pos) => (
                    <tr key={pos.Id}>
                      <td className="px-4 py-3">
                        <p className="font-medium">{pos.Nama}</p>
                        <p className="font-mono text-xs text-muted-foreground">
                          {pos.Kode}
                          {pos.NamaInduk ? ` · di bawah ${pos.NamaInduk}` : ''}
                        </p>
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {formatUang(pos.Jumlah, anggaran.MataUang)}
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {formatUang(pos.Terpakai, anggaran.MataUang)}
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {formatUang(pos.Ditahan, anggaran.MataUang)}
                      </td>
                      <td className="px-4 py-3 text-right font-mono font-semibold">
                        {formatUang(pos.Sisa, anggaran.MataUang)}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex justify-end gap-1">
                          {anggaran.Status === 'Aktif' && (
                            <DialogTransaksi
                              pos={pos}
                              mataUang={anggaran.MataUang}
                              dapatMenyesuaikan={dapatMenyesuaikan}
                              wajib={wajib.transaksi}
                            />
                          )}
                          {dapatUbah && (
                            <DialogPos anggaran={anggaran} pos={pos} semuaPos={posisi} wajib={wajib.pos} />
                          )}
                          {dapatUbah && (
                            <Button
                              variant="ghost"
                              size="sm"
                              aria-label={`Hapus ${pos.Nama}`}
                              onClick={() => hapusPos(pos)}
                            >
                              <Trash2 />
                            </Button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>

        <section className="overflow-hidden rounded-[9px] border border-border bg-card">
          <div className="border-b border-border p-4">
            <h2 className="font-semibold">Ledger Transaksi</h2>
            <p className="text-xs text-muted-foreground">
              100 transaksi terbaru; baris tidak dapat diedit atau dihapus.
            </p>
          </div>
          {transaksi.length === 0 ? (
            <div className="p-8 text-center text-sm text-muted-foreground">Belum ada transaksi.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-[760px] w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Tanggal</th>
                    <th className="px-4 py-3">Pos</th>
                    <th className="px-4 py-3">Jenis</th>
                    <th className="px-4 py-3 text-right">Jumlah</th>
                    <th className="px-4 py-3">Keterangan</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {transaksi.map((item) => (
                    <tr key={item.Id}>
                      <td className="px-4 py-3">
                        {new Date(`${item.Tanggal}T00:00:00`).toLocaleDateString('id-ID')}
                      </td>
                      <td className="px-4 py-3">{item.NamaPosAnggaran}</td>
                      <td className="px-4 py-3">
                        <Badge
                          variant={
                            item.Jenis === 'Realisasi'
                              ? 'sukses'
                              : item.Jenis === 'Komitmen'
                                ? 'perhatian'
                                : 'netral'
                          }
                        >
                          {item.Jenis === 'PelepasanKomitmen' ? 'Pelepasan' : item.Jenis}
                        </Badge>
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {formatUang(item.Jumlah, anggaran.MataUang)}
                      </td>
                      <td className="max-w-xs px-4 py-3 text-muted-foreground">{item.Keterangan ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>
      </div>
    </KerangkaAplikasi>
  );
}
