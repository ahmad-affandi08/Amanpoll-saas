import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import type { Kontrak, LayananKontrak } from '@/features/Kontrak/types';
import { ruteKontrak } from '@/features/Kontrak/api';
import { formatUang } from '@/lib/uang';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import type { AsetRingkas } from '@/features/Kontrak/types';
import { DialogTambahAset } from '@/features/Kontrak/components/DialogTambahAset';
import { DialogTambahLayanan } from '@/features/Kontrak/components/DialogTambahLayanan';
import { DialogPemakaian } from '@/features/Kontrak/components/DialogPemakaian';
import { DialogBatalkan } from '@/features/Kontrak/components/DialogBatalkan';
import type { AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  kontrak: Kontrak;
  aset: AsetRingkas[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const VARIAN_STATUS = { Aktif: 'sukses', Berakhir: 'netral', Dibatalkan: 'bahaya' } as const;

export default function KontrakShow({ kontrak, aset, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const daftarAset = kontrak.Aset ?? [];
  const daftarLayanan = kontrak.Layanan ?? [];
  const aktif = kontrak.Status === 'Aktif';

  async function lepasAset(cakupanId: string, namaAset: string): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Lepas aset "${namaAset}" dari kontrak ini?`,
      deskripsi: 'Pekerjaan vendor pada aset tersebut tidak lagi tertaut ke kontrak ini.',
      ragam: 'bahaya',
    });
    if (lanjut) router.delete(ruteKontrak.asetDetail(kontrak.Id, cakupanId), { preserveScroll: true });
  }

  async function hapusLayanan(layanan: LayananKontrak): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Hapus layanan "${layanan.Nama}"?`,
      deskripsi: 'Layanan yang sudah memiliki pemakaian tidak dapat dihapus.',
      ragam: 'bahaya',
    });
    if (lanjut) router.delete(ruteKontrak.layananDetail(kontrak.Id, layanan.Id), { preserveScroll: true });
  }

  return (
    <KerangkaAplikasi>
      <Head title={`${kontrak.Nomor} — Kontrak`} />
      <div className="space-y-6">
        <Link
          href={ruteKontrak.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali ke Kontrak
        </Link>

        <KepalaHalaman
          judul={kontrak.Nama}
          labelBreadcrumb={kontrak.Nomor}
          lencana={<Badge variant={VARIAN_STATUS[kontrak.Status]}>{kontrak.Status}</Badge>}
          deskripsi={
            <span className="font-mono">
              {kontrak.Nomor} · {kontrak.Jenis} · {kontrak.NamaPenyedia ?? 'Tanpa penyedia'}
            </span>
          }
          aksi={aktif ? <DialogBatalkan kontrak={kontrak} wajib={wajib.batalkan} /> : undefined}
        />

        {aktif && kontrak.SisaHari >= 0 && kontrak.SisaHari <= kontrak.PeringatanHariSebelum && (
          <Alert variant="perhatian">
            <AlertTitle>Kontrak akan berakhir dalam {kontrak.SisaHari} hari</AlertTitle>
            <AlertDescription>
              Berakhir {kontrak.BerakhirPada}. Siapkan perpanjangan atau pengadaan pengganti sebelum tanggal
              tersebut.
            </AlertDescription>
          </Alert>
        )}
        {aktif && kontrak.SisaHari < 0 && (
          <Alert variant="bahaya">
            <AlertTitle>Kontrak sudah lewat masa berlaku</AlertTitle>
            <AlertDescription>
              Berakhir {kontrak.BerakhirPada}. Status akan ditutup otomatis pada pemeriksaan harian
              berikutnya.
            </AlertDescription>
          </Alert>
        )}

        <Card>
          <CardHeader>
            <CardTitle>Ringkasan</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 sm:grid-cols-4">
            <div>
              <p className="text-xs text-muted-foreground">Periode</p>
              <p className="font-medium">
                {kontrak.MulaiPada} s.d. {kontrak.BerakhirPada}
              </p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Nilai</p>
              <p className="font-mono font-medium">
                {kontrak.Nilai ? formatUang(kontrak.Nilai, kontrak.MataUang) : '—'}
              </p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Tingkat layanan</p>
              <p className="font-medium">{kontrak.NamaTingkatLayanan ?? 'Tanpa SLA'}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Peringatan</p>
              <p className="font-medium">H-{kontrak.PeringatanHariSebelum}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Aset Tercakup</CardTitle>
            {aktif && <DialogTambahAset kontrak={kontrak} aset={aset} wajib={wajib.aset} />}
          </CardHeader>
          <CardContent className="space-y-3">
            {daftarAset.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada aset tercakup."
                deskripsi="Lampirkan aset agar pekerjaan vendor dapat ditelusuri ke kontrak ini."
              />
            ) : (
              daftarAset.map((item) => (
                <div
                  key={item.Id}
                  className="flex flex-col gap-2 rounded-[9px] border border-border p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <p className="font-medium">{item.NamaAset}</p>
                    <p className="font-mono text-xs text-muted-foreground">
                      {item.KodeAset} · {item.MulaiPada ?? kontrak.MulaiPada} s.d.{' '}
                      {item.BerakhirPada ?? kontrak.BerakhirPada}
                    </p>
                  </div>
                  {aktif && (
                    <Button
                      size="icon"
                      variant="ghost"
                      aria-label={`Lepas ${item.NamaAset}`}
                      onClick={() => lepasAset(item.Id, item.NamaAset ?? 'aset ini')}
                    >
                      <Trash2 />
                    </Button>
                  )}
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Layanan</CardTitle>
            {aktif && <DialogTambahLayanan kontrak={kontrak} wajib={wajib.layanan} />}
          </CardHeader>
          <CardContent className="space-y-3">
            {daftarLayanan.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada layanan."
                deskripsi="Daftarkan layanan beserta kuotanya bila kontrak membatasi jumlah pekerjaan."
              />
            ) : (
              daftarLayanan.map((layanan) => (
                <div key={layanan.Id} className="space-y-2 rounded-[9px] border border-border p-3">
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0">
                      <p className="font-medium">{layanan.Nama}</p>
                      <p className="text-xs text-muted-foreground">
                        Terpakai {layanan.Terpakai}
                        {layanan.Kuota ? ` dari ${layanan.Kuota}` : ' (tanpa kuota)'} {layanan.Satuan ?? ''}
                      </p>
                    </div>
                    <div className="flex items-center gap-2">
                      {aktif && (
                        <DialogPemakaian kontrak={kontrak} layanan={layanan} wajib={wajib.pemakaian} />
                      )}
                      {aktif && (
                        <Button
                          size="icon"
                          variant="ghost"
                          aria-label={`Hapus layanan ${layanan.Nama}`}
                          onClick={() => hapusLayanan(layanan)}
                        >
                          <Trash2 />
                        </Button>
                      )}
                    </div>
                  </div>
                  {layanan.Deskripsi && <p className="text-sm text-muted-foreground">{layanan.Deskripsi}</p>}
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Dokumen dan Kolaborasi</CardTitle>
          </CardHeader>
          <CardContent>
            <PanelKolaborasi jenisEntitas="Kontrak" entitasId={kontrak.Id} />
          </CardContent>
        </Card>
      </div>
    </KerangkaAplikasi>
  );
}
