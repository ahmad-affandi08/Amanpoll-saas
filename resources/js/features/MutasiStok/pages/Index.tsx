import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import type { JenisMutasiStok, MutasiStok } from '@/features/Persediaan/types';
import type { Paginasi } from '@/types/global';
import { VARIAN_BADGE_STATUS_MUTASI_STOK } from '@/features/Persediaan/status';
import { ruteMutasiStok } from '@/features/MutasiStok/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN, opsiDari } from '@/lib/pilihan';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';

interface Ringkas {
  Id: string;
  Nama: string;
}

interface Props {
  mutasiStok: Paginasi<MutasiStok>;
  gudang: Ringkas[];
  filter: { status?: string; jenis?: string };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const LABEL_JENIS: Record<JenisMutasiStok, string> = {
  Penerimaan: 'Penerimaan',
  Pengeluaran: 'Pengeluaran',
  Transfer: 'Transfer',
  Adjustment: 'Penyesuaian',
  Return: 'Retur',
};

function butuhGudangAsal(jenis: JenisMutasiStok): boolean {
  return jenis === 'Pengeluaran' || jenis === 'Adjustment' || jenis === 'Transfer';
}

function butuhGudangTujuan(jenis: JenisMutasiStok): boolean {
  return jenis === 'Penerimaan' || jenis === 'Return' || jenis === 'Transfer';
}

function DialogBuatMutasi({ gudang, wajib }: { gudang: Ringkas[]; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Jenis: 'Penerimaan' as JenisMutasiStok,
    GudangAsalId: TANPA_PILIHAN,
    GudangTujuanId: TANPA_PILIHAN,
    Catatan: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      ruteMutasiStok.index,
      {
        Jenis: form.data.Jenis,
        GudangAsalId: form.data.GudangAsalId === TANPA_PILIHAN ? null : form.data.GudangAsalId,
        GudangTujuanId: form.data.GudangTujuanId === TANPA_PILIHAN ? null : form.data.GudangTujuanId,
        Catatan: form.data.Catatan || null,
      },
      { onSuccess: () => setBuka(false) },
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Buat Mutasi Stok</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Buat Mutasi Stok</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Jenis">Jenis</Label>
              <Combobox
                nilai={form.data.Jenis}
                onPilih={(v) => form.setData('Jenis', v as JenisMutasiStok)}
                opsi={(Object.keys(LABEL_JENIS) as JenisMutasiStok[]).map((j) => ({
                  nilai: j,
                  label: LABEL_JENIS[j],
                }))}
              />
            </div>
            {butuhGudangAsal(form.data.Jenis) && (
              <div className="space-y-1.5">
                <Label nama="GudangAsalId">Gudang Asal</Label>
                <Combobox
                  nilai={form.data.GudangAsalId}
                  onPilih={(v) => form.setData('GudangAsalId', v)}
                  opsi={opsiDari(gudang, (g) => g.Nama)}
                  placeholder="Pilih gudang"
                />
              </div>
            )}
            {butuhGudangTujuan(form.data.Jenis) && (
              <div className="space-y-1.5">
                <Label nama="GudangTujuanId">Gudang Tujuan</Label>
                <Combobox
                  nilai={form.data.GudangTujuanId}
                  onPilih={(v) => form.setData('GudangTujuanId', v)}
                  opsi={opsiDari(gudang, (g) => g.Nama)}
                  placeholder="Pilih gudang"
                />
              </div>
            )}
            <div className="space-y-1.5">
              <Label nama="Catatan">
                Catatan {form.data.Jenis === 'Adjustment' && '(alasan penyesuaian, wajib)'}
              </Label>
              <Textarea
                value={form.data.Catatan}
                onChange={(e) => form.setData('Catatan', e.target.value)}
                rows={3}
              />
              {form.errors.Catatan && <p className="text-sm text-destructive">{form.errors.Catatan}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Buat Draft
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

/** Hanya penyaring yang benar-benar terisi yang ikut dibawa saat berpindah halaman. */
function filterAktif(filter: Props['filter']): Record<string, string> {
  return Object.fromEntries(
    Object.entries(filter).filter((pasangan): pasangan is [string, string] => Boolean(pasangan[1])),
  );
}

export default function MutasiStokIndex({ mutasiStok, gudang, filter, wajib }: Props) {
  return (
    <KerangkaAplikasi>
      <Head title="Mutasi Stok" />
      <KepalaHalaman
        judul="Mutasi Stok"
        deskripsi="Penerimaan, pengeluaran, transfer, penyesuaian, dan retur -- draf, posting, sampai audit."
        aksi={
          <>
            <TombolEkspor url={ruteMutasiStok.ekspor} filter={filter as Record<string, string>} />
            <DialogBuatMutasi gudang={gudang} wajib={wajib.mutasi} />
          </>
        }
        className="mb-6"
      />

      {mutasiStok.data.length === 0 ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/persediaan.webp"
          judul="Belum ada mutasi stok."
          deskripsi="Buat mutasi pertama untuk mulai mencatat pergerakan stok."
        />
      ) : (
        <div className="space-y-2">
          {mutasiStok.data.map((m) => (
            <Link
              key={m.Id}
              href={ruteMutasiStok.detail(m.Id)}
              className="flex items-center justify-between rounded-[9px] border border-border bg-card p-4 hover:border-teknisi-600/40"
            >
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-mono text-sm text-muted-foreground">{m.Nomor}</span>
                  <Badge variant="netral">{LABEL_JENIS[m.Jenis]}</Badge>
                </div>
                <div className="mt-1 text-sm text-foreground">
                  {m.NamaGudangAsal && <span>{m.NamaGudangAsal}</span>}
                  {m.NamaGudangAsal && m.NamaGudangTujuan && <span className="mx-1">&rarr;</span>}
                  {m.NamaGudangTujuan && <span>{m.NamaGudangTujuan}</span>}
                </div>
                <div className="text-xs text-muted-foreground">
                  {new Date(m.Tanggal).toLocaleString('id-ID')} &middot; {m.NamaDibuatOleh}
                </div>
              </div>
              <Badge variant={VARIAN_BADGE_STATUS_MUTASI_STOK[m.Status]}>{m.Status}</Badge>
            </Link>
          ))}
          <KontrolPaginasi
            meta={mutasiStok.meta}
            onNavigasi={(halaman) => navigasiHalaman(halaman, filterAktif(filter))}
          />
        </div>
      )}
    </KerangkaAplikasi>
  );
}
