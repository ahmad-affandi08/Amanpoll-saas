import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { formatUang } from '@/lib/uang';
import type { PengajuanPenghapusanAset } from '@/features/SiklusAset/types';
import { VARIAN_BADGE_STATUS_PENGHAPUSAN } from '@/features/SiklusAset/status';
import type { Aset } from '@/features/Aset/types';
import { rutePenghapusanAset } from '@/features/PenghapusanAset/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';
import { InputUang } from '@/components/shared/InputUang';

interface Props {
  pengajuan: PengajuanPenghapusanAset;
  aset: Aset[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogTambahAset({
  pengajuan,
  aset,
  wajib,
}: {
  pengajuan: PengajuanPenghapusanAset;
  aset: Aset[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ AsetId: '', NilaiBukuSaatPenghapusan: '', HasilPelepasan: '' });

  const asetTersedia = aset.filter((a) => !pengajuan.DetailPenghapusanAset.some((d) => d.AsetId === a.Id));

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(rutePenghapusanAset.detail2(pengajuan.Id), form.data, {
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
          <DialogTitle>Tambah Aset ke Pengajuan</DialogTitle>
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
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-1.5">
                <Label nama="NilaiBukuSaatPenghapusan">Nilai Buku Saat Ini</Label>
                <InputUang
                  value={form.data.NilaiBukuSaatPenghapusan}
                  onChange={(nilai) => form.setData('NilaiBukuSaatPenghapusan', nilai)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="HasilPelepasan">Estimasi Hasil Pelepasan</Label>
                <InputUang
                  value={form.data.HasilPelepasan}
                  onChange={(nilai) => form.setData('HasilPelepasan', nilai)}
                />
              </div>
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

export default function PenghapusanAsetShow({ pengajuan, aset, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapusDetail = async (detailId: string) => {
    if (
      !(await konfirmasi({
        judul: 'Hapus aset ini dari pengajuan?',
        deskripsi: 'Aset dikeluarkan dari pengajuan; status asetnya tidak berubah.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(rutePenghapusanAset.detailDetail(detailId), { preserveScroll: true });
  };

  const submit = () => router.post(rutePenghapusanAset.submit(pengajuan.Id), {}, { preserveScroll: true });
  const batalkan = async () => {
    if (
      !(await konfirmasi({
        judul: 'Batalkan pengajuan penghapusan ini?',
        deskripsi: 'Pengajuan tidak dapat dilanjutkan dan aset tetap aktif.',
        ragam: 'bahaya',
        ilustrasi: '/assets/3d/peringatan.webp',
      }))
    )
      return;
    router.post(rutePenghapusanAset.batalkan(pengajuan.Id), {}, { preserveScroll: true });
  };
  const eksekusi = async () => {
    if (
      !(await konfirmasi({
        judul: 'Eksekusi penghapusan?',
        deskripsi: 'Aset akan diarsipkan dan tidak dapat dikembalikan lewat halaman ini.',
        ragam: 'bahaya',
        ilustrasi: '/assets/3d/peringatan.webp',
      }))
    )
      return;
    router.post(rutePenghapusanAset.eksekusi(pengajuan.Id), {}, { preserveScroll: true });
  };

  return (
    <KerangkaAplikasi>
      <Head title={pengajuan.Nomor} />
      <div className="space-y-6">
        <KepalaHalaman
          judul={pengajuan.MetodePenghapusan ?? 'Penghapusan Aset'}
          lencana={
            <>
              <p className="font-mono text-sm text-muted-foreground">{pengajuan.Nomor}</p>
            </>
          }
          meta={pengajuan.Alasan}
          aksi={
            <>
              <div className="flex items-center gap-2">
                <Badge variant={VARIAN_BADGE_STATUS_PENGHAPUSAN[pengajuan.Status]}>{pengajuan.Status}</Badge>
                {pengajuan.Status === 'Draft' && (
                  <Button size="sm" onClick={submit}>
                    Submit
                  </Button>
                )}
                {(pengajuan.Status === 'Draft' || pengajuan.Status === 'Menunggu') && (
                  <Button size="sm" variant="outline" onClick={batalkan}>
                    Batalkan
                  </Button>
                )}
                {pengajuan.Status === 'Disetujui' && (
                  <Button size="sm" variant="destructive" onClick={eksekusi}>
                    Eksekusi
                  </Button>
                )}
              </div>
            </>
          }
        />

        <div className="grid gap-4 sm:grid-cols-2">
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Diajukan Oleh</p>
            <p className="text-sm font-medium text-foreground">{pengajuan.NamaDiajukanOleh ?? '—'}</p>
          </div>
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Diselesaikan Pada</p>
            <p className="text-sm font-medium text-foreground">
              {pengajuan.DiselesaikanPada
                ? new Date(pengajuan.DiselesaikanPada).toLocaleString('id-ID')
                : '—'}
            </p>
          </div>
        </div>

        <div className="rounded-[9px] border border-border bg-card p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Daftar Aset</h2>
            {pengajuan.Status === 'Draft' && (
              <DialogTambahAset pengajuan={pengajuan} aset={aset} wajib={wajib.detail} />
            )}
          </div>
          {pengajuan.DetailPenghapusanAset.length === 0 && (
            <KeadaanKosong
              judul="Belum ada aset ditambahkan."
              deskripsi="Tambahkan aset yang akan dihapuskan."
            />
          )}
          <div className="space-y-2">
            {pengajuan.DetailPenghapusanAset.map((d) => (
              <div
                key={d.Id}
                className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
              >
                <div>
                  <span className="font-medium text-foreground">{d.NamaAset ?? '—'}</span>
                  <span className="ml-2 font-mono text-xs text-muted-foreground">{d.KodeAset}</span>
                  {d.NilaiBukuSaatPenghapusan && (
                    <span className="ml-2 text-xs text-muted-foreground">
                      Nilai buku: {formatUang(d.NilaiBukuSaatPenghapusan)}
                    </span>
                  )}
                </div>
                <div className="flex items-center gap-2">
                  <Badge
                    variant={
                      d.Status === 'Selesai' ? 'sukses' : d.Status === 'Dibatalkan' ? 'netral' : 'perhatian'
                    }
                  >
                    {d.Status}
                  </Badge>
                  {pengajuan.Status === 'Draft' && (
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
