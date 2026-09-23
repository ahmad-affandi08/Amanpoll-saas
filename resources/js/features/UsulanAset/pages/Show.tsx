import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Send, ShieldCheck, Trash2 } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { UsulanAset } from '@/features/UsulanAset/types';
import { formatUang } from '@/lib/uang';
import { ruteUsulanAset } from '@/features/UsulanAset/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DialogUbahUsulan } from '@/features/UsulanAset/components/DialogUbahUsulan';
import { DialogPenilaian } from '@/features/UsulanAset/components/DialogPenilaian';
import type { Referensi } from '@/features/UsulanAset/types';
import type { AturanWajib } from '@/lib/aturan-wajib';

interface Keputusan {
  Keputusan: string;
  Catatan: string | null;
  NamaPenyetuju: string | null;
  DiputuskanPada: string | null;
}

interface Persetujuan {
  Id: string;
  Status: string;
  DimintaPada: string | null;
  SelesaiPada: string | null;
  Keputusan: Keputusan[];
}

interface CatatanAudit {
  Id: string;
  Aksi: string;
  PenggunaId: string | null;
  DibuatPada: string;
}

interface Props {
  usulan: UsulanAset;
  persetujuan: Persetujuan[];
  audit: CatatanAudit[];
  unitOrganisasi: Referensi[];
  kategoriAset: Referensi[];
  modelAset: Referensi[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const VARIAN_STATUS = {
  Draft: 'netral',
  Diajukan: 'info',
  MenungguPersetujuan: 'perhatian',
  Disetujui: 'sukses',
  Ditolak: 'bahaya',
} as const;

export default function UsulanAsetShow({
  usulan,
  persetujuan,
  audit,
  unitOrganisasi,
  kategoriAset,
  modelAset,
  wajib,
}: Props) {
  const konfirmasi = useKonfirmasi();
  const dapatUbah = usulan.Status === 'Draft' || usulan.Status === 'Ditolak';
  const dapatNilai = usulan.Status === 'Diajukan';
  async function submitUsulan(): Promise<void> {
    if (
      await konfirmasi({
        judul: `Submit ${usulan.Nomor} untuk penilaian?`,
        deskripsi: 'Usulan terkunci dari perubahan selama proses penilaian.',
        ragam: 'perhatian',
      })
    )
      router.post(ruteUsulanAset.submit(usulan.Id));
  }
  async function ajukanPersetujuan(): Promise<void> {
    if (
      await konfirmasi({
        judul: `Ajukan ${usulan.Nomor} ke alur persetujuan aktif?`,
        deskripsi: 'Penyetuju pada alur aktif akan menerima permintaan persetujuan.',
        ragam: 'perhatian',
      })
    )
      router.post(ruteUsulanAset.ajukanPersetujuan(usulan.Id));
  }
  async function hapus(): Promise<void> {
    if (
      await konfirmasi({
        judul: `Hapus usulan ${usulan.Nomor}?`,
        deskripsi: 'Seluruh penilaian pada usulan ini ikut terhapus.',
        ragam: 'bahaya',
      })
    )
      router.delete(ruteUsulanAset.detail(usulan.Id));
  }
  return (
    <KerangkaAplikasi>
      <Head title={`${usulan.Nomor} — Usulan Aset`} />
      <div className="space-y-6">
        <Link
          href={ruteUsulanAset.index}
          className="inline-flex min-h-11 items-center gap-2 rounded-[5px] text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring md:min-h-0"
        >
          <ArrowLeft className="size-4" /> Kembali ke Usulan Aset
        </Link>
        <KepalaHalaman
          judul={usulan.NamaKebutuhan}
          labelBreadcrumb={usulan.Nomor}
          lencana={
            <Badge variant={VARIAN_STATUS[usulan.Status]}>
              {usulan.Status === 'MenungguPersetujuan' ? 'Menunggu Persetujuan' : usulan.Status}
            </Badge>
          }
          deskripsi={
            <>
              <span className="font-mono">{usulan.Nomor}</span> · {usulan.NamaUnitOrganisasi} · diajukan oleh{' '}
              {usulan.NamaPengaju}
            </>
          }
          aksi={
            <>
              {dapatUbah && (
                <DialogUbahUsulan
                  usulan={usulan}
                  unitOrganisasi={unitOrganisasi}
                  kategoriAset={kategoriAset}
                  modelAset={modelAset}
                  wajib={wajib.usulan}
                />
              )}
              {usulan.Status === 'Draft' && (
                <Button size="sm" onClick={submitUsulan}>
                  <Send /> Submit
                </Button>
              )}
              {dapatNilai && <DialogPenilaian usulan={usulan} wajib={wajib.penilaian} />}
              {dapatNilai && (usulan.Penilaian?.length ?? 0) > 0 && (
                <Button size="sm" onClick={ajukanPersetujuan}>
                  <ShieldCheck /> Ajukan Persetujuan
                </Button>
              )}
              {dapatUbah && (
                <Button size="sm" variant="outline" onClick={hapus}>
                  <Trash2 /> Hapus
                </Button>
              )}
            </>
          }
        />
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          {[
            ['Jumlah', Number(usulan.Jumlah).toLocaleString('id-ID')],
            [
              'Estimasi / Unit',
              usulan.EstimasiHargaSatuan ? formatUang(usulan.EstimasiHargaSatuan) : 'Belum diisi',
            ],
            [
              'Total Estimasi',
              usulan.EstimasiHargaSatuan
                ? formatUang(Number(usulan.Jumlah) * Number(usulan.EstimasiHargaSatuan))
                : 'Belum diisi',
            ],
            ['Prioritas', usulan.Prioritas],
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
        <Card>
          <CardHeader>
            <CardTitle>Rincian Kebutuhan</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
            <div>
              <p className="text-muted-foreground">Kategori / Model</p>
              <p className="font-medium">
                {usulan.NamaKategoriAset ?? 'Belum ditentukan'} / {usulan.NamaModelAset ?? 'Belum ditentukan'}
              </p>
            </div>
            <div>
              <p className="text-muted-foreground">Jenis / Tahun Kebutuhan</p>
              <p className="font-medium">
                {usulan.JenisKebutuhan ?? 'Belum ditentukan'} / {usulan.TahunKebutuhan ?? '—'}
              </p>
            </div>
            <div className="sm:col-span-2">
              <p className="text-muted-foreground">Alasan</p>
              <p className="mt-1 whitespace-pre-wrap">{usulan.Alasan}</p>
            </div>
          </CardContent>
        </Card>
        <section className="overflow-hidden rounded-[9px] border border-border bg-card">
          <div className="border-b border-border p-4">
            <h2 className="font-semibold">Penilaian</h2>
            <p className="text-xs text-muted-foreground">Riwayat kriteria dan skor berbobot.</p>
          </div>
          {(usulan.Penilaian?.length ?? 0) === 0 ? (
            <p className="p-8 text-center text-sm text-muted-foreground">Belum ada penilaian.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-[650px] w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Kriteria</th>
                    <th className="px-4 py-3 text-right">Bobot</th>
                    <th className="px-4 py-3 text-right">Nilai</th>
                    <th className="px-4 py-3 text-right">Skor</th>
                    <th className="px-4 py-3">Penilai</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {usulan.Penilaian?.map((item) => (
                    <tr key={item.Id}>
                      <td className="px-4 py-3 font-medium">{item.Kriteria}</td>
                      <td className="px-4 py-3 text-right font-mono">{item.Bobot}</td>
                      <td className="px-4 py-3 text-right font-mono">{item.Nilai}</td>
                      <td className="px-4 py-3 text-right font-mono font-semibold">{item.Skor}</td>
                      <td className="px-4 py-3">{item.NamaPenilai ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>
        <div className="grid gap-4 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Riwayat Persetujuan</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {persetujuan.length === 0 ? (
                <p className="text-sm text-muted-foreground">Belum diajukan ke alur persetujuan.</p>
              ) : (
                persetujuan.map((item) => (
                  <div key={item.Id} className="rounded-md border border-border p-3 text-sm">
                    <div className="flex justify-between gap-3">
                      <Badge
                        variant={
                          item.Status === 'Disetujui'
                            ? 'sukses'
                            : item.Status === 'Ditolak'
                              ? 'bahaya'
                              : 'perhatian'
                        }
                      >
                        {item.Status}
                      </Badge>
                      <span className="text-xs text-muted-foreground">
                        {item.DimintaPada ? new Date(item.DimintaPada).toLocaleString('id-ID') : '—'}
                      </span>
                    </div>
                    {item.Keputusan.map((keputusan, index) => (
                      <p key={index} className="mt-2 text-muted-foreground">
                        {keputusan.NamaPenyetuju ?? 'Penyetuju'}: {keputusan.Keputusan}
                        {keputusan.Catatan ? ` — ${keputusan.Catatan}` : ''}
                      </p>
                    ))}
                  </div>
                ))
              )}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Jejak Audit</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              {audit.length === 0 ? (
                <p className="text-sm text-muted-foreground">Belum ada catatan audit.</p>
              ) : (
                audit.map((item) => (
                  <div
                    key={item.Id}
                    className="flex justify-between gap-3 border-b border-border py-2 text-sm last:border-0"
                  >
                    <span>{item.Aksi}</span>
                    <span className="text-xs text-muted-foreground">
                      {new Date(item.DibuatPada).toLocaleString('id-ID')}
                    </span>
                  </div>
                ))
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
