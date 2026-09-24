import { FormEvent, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Keluhan, PrioritasKeluhan, StatusKeluhan } from '@/features/Keluhan/types';
import { VARIAN_PRIORITAS_KELUHAN, VARIAN_STATUS_KELUHAN } from '@/features/Keluhan/status';
import { ruteKeluhan } from '@/features/Keluhan/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';
import { TANPA_PILIHAN, opsiUnitPengelola } from '@/lib/pilihan';

interface Props {
  keluhan: Keluhan;
  dapatMengelola: boolean;
  /** Pilihan awal Ubah Prioritas: usulan pelapor selama prioritas belum ditetapkan. */
  prioritasAwal: PrioritasKeluhan;
  transisiDiizinkan: StatusKeluhan[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
  /** Organisasi memakai unit pengelola (PRD 8.21). */
  pakaiUnitPengelola: boolean;
  /** Unit tujuan pengalihan; kosong bila pengguna tidak boleh mengalihkan. */
  pilihanUnitPengelola: UnitPengelolaRingkas[];
  /** Alasan keluhan ini tidak dapat dialihkan (mis. sudah ditutup), atau null. */
  hambatanPengalihan: string | null;
}
function formatTanggal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '—';
}

function DialogStatus({
  keluhan,
  transisi,
  wajib,
}: {
  keluhan: Keluhan;
  transisi: StatusKeluhan[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Status: transisi[0] ?? keluhan.Status, Catatan: '', Versi: keluhan.Versi });
  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.put(ruteKeluhan.status(keluhan.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button disabled={transisi.length === 0}>Ubah Status</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Ubah Status Keluhan</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Status">Status berikutnya</Label>
              <Combobox
                nilai={form.data.Status}
                onPilih={(v) => form.setData('Status', v as StatusKeluhan)}
                opsi={transisi.map((s) => ({ nilai: s, label: s }))}
              />
            </div>
            <div className="space-y-1.5">
              <Label nama="Status">
                Catatan {(form.data.Status === 'Ditolak' || form.data.Status === 'Dibatalkan') && '(wajib)'}
              </Label>
              <Textarea
                rows={4}
                value={form.data.Catatan}
                onChange={(e) => form.setData('Catatan', e.target.value)}
              />
              {form.errors.Catatan && <p className="text-sm text-destructive">{form.errors.Catatan}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan Status
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function DialogPrioritas({
  keluhan,
  prioritasAwal,
  wajib,
}: {
  keluhan: Keluhan;
  prioritasAwal: PrioritasKeluhan;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Prioritas: prioritasAwal, Alasan: '', Versi: keluhan.Versi });
  const dariUsulan = keluhan.PrioritasUsulan !== null && prioritasAwal !== keluhan.Prioritas;
  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.put(ruteKeluhan.prioritas(keluhan.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline">Ubah Prioritas</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Ubah Prioritas</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Prioritas">Prioritas</Label>
              <Combobox
                nilai={form.data.Prioritas}
                onPilih={(v) => form.setData('Prioritas', v as PrioritasKeluhan)}
                opsi={(['Rendah', 'Normal', 'Tinggi', 'Kritis'] as PrioritasKeluhan[]).map((p) => ({
                  nilai: p,
                  label: p,
                }))}
              />
              {dariUsulan && (
                <p className="text-sm text-muted-foreground">
                  Terisi dari usulan pelapor ({keluhan.LabelUsulanUrgensi}). Prioritas saat ini{' '}
                  {keluhan.Prioritas}.
                </p>
              )}
            </div>
            <div className="space-y-1.5">
              <Label nama="Alasan">Alasan perubahan</Label>
              <Textarea value={form.data.Alasan} onChange={(e) => form.setData('Alasan', e.target.value)} />
              {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Terapkan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

/**
 * Alihkan keluhan ke antrean unit pengelola lain (PRD 8.21). Alasan wajib; tercatat di audit
 * dan riwayat. Perintah kerja yang masih mengikuti antrean lama ikut dialihkan server.
 */
function DialogAlihkan({
  keluhan,
  pilihan,
  wajib,
}: {
  keluhan: Keluhan;
  pilihan: UnitPengelolaRingkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const tujuan = pilihan.filter((unit) => unit.Id !== keluhan.UnitPengelolaId);
  const form = useForm({ UnitPengelolaId: TANPA_PILIHAN, Alasan: '', Versi: keluhan.Versi });
  const ubahBuka = (terbuka: boolean) => {
    if (terbuka) {
      form.setData({ UnitPengelolaId: TANPA_PILIHAN, Alasan: '', Versi: keluhan.Versi });
      form.clearErrors();
    }
    setBuka(terbuka);
  };
  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      UnitPengelolaId: data.UnitPengelolaId === TANPA_PILIHAN ? null : data.UnitPengelolaId,
    }));
    form.put(ruteKeluhan.unitPengelola(keluhan.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };
  return (
    <Dialog open={buka} onOpenChange={ubahBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" disabled={tujuan.length === 0}>
          Alihkan
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Alihkan ke Unit Pengelola Lain</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Saat ini dikelola{' '}
              <span className="font-medium text-foreground">
                {keluhan.UnitPengelola?.Nama ?? 'belum ada unit pengelola'}
              </span>
              . Perintah kerja keluhan ini yang masih di antrean lama ikut dialihkan.
            </p>
            <div className="space-y-1.5">
              <Label nama="UnitPengelolaId">Unit pengelola tujuan</Label>
              <Combobox
                nilai={form.data.UnitPengelolaId}
                onPilih={(v) => form.setData('UnitPengelolaId', v)}
                opsi={opsiUnitPengelola(tujuan, false)}
                placeholder="Pilih unit pengelola"
              />
              {form.errors.UnitPengelolaId && (
                <p className="text-sm text-destructive">{form.errors.UnitPengelolaId}</p>
              )}
            </div>
            <div className="space-y-1.5">
              <Label nama="Alasan">Alasan pengalihan</Label>
              <Textarea
                rows={3}
                value={form.data.Alasan}
                onChange={(e) => form.setData('Alasan', e.target.value)}
                placeholder="Mis. printer adalah perangkat IT, bukan peralatan gedung."
              />
              {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Alihkan Keluhan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function KeluhanShow({
  keluhan,
  dapatMengelola,
  prioritasAwal,
  transisiDiizinkan,
  wajib,
  pakaiUnitPengelola,
  pilihanUnitPengelola,
  hambatanPengalihan,
}: Props) {
  const dapatMengalihkan = pakaiUnitPengelola && dapatMengelola;
  return (
    <KerangkaAplikasi>
      <Head title={keluhan.Nomor} />
      <div className="mb-5">
        <Link
          href={ruteKeluhan.index}
          className="inline-flex min-h-11 items-center gap-2 rounded-[5px] text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring md:min-h-0"
        >
          ← Kembali ke Keluhan
        </Link>
      </div>
      <KepalaHalaman
        className="mb-5"
        judul={keluhan.Judul}
        labelBreadcrumb={keluhan.Nomor}
        lencana={
          <>
            <span className="font-mono text-sm text-muted-foreground">{keluhan.Nomor}</span>
            <Badge variant={VARIAN_PRIORITAS_KELUHAN[keluhan.Prioritas]}>{keluhan.Prioritas}</Badge>
            <Badge variant={VARIAN_STATUS_KELUHAN[keluhan.Status]}>{keluhan.Status}</Badge>
          </>
        }
        deskripsi={
          <>
            Dilaporkan {formatTanggal(keluhan.DilaporkanPada)} oleh {keluhan.NamaPelapor}
            {pakaiUnitPengelola && (
              <>
                {' · '}Dikelola: {keluhan.UnitPengelola?.Nama ?? 'belum ada unit pengelola'}
              </>
            )}
          </>
        }
        aksi={
          <>
            {/* Keluhan final tidak dapat dialihkan: tombolnya tidak ditawarkan sama sekali. */}
            {dapatMengalihkan && hambatanPengalihan === null && (
              <DialogAlihkan keluhan={keluhan} pilihan={pilihanUnitPengelola} wajib={wajib.unitPengelola} />
            )}
            {dapatMengelola && (
              <DialogPrioritas keluhan={keluhan} prioritasAwal={prioritasAwal} wajib={wajib.prioritas} />
            )}
            <DialogStatus
              keluhan={keluhan}
              transisi={
                dapatMengelola ? transisiDiizinkan : transisiDiizinkan.filter((s) => s === 'Dibatalkan')
              }
              wajib={wajib.status}
            />
          </>
        }
      />
      <div className="grid gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
        <div className="space-y-5">
          <Card>
            <CardHeader>
              <CardTitle>Detail Keluhan</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="whitespace-pre-wrap text-sm leading-6">{keluhan.Deskripsi}</p>
              <dl className="grid gap-4 border-t border-border pt-4 text-sm sm:grid-cols-2">
                <div>
                  <dt className="text-muted-foreground">Kategori</dt>
                  <dd className="font-medium">{keluhan.NamaKategori}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Lokasi</dt>
                  <dd className="font-medium">{keluhan.NamaLokasi}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Aset</dt>
                  <dd className="font-medium">
                    {keluhan.NamaAset ? `${keluhan.KodeAset} · ${keluhan.NamaAset}` : 'Tidak terkait aset'}
                  </dd>
                </div>
                {pakaiUnitPengelola && (
                  <div>
                    <dt className="text-muted-foreground">Dikelola</dt>
                    <dd className="font-medium">
                      {keluhan.UnitPengelola ? (
                        <>
                          {keluhan.UnitPengelola.Nama}{' '}
                          <span className="font-mono text-xs font-normal text-muted-foreground">
                            {keluhan.UnitPengelola.Kode}
                          </span>
                        </>
                      ) : (
                        <span className="font-normal text-muted-foreground">
                          Belum ada unit pengelola (hanya terlihat lewat lokasinya)
                        </span>
                      )}
                    </dd>
                  </div>
                )}
                <div>
                  <dt className="text-muted-foreground">Tingkat layanan</dt>
                  <dd className="font-medium">{keluhan.NamaTingkatLayanan ?? 'Tanpa SLA'}</dd>
                </div>
                {keluhan.LabelUsulanUrgensi && (
                  <div>
                    <dt className="text-muted-foreground">Seberapa mendesak</dt>
                    <dd className="font-medium">
                      Usulan pelapor: {keluhan.LabelUsulanUrgensi}
                      {keluhan.PrioritasUsulan && (
                        <span className="font-normal text-muted-foreground">
                          {' '}
                          (setara prioritas {keluhan.PrioritasUsulan})
                        </span>
                      )}
                    </dd>
                  </div>
                )}
              </dl>
            </CardContent>
          </Card>
          {dapatMengelola && <PanelKolaborasi jenisEntitas="Keluhan" entitasId={keluhan.Id} />}
        </div>
        <div className="space-y-5">
          <Card>
            <CardHeader>
              <CardTitle>SLA</CardTitle>
            </CardHeader>
            <CardContent>
              <dl className="space-y-3 text-sm">
                <div>
                  <dt className="text-muted-foreground">Batas respons</dt>
                  <dd className="font-medium">{formatTanggal(keluhan.BatasResponsPada)}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Direspons</dt>
                  <dd className="font-medium">{formatTanggal(keluhan.DiresponsPada)}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Batas penyelesaian</dt>
                  <dd className="font-medium">{formatTanggal(keluhan.BatasPenyelesaianPada)}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Diselesaikan</dt>
                  <dd className="font-medium">{formatTanggal(keluhan.DiresolusikanPada)}</dd>
                </div>
              </dl>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Riwayat Status</CardTitle>
            </CardHeader>
            <CardContent>
              <ol className="space-y-4">
                {keluhan.RiwayatStatus.map((riwayat) => (
                  <li key={riwayat.Id} className="relative border-l-2 border-border pl-4">
                    <div className="font-medium text-sm">
                      {/* Status sebelum = sesudah hanya ditulis pengalihan unit pengelola. */}
                      {riwayat.StatusSebelum === riwayat.StatusSesudah
                        ? 'Dialihkan ke unit pengelola lain'
                        : riwayat.StatusSesudah}
                    </div>
                    <div className="text-xs text-muted-foreground">
                      {formatTanggal(riwayat.DiubahPada)} · {riwayat.NamaPengubah ?? 'Sistem'}
                    </div>
                    {riwayat.Catatan && (
                      <p className="mt-1 text-sm text-muted-foreground">{riwayat.Catatan}</p>
                    )}
                  </li>
                ))}
              </ol>
            </CardContent>
          </Card>
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
