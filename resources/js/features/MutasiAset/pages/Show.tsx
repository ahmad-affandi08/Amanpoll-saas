import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import type {
  DetailMutasiAset,
  PermintaanMutasiAset,
  PilihanJenisMutasiAset,
  StatusDetailMutasiAset,
} from '@/features/SiklusAset/types';
import { VARIAN_BADGE_STATUS_MUTASI } from '@/features/SiklusAset/status';
import type { Aset } from '@/features/Aset/types';
import { ruteMutasiAset } from '@/features/MutasiAset/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';

interface Props {
  permintaan: PermintaanMutasiAset;
  aset: Aset[];
  /** Pilihan jenis mutasi, dibaca dari enum di server. */
  daftarJenis: PilihanJenisMutasiAset[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const VARIAN_BADGE_DETAIL: Record<
  StatusDetailMutasiAset,
  'sukses' | 'netral' | 'perhatian' | 'info' | 'bahaya'
> = {
  Menunggu: 'perhatian',
  Disetujui: 'info',
  Ditolak: 'bahaya',
  Selesai: 'sukses',
  Dibatalkan: 'netral',
};

/** Keputusan masih bisa diubah selama aset belum benar-benar berpindah. */
const STATUS_DETAIL_TERBUKA: StatusDetailMutasiAset[] = ['Menunggu', 'Disetujui', 'Ditolak'];

function DialogTolakAset({ detail, wajib }: { detail: DetailMutasiAset; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Disetujui: false, AlasanPenolakan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteMutasiAset.putuskanDetail(detail.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="ghost" size="sm">
          Tolak
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tolak {detail.NamaAset ?? detail.KodeAset}</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="AlasanPenolakan">Alasan Penolakan</Label>
              <Textarea
                id="AlasanPenolakan"
                value={form.data.AlasanPenolakan}
                onChange={(e) => form.setData('AlasanPenolakan', e.target.value)}
                placeholder="Mis. alat sedang dipakai tindakan sampai pekan depan."
              />
              {form.errors.AlasanPenolakan && (
                <p className="text-sm text-destructive">{form.errors.AlasanPenolakan}</p>
              )}
            </div>
            <p className="text-sm text-muted-foreground">
              Aset ini tidak ikut berpindah saat mutasi dieksekusi; permintaannya sendiri tetap berjalan untuk
              aset yang lain.
            </p>
            <DialogFooter>
              <Button type="submit" variant="destructive" disabled={form.processing}>
                Tolak Aset
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

/**
 * Verifikasi fisik saat pengambilan. Masukan tetap berupa kolom teks supaya
 * pemindai genggam -- yang mengetikkan kode lalu menekan Enter -- dan
 * pengetikan manual saat stikernya rusak sama-sama bisa dipakai.
 */
function PanelPindai({ permintaan, wajib }: { permintaan: PermintaanMutasiAset; wajib: AturanWajib }) {
  const form = useForm({ Kode: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteMutasiAset.pindai(permintaan.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => form.setData('Kode', ''),
    });
  };

  const terverifikasi = permintaan.DetailMutasiAset.filter((d) => d.DipindaiPada !== null).length;
  const perluDiambil = permintaan.DetailMutasiAset.filter((d) =>
    ['Menunggu', 'Disetujui'].includes(d.Status),
  ).length;

  return (
    <div className="rounded-md border border-border bg-card px-5 py-4">
      <h2 className="mb-1 text-sm font-semibold text-foreground">Verifikasi Pengambilan</h2>
      <p className="mb-3 text-sm text-muted-foreground">
        Pindai QR/barcode yang menempel di aset saat barangnya diambil. Kode di luar permintaan ini ditolak,
        sehingga alat sejenis tidak tertukar. {terverifikasi} dari {perluDiambil} aset sudah terverifikasi.
      </p>
      <AturanWajibProvider aturan={wajib}>
        <form onSubmit={submit} className="flex items-end gap-2">
          <div className="flex-1 space-y-1.5">
            <Label nama="Kode">Kode Aset</Label>
            <Input
              id="Kode"
              autoFocus
              value={form.data.Kode}
              onChange={(e) => form.setData('Kode', e.target.value)}
              placeholder="Pindai atau ketik kode"
            />
          </div>
          <Button type="submit" disabled={form.processing || form.data.Kode.trim() === ''}>
            Verifikasi
          </Button>
        </form>
      </AturanWajibProvider>
      {form.errors.Kode && <p className="mt-2 text-sm text-destructive">{form.errors.Kode}</p>}
    </div>
  );
}

function DialogTambahAset({
  permintaan,
  aset,
  wajib,
}: {
  permintaan: PermintaanMutasiAset;
  aset: Aset[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ AsetId: '', Catatan: '' });

  const asetTersedia = aset.filter((a) => !permintaan.DetailMutasiAset.some((d) => d.AsetId === a.Id));

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteMutasiAset.detail2(permintaan.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          Tambah Aset
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tambah Aset ke Mutasi</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="AsetId">Aset</Label>
              <Combobox
                nilai={form.data.AsetId}
                onPilih={(v) => form.setData('AsetId', v)}
                opsi={opsiDari(asetTersedia, (a) => `${a.Nama} (${a.KodeAset})`)}
                placeholder="Pilih aset"
              />
              {form.errors.AsetId && <p className="text-sm text-destructive">{form.errors.AsetId}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing || !form.data.AsetId}>
                Tambah
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function MutasiAsetShow({ permintaan, aset, daftarJenis, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const labelJenis =
    daftarJenis.find((j) => j.nilai === permintaan.JenisMutasi)?.label ?? permintaan.JenisMutasi;
  const bolehMemutuskan = permintaan.Status === 'Menunggu' || permintaan.Status === 'Disetujui';

  const setujuiDetail = (detailId: string) =>
    router.post(ruteMutasiAset.putuskanDetail(detailId), { Disetujui: true }, { preserveScroll: true });
  const hapusDetail = async (detailId: string) => {
    if (
      !(await konfirmasi({
        judul: 'Hapus aset ini dari daftar mutasi?',
        deskripsi: 'Aset dikeluarkan dari permintaan; permintaan itu sendiri tetap ada.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteMutasiAset.detailDetail(detailId), { preserveScroll: true });
  };

  const submit = () => router.post(ruteMutasiAset.submit(permintaan.Id), {}, { preserveScroll: true });
  const batalkan = async () => {
    if (
      !(await konfirmasi({
        judul: 'Batalkan permintaan mutasi ini?',
        deskripsi: 'Permintaan tidak dapat diajukan lagi dan lokasi aset tidak berubah.',
        ragam: 'bahaya',
        ilustrasi: '/assets/3d/peringatan.webp',
      }))
    )
      return;
    router.post(ruteMutasiAset.batalkan(permintaan.Id), {}, { preserveScroll: true });
  };
  const eksekusi = async () => {
    if (
      !(await konfirmasi({
        judul: 'Eksekusi mutasi ini?',
        deskripsi: 'Lokasi/unit aset akan diperbarui.',
        ragam: 'bahaya',
        ilustrasi: '/assets/3d/peringatan.webp',
      }))
    )
      return;
    router.post(ruteMutasiAset.eksekusi(permintaan.Id), {}, { preserveScroll: true });
  };

  return (
    <KerangkaAplikasi>
      <Head title={permintaan.Nomor} />
      <div className="space-y-5">
        <KepalaHalaman
          judul={labelJenis}
          labelBreadcrumb={permintaan.Nomor}
          deskripsi={
            <>
              <span className="font-mono">{permintaan.Nomor}</span> · {permintaan.NamaLokasiAsal ?? '—'} →{' '}
              {permintaan.NamaLokasiTujuan ?? permintaan.NamaUnitTujuan ?? '—'}
            </>
          }
          aksi={
            <>
              <Badge variant={VARIAN_BADGE_STATUS_MUTASI[permintaan.Status]}>{permintaan.Status}</Badge>
              {permintaan.Status === 'Draft' && (
                <Button size="sm" onClick={submit}>
                  Submit
                </Button>
              )}
              {(permintaan.Status === 'Draft' || permintaan.Status === 'Menunggu') && (
                <Button size="sm" variant="outline" onClick={batalkan}>
                  Batalkan
                </Button>
              )}
              {permintaan.Status === 'Disetujui' && (
                <Button size="sm" onClick={eksekusi}>
                  Eksekusi
                </Button>
              )}
            </>
          }
        />

        <dl className="grid gap-4 rounded-md border border-border bg-card px-5 py-4 sm:grid-cols-3">
          <div>
            <dt className="text-[13px] text-grafit-700">Diminta Oleh</dt>
            <dd className="text-sm font-medium text-foreground">{permintaan.NamaDimintaOleh ?? '—'}</dd>
          </div>
          <div>
            <dt className="text-[13px] text-grafit-700">Diminta Pada</dt>
            <dd className="text-sm font-medium text-foreground">
              {new Date(permintaan.DimintaPada).toLocaleString('id-ID')}
            </dd>
          </div>
          <div>
            <dt className="text-[13px] text-grafit-700">Selesai Pada</dt>
            <dd className="text-sm font-medium text-foreground">
              {permintaan.SelesaiPada ? new Date(permintaan.SelesaiPada).toLocaleString('id-ID') : '—'}
            </dd>
          </div>
        </dl>

        {permintaan.Status === 'Disetujui' && <PanelPindai permintaan={permintaan} wajib={wajib.pindai} />}

        <div className="rounded-md border border-border bg-card px-5 py-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Daftar Aset</h2>
            {permintaan.Status === 'Draft' && (
              <DialogTambahAset permintaan={permintaan} aset={aset} wajib={wajib.detail} />
            )}
          </div>
          {permintaan.DetailMutasiAset.length === 0 && (
            <KeadaanKosong
              judul="Belum ada aset ditambahkan."
              deskripsi="Tambahkan aset yang akan dimutasi sebelum submit."
            />
          )}
          <div className="space-y-2">
            {permintaan.DetailMutasiAset.map((d) => (
              <div
                key={d.Id}
                className="flex items-start justify-between gap-3 rounded-md border border-border px-3 py-2 text-sm"
              >
                <div className="min-w-0">
                  <span className="font-medium text-foreground">{d.NamaAset ?? '—'}</span>
                  <span className="ml-2 font-mono text-xs text-muted-foreground">{d.KodeAset}</span>
                  {d.Status === 'Ditolak' && d.AlasanPenolakan && (
                    <p className="mt-0.5 text-xs text-muted-foreground">
                      Ditolak {d.NamaDiputuskanOleh ? `oleh ${d.NamaDiputuskanOleh}` : ''}:{' '}
                      {d.AlasanPenolakan}
                    </p>
                  )}
                  {d.DipindaiPada && (
                    <p className="mt-0.5 text-xs text-muted-foreground">
                      Terverifikasi {d.NamaDipindaiOleh ? `oleh ${d.NamaDipindaiOleh}` : ''} pada{' '}
                      {new Date(d.DipindaiPada).toLocaleString('id-ID')}
                    </p>
                  )}
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  {d.DipindaiPada && <Badge variant="sukses">Terverifikasi</Badge>}
                  <Badge variant={VARIAN_BADGE_DETAIL[d.Status]}>{d.Status}</Badge>
                  {bolehMemutuskan && STATUS_DETAIL_TERBUKA.includes(d.Status) && (
                    <>
                      {d.Status !== 'Disetujui' && (
                        <Button variant="ghost" size="sm" onClick={() => setujuiDetail(d.Id)}>
                          Setujui
                        </Button>
                      )}
                      {d.Status !== 'Ditolak' && <DialogTolakAset detail={d} wajib={wajib.keputusan} />}
                    </>
                  )}
                  {permintaan.Status === 'Draft' && (
                    <Button variant="ghost" size="sm" onClick={() => hapusDetail(d.Id)}>
                      Hapus
                    </Button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
