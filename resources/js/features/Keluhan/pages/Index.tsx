import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import type { Keluhan, PrioritasKeluhan, StatusKeluhan } from '@/features/Keluhan/types';
import type { Paginasi } from '@/types/global';
import { VARIAN_PRIORITAS_KELUHAN, VARIAN_STATUS_KELUHAN } from '@/features/Keluhan/status';
import { ruteKeluhan } from '@/features/Keluhan/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';

interface KategoriRingkas {
  Id: string;
  Nama: string;
  PrioritasBawaan: PrioritasKeluhan;
  AsetWajib: boolean;
}
interface AsetRingkas {
  Id: string;
  KodeAset: string;
  Nama: string;
  LokasiId: string | null;
}
interface Ringkas {
  Id: string;
  Nama: string;
}
interface Props {
  keluhan: Paginasi<Keluhan>;
  kategori: KategoriRingkas[];
  aset: AsetRingkas[];
  lokasi: Ringkas[];
  filter: { status?: string; prioritas?: string };
  dapatMengelola: boolean;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}
function DialogBuatKeluhan({
  kategori,
  aset,
  lokasi,
  dapatMengelola,
  wajib,
}: Pick<Props, 'kategori' | 'aset' | 'lokasi' | 'dapatMengelola'> & { wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    KategoriKeluhanId: '',
    AsetId: TANPA_PILIHAN,
    LokasiId: '',
    Judul: '',
    Deskripsi: '',
    Prioritas: TANPA_PILIHAN,
    Lampiran: [] as File[],
  });
  const kategoriDipilih = kategori.find((item) => item.Id === form.data.KategoriKeluhanId);

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      AsetId: data.AsetId === TANPA_PILIHAN ? null : data.AsetId,
      Prioritas: data.Prioritas === TANPA_PILIHAN ? null : data.Prioritas,
    }));
    form.post(ruteKeluhan.index, { onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Buat Keluhan</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Buat Keluhan</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="KategoriKeluhanId">Kategori</Label>
              <Combobox
                nilai={form.data.KategoriKeluhanId}
                onPilih={(value) => {
                  form.setData('KategoriKeluhanId', value);
                }}
                opsi={opsiDari(kategori, (item) => item.Nama)}
                placeholder="Pilih kategori"
              />
              {form.errors.KategoriKeluhanId && (
                <p className="text-sm text-destructive">{form.errors.KategoriKeluhanId}</p>
              )}
            </div>
            <div className="space-y-1.5">
              <Label nama="AsetId">Aset {kategoriDipilih?.AsetWajib ? '(wajib)' : '(opsional)'}</Label>
              <Combobox
                nilai={form.data.AsetId}
                onPilih={(value) => {
                  const dipilih = aset.find((item) => item.Id === value);
                  form.setData((data) => ({
                    ...data,
                    AsetId: value,
                    LokasiId: dipilih?.LokasiId ?? data.LokasiId,
                  }));
                }}
                opsi={[
                  opsiKosong('Tanpa aset'),
                  ...opsiDari(aset, (item) => `${item.KodeAset} · ${item.Nama}`),
                ]}
              />
              {form.errors.AsetId && <p className="text-sm text-destructive">{form.errors.AsetId}</p>}
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="LokasiId">Lokasi</Label>
                <Combobox
                  nilai={form.data.LokasiId}
                  onPilih={(value) => form.setData('LokasiId', value)}
                  opsi={opsiDari(lokasi, (item) => item.Nama)}
                  placeholder="Pilih lokasi"
                />
                {form.errors.LokasiId && <p className="text-sm text-destructive">{form.errors.LokasiId}</p>}
              </div>
              {dapatMengelola && (
                <div className="space-y-1.5">
                  <Label nama="Prioritas">Prioritas</Label>
                  <Combobox
                    nilai={form.data.Prioritas}
                    onPilih={(value) => form.setData('Prioritas', value)}
                    opsi={[
                      opsiKosong('Gunakan bawaan kategori'),
                      ...(['Rendah', 'Normal', 'Tinggi', 'Kritis'] as PrioritasKeluhan[]).map((p) => ({
                        nilai: p,
                        label: p,
                      })),
                    ]}
                  />
                </div>
              )}
            </div>
            <div className="space-y-1.5">
              <Label nama="Judul">Judul</Label>
              <Input value={form.data.Judul} onChange={(e) => form.setData('Judul', e.target.value)} />
              {form.errors.Judul && <p className="text-sm text-destructive">{form.errors.Judul}</p>}
            </div>
            <div className="space-y-1.5">
              <Label nama="Deskripsi">Deskripsi</Label>
              <Textarea
                rows={5}
                value={form.data.Deskripsi}
                onChange={(e) => form.setData('Deskripsi', e.target.value)}
              />
              {form.errors.Deskripsi && <p className="text-sm text-destructive">{form.errors.Deskripsi}</p>}
            </div>
            <div className="space-y-1.5">
              <Label nama="Lampiran">Lampiran bukti (maksimal 5)</Label>
              <Input
                type="file"
                multiple
                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt"
                onChange={(event) => form.setData('Lampiran', Array.from(event.target.files ?? []))}
              />
              {form.errors.Lampiran && <p className="text-sm text-destructive">{form.errors.Lampiran}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Kirim Keluhan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function formatTanggal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}
function labelSla(item: Keluhan): string {
  if (!item.BatasPenyelesaianPada) return 'Tanpa SLA';
  const lewat = !item.DiresolusikanPada && new Date(item.BatasPenyelesaianPada).getTime() < Date.now();
  return `${lewat ? 'Terlewati' : 'Batas'} ${formatTanggal(item.BatasPenyelesaianPada)}`;
}

/** Hanya penyaring yang benar-benar terisi yang ikut dibawa saat berpindah halaman. */
function filterAktif(filter: Props['filter']): Record<string, string> {
  return Object.fromEntries(
    Object.entries(filter).filter((pasangan): pasangan is [string, string] => Boolean(pasangan[1])),
  );
}

export default function KeluhanIndex({
  keluhan,
  kategori,
  aset,
  lokasi,
  filter,
  dapatMengelola,
  wajib,
}: Props) {
  const filterData = (kunci: 'status' | 'prioritas', nilai: string) =>
    router.get(
      ruteKeluhan.index,
      { ...filter, [kunci]: nilai === TANPA_PILIHAN ? undefined : nilai },
      { preserveState: true, replace: true },
    );
  return (
    <KerangkaAplikasi>
      <Head title="Keluhan" />
      <KepalaHalaman
        judul="Keluhan"
        deskripsi={
          dapatMengelola
            ? 'Triage dan pantau keluhan beserta kepatuhan SLA.'
            : 'Laporkan masalah dan pantau status keluhan Anda.'
        }
        aksi={
          <>
            <DialogBuatKeluhan
              kategori={kategori}
              aset={aset}
              lokasi={lokasi}
              dapatMengelola={dapatMengelola}
              wajib={wajib.keluhan}
            />
          </>
        }
        className="mb-6"
      />
      <div className="mb-4 grid gap-2 sm:grid-cols-2 lg:max-w-xl">
        <Combobox
          nilai={filter.status ?? TANPA_PILIHAN}
          onPilih={(value) => filterData('status', value)}
          opsi={[
            opsiKosong('Semua status'),
            ...(
              [
                'Baru',
                'Ditinjau',
                'Diterima',
                'Diproses',
                'Selesai',
                'Ditutup',
                'Ditolak',
                'Dibatalkan',
              ] as StatusKeluhan[]
            ).map((s) => ({ nilai: s, label: s })),
          ]}
          placeholder="Semua status"
        />
        <Combobox
          nilai={filter.prioritas ?? TANPA_PILIHAN}
          onPilih={(value) => filterData('prioritas', value)}
          opsi={[
            opsiKosong('Semua prioritas'),
            ...(['Rendah', 'Normal', 'Tinggi', 'Kritis'] as PrioritasKeluhan[]).map((p) => ({
              nilai: p,
              label: p,
            })),
          ]}
          placeholder="Semua prioritas"
        />
      </div>
      {keluhan.data.length === 0 ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/keluhan.webp"
          judul="Belum ada keluhan."
          deskripsi="Buat keluhan pertama agar masalah dapat segera ditindaklanjuti."
        />
      ) : (
        <div className="space-y-3">
          {keluhan.data.map((item) => (
            <Link
              key={item.Id}
              href={ruteKeluhan.detail(item.Id)}
              className="block rounded-[9px] border border-border bg-card p-4 transition-colors hover:border-teknisi-600/40"
            >
              <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="font-mono text-xs text-muted-foreground">{item.Nomor}</span>
                    <Badge variant={VARIAN_PRIORITAS_KELUHAN[item.Prioritas]}>{item.Prioritas}</Badge>
                    <Badge variant={VARIAN_STATUS_KELUHAN[item.Status]}>{item.Status}</Badge>
                  </div>
                  <h2 className="mt-2 truncate font-medium text-foreground">{item.Judul}</h2>
                  <p className="mt-1 text-sm text-muted-foreground">
                    {item.NamaKategori} · {item.NamaLokasi} {item.NamaAset ? `· ${item.NamaAset}` : ''}
                  </p>
                </div>
                <div className="shrink-0 text-left text-xs text-muted-foreground sm:text-right">
                  <div>{formatTanggal(item.DilaporkanPada)}</div>
                  <div
                    className={
                      item.BatasPenyelesaianPada &&
                      !item.DiresolusikanPada &&
                      new Date(item.BatasPenyelesaianPada).getTime() < Date.now()
                        ? 'mt-1 text-destructive'
                        : 'mt-1'
                    }
                  >
                    {labelSla(item)}
                  </div>
                </div>
              </div>
            </Link>
          ))}
          <KontrolPaginasi
            meta={keluhan.meta}
            onNavigasi={(halaman) => navigasiHalaman(halaman, filterAktif(filter))}
          />
        </div>
      )}
    </KerangkaAplikasi>
  );
}
