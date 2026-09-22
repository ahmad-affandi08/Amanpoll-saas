import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { LangkahSequence, SequenceEmail } from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

type PilihanTemplate = { Id: string; Nama: string; Kode: string };

interface Props {
  sequence: SequenceEmail[];
  template: PilihanTemplate[];
  kodeSequenceTrial: string;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const AKAR = rutePemasaran.emailSequence;

export default function PemasaranEmailSequence({ sequence, template, kodeSequenceTrial, wajib }: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Sequence Email" />

      <KepalaHalaman
        judul="Sequence Email"
        deskripsi="Rangkaian email onboarding beserta jadwalnya, disusun dari konsol tanpa rilis."
        tanpaBreadcrumb
        aksi={<DialogSequence sequence={null} wajib={wajib.sequence} />}
        className="mb-6"
      />

      <div className="mb-6 rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
        Sequence yang dijalankan saat trial dimulai ditentukan lewat setelan{' '}
        <span className="font-mono">{kodeSequenceTrial}</span> di halaman Pengaturan Pemasaran.
      </div>

      {sequence.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada sequence email.</p>
      ) : (
        <div className="space-y-4">
          {sequence.map((satu) => (
            <KartuSequence key={satu.Id} sequence={satu} template={template} wajib={wajib} />
          ))}
        </div>
      )}
    </KerangkaPlatform>
  );
}

function KartuSequence({
  sequence,
  template,
  wajib,
}: {
  sequence: SequenceEmail;
  template: PilihanTemplate[];
  wajib: Record<string, AturanWajib>;
}) {
  const konfirmasi = useKonfirmasi();
  const terkunci = sequence.JumlahBerjalan > 0;

  const hapusLangkah = async (langkah: LangkahSequence) => {
    const setuju = await konfirmasi({
      judul: 'Hapus langkah?',
      deskripsi: `Langkah hari ke-${langkah.HariKe} tidak lagi dijadwalkan untuk pendaftar berikutnya.`,
      ragam: 'bahaya',
    });

    if (setuju) {
      router.delete(`${AKAR}/${sequence.Kode}/langkah/${langkah.Id}`, { preserveScroll: true });
    }
  };

  return (
    <Card>
      <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
        <div>
          <CardTitle className="text-base">{sequence.Nama}</CardTitle>
          <p className="font-mono text-xs text-muted-foreground">{sequence.Kode}</p>
          {sequence.Keterangan ? (
            <p className="mt-1 text-sm text-muted-foreground">{sequence.Keterangan}</p>
          ) : null}
        </div>
        <div className="flex shrink-0 items-center gap-2">
          {sequence.Aktif ? <Badge>Aktif</Badge> : <Badge variant="secondary">Nonaktif</Badge>}
          <DialogSequence sequence={sequence} wajib={wajib.sequence} />
        </div>
      </CardHeader>

      <CardContent className="space-y-3">
        {terkunci ? (
          <p className="rounded-md border border-dashed p-3 text-sm text-muted-foreground">
            {sequence.JumlahBerjalan} pendaftaran masih berjalan. Kirimannya sudah terjadwal, jadi langkah
            tidak dapat diubah — nonaktifkan sequence ini lalu buat versi barunya.
          </p>
        ) : null}

        {sequence.Langkah.length === 0 ? (
          <p className="text-sm text-muted-foreground">Belum ada langkah.</p>
        ) : (
          <ul className="divide-y rounded-md border">
            {sequence.Langkah.map((langkah) => (
              <li key={langkah.Id} className="flex items-center justify-between gap-3 p-3">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium">
                    #{langkah.Urutan} · {langkah.TemplateNama}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {langkah.HariKe === 0 ? 'Saat pendaftaran' : `Hari ke-${langkah.HariKe}`}
                    {langkah.Aktif ? '' : ' · nonaktif'}
                  </p>
                </div>
                {terkunci ? null : (
                  <div className="flex shrink-0 gap-1">
                    <DialogLangkah
                      sequence={sequence}
                      langkah={langkah}
                      template={template}
                      wajib={wajib.langkah}
                    />
                    <Button variant="ghost" size="sm" onClick={() => hapusLangkah(langkah)}>
                      Hapus
                    </Button>
                  </div>
                )}
              </li>
            ))}
          </ul>
        )}

        {terkunci ? null : (
          <div className="flex justify-end">
            <DialogLangkah sequence={sequence} langkah={null} template={template} wajib={wajib.langkah} />
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function DialogSequence({ sequence, wajib }: { sequence: SequenceEmail | null; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);

  const form = useForm({
    Kode: sequence?.Kode ?? '',
    Nama: sequence?.Nama ?? '',
    Keterangan: sequence?.Keterangan ?? '',
    Aktif: sequence?.Aktif ?? true,
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();

    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!sequence) form.reset();
      },
    };

    if (sequence) {
      form.put(`${AKAR}/${sequence.Kode}`, opsi);
    } else {
      form.post(AKAR, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={sequence ? 'outline' : 'default'} size={sequence ? 'sm' : 'default'}>
          {sequence ? 'Ubah' : 'Tambah Sequence'}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{sequence ? 'Ubah Sequence' : 'Tambah Sequence'}</DialogTitle>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={kirim} className="grid gap-4">
            <BidangKode
              nilai={form.data.Kode}
              onUbah={(nilai) => form.setData('Kode', nilai)}
              galat={form.errors.Kode}
              contoh="onboarding-trial"
            />

            <div className="grid gap-2">
              <Label nama="NamaSequence" htmlFor="NamaSequence">
                Nama
              </Label>
              <Input
                id="NamaSequence"
                value={form.data.Nama}
                onChange={(e) => form.setData('Nama', e.target.value)}
                required
              />
              {form.errors.Nama ? <p className="text-sm text-destructive">{form.errors.Nama}</p> : null}
            </div>

            <div className="grid gap-2">
              <Label nama="KeteranganSequence" htmlFor="KeteranganSequence">
                Keterangan
              </Label>
              <Textarea
                id="KeteranganSequence"
                rows={3}
                value={form.data.Keterangan}
                onChange={(e) => form.setData('Keterangan', e.target.value)}
              />
            </div>

            <label className="flex items-center gap-2 text-sm">
              <Checkbox
                checked={form.data.Aktif}
                onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
              />
              Aktif
            </label>

            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function DialogLangkah({
  sequence,
  langkah,
  template,
  wajib,
}: {
  sequence: SequenceEmail;
  langkah: LangkahSequence | null;
  template: PilihanTemplate[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const urutanBerikut = sequence.Langkah.reduce((maks, satu) => Math.max(maks, satu.Urutan + 1), 0);

  const form = useForm({
    TemplateEmailPemasaranId: langkah?.TemplateEmailPemasaranId ?? template[0]?.Id ?? '',
    Urutan: String(langkah?.Urutan ?? urutanBerikut),
    HariKe: String(langkah?.HariKe ?? 0),
    Aktif: langkah?.Aktif ?? true,
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();

    form.transform((data) => ({
      ...data,
      Urutan: Number(data.Urutan),
      HariKe: Number(data.HariKe),
    }));

    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!langkah) form.reset();
      },
    };

    if (langkah) {
      form.put(`${AKAR}/${sequence.Kode}/langkah/${langkah.Id}`, opsi);
    } else {
      form.post(`${AKAR}/${sequence.Kode}/langkah`, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          {langkah ? 'Ubah' : 'Tambah Langkah'}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{langkah ? 'Ubah Langkah' : 'Tambah Langkah'}</DialogTitle>
        </DialogHeader>

        {template.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Belum ada template aktif. Buat template lebih dulu di halaman Template Email.
          </p>
        ) : (
          <AturanWajibProvider aturan={wajib}>
            <form onSubmit={kirim} className="grid gap-4">
              <div className="grid gap-2">
                <Label nama="TemplateLangkah" htmlFor="TemplateLangkah">
                  Template
                </Label>
                <Select
                  value={form.data.TemplateEmailPemasaranId}
                  onValueChange={(v) => form.setData('TemplateEmailPemasaranId', v)}
                >
                  <SelectTrigger id="TemplateLangkah">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {template.map((satu) => (
                      <SelectItem key={satu.Id} value={satu.Id}>
                        {satu.Nama} · {satu.Kode}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {form.errors.TemplateEmailPemasaranId ? (
                  <p className="text-sm text-destructive">{form.errors.TemplateEmailPemasaranId}</p>
                ) : null}
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                  <Label nama="UrutanLangkah" htmlFor="UrutanLangkah">
                    Urutan
                  </Label>
                  <Input
                    id="UrutanLangkah"
                    type="number"
                    min={0}
                    value={form.data.Urutan}
                    onChange={(e) => form.setData('Urutan', e.target.value)}
                    required
                  />
                  {form.errors.Urutan ? (
                    <p className="text-sm text-destructive">{form.errors.Urutan}</p>
                  ) : null}
                </div>

                <div className="grid gap-2">
                  <Label nama="HariLangkah" htmlFor="HariLangkah">
                    Hari ke
                  </Label>
                  <Input
                    id="HariLangkah"
                    type="number"
                    min={0}
                    value={form.data.HariKe}
                    onChange={(e) => form.setData('HariKe', e.target.value)}
                    required
                  />
                  {form.errors.HariKe ? (
                    <p className="text-sm text-destructive">{form.errors.HariKe}</p>
                  ) : null}
                </div>
              </div>

              <p className="text-sm text-muted-foreground">
                Hari ke-0 berangkat saat orang mendaftar; angka lain dihitung dari tanggal itu.
              </p>

              <label className="flex items-center gap-2 text-sm">
                <Checkbox
                  checked={form.data.Aktif}
                  onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
                />
                Aktif
              </label>

              <DialogFooter>
                <Button type="submit" disabled={form.processing}>
                  Simpan
                </Button>
              </DialogFooter>
            </form>
          </AturanWajibProvider>
        )}
      </DialogContent>
    </Dialog>
  );
}
