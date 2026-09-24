import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { Badge } from '@/components/ui/badge';
import { varianAktif, varianStatus } from '@/features/Pemasaran/status';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { ProgramReferral, RewardReferralRingkas } from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

type Pilihan = { Jenis: string[]; JenisDidukung: string[] };

interface Props {
  program: ProgramReferral[];
  /** status referral => jumlah yang berhenti di situ. */
  corong: Record<string, number>;
  reward: RewardReferralRingkas[];
  pilihan: Pilihan;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const AKAR = rutePemasaran.referral;

const waktu = (nilai: string | null) => (nilai ? new Date(nilai).toLocaleString('id-ID') : '—');

const URUTAN_CORONG = ['Dibuat', 'Diklik', 'Lead', 'Trial', 'Paid', 'RewardPending', 'Rewarded'];

export default function PemasaranReferralIndex({ program, corong, reward, pilihan, wajib }: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Referral" />

      <KepalaHalaman
        judul="Referral"
        deskripsi="Program referral, kode tiap pelanggan, dan imbalan yang terutang."
        tanpaBreadcrumb
        aksi={<DialogProgram program={null} pilihan={pilihan} wajib={wajib.program} />}
        className="mb-5"
      />

      <Corong corong={corong} />

      <Tabs defaultValue="program" className="mt-5">
        <TabsList>
          <TabsTrigger value="program">Program</TabsTrigger>
          <TabsTrigger value="reward">Imbalan</TabsTrigger>
        </TabsList>

        <TabsContent value="program" className="mt-4 space-y-4">
          {program.length === 0 ? (
            <p className="text-sm text-muted-foreground">Belum ada program referral.</p>
          ) : (
            program.map((satu) => (
              <KartuProgram key={satu.Id} program={satu} pilihan={pilihan} wajib={wajib} />
            ))
          )}
        </TabsContent>

        <TabsContent value="reward" className="mt-4">
          <DaftarReward reward={reward} />
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}

function Corong({ corong }: { corong: Record<string, number> }) {
  const ditolak = corong.Ditolak ?? 0;
  const kedaluwarsa = corong.Kedaluwarsa ?? 0;

  return (
    <div className="space-y-3">
      <ol className="grid gap-2 sm:grid-cols-4 lg:grid-cols-7">
        {URUTAN_CORONG.map((tahap) => (
          <li key={tahap} className="rounded-md border px-4 py-3">
            <p className="text-[13px] text-grafit-700">{tahap}</p>
            <p className="text-[26px] leading-tight font-semibold tracking-[-0.015em] text-foreground tabular-nums">
              {corong[tahap] ?? 0}
            </p>
          </li>
        ))}
      </ol>

      {ditolak > 0 || kedaluwarsa > 0 ? (
        <p className="text-sm text-muted-foreground">
          {ditolak} ditolak karena mereferensikan diri sendiri, {kedaluwarsa} lewat jendelanya tanpa pernah
          dibayar.
        </p>
      ) : null}
    </div>
  );
}

function KartuProgram({
  program,
  pilihan,
  wajib,
}: {
  program: ProgramReferral;
  pilihan: Pilihan;
  wajib: Record<string, AturanWajib>;
}) {
  return (
    <Card>
      <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
        <div className="min-w-0">
          <CardTitle>{program.Nama}</CardTitle>
          <p className="font-mono text-xs text-muted-foreground">{program.Kode}</p>
          {program.Keterangan ? (
            <p className="mt-1 text-sm text-muted-foreground">{program.Keterangan}</p>
          ) : null}
        </div>
        <div className="flex shrink-0 items-center gap-2">
          <Badge variant={varianAktif(program.Aktif)}>{program.Aktif ? 'Aktif' : 'Nonaktif'}</Badge>
          <DialogProgram program={program} pilihan={pilihan} wajib={wajib.program} />
        </div>
      </CardHeader>

      <CardContent className="space-y-3">
        <p className="text-sm text-muted-foreground">
          Imbalan {program.JenisReward} sebesar {program.NilaiReward} · jendela {program.HariKedaluwarsa} hari
          · {program.JumlahReferral} referral
        </p>

        {program.Kodenya.length === 0 ? (
          <p className="text-sm text-muted-foreground">Belum ada pelanggan yang punya kode.</p>
        ) : (
          <ul className="divide-y rounded-md border">
            {program.Kodenya.map((kode) => (
              <li key={kode.Id} className="flex flex-wrap items-center justify-between gap-2 p-3">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium">{kode.Organisasi}</p>
                  <p className="break-all font-mono text-xs text-muted-foreground">{kode.Url ?? kode.Kode}</p>
                </div>
                {kode.Aktif ? null : <Badge variant="netral">Nonaktif</Badge>}
              </li>
            ))}
          </ul>
        )}

        <div className="flex justify-end">
          <DialogKode program={program} />
        </div>
      </CardContent>
    </Card>
  );
}

function DialogProgram({
  program,
  pilihan,
  wajib,
}: {
  program: ProgramReferral | null;
  pilihan: Pilihan;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);

  const form = useForm({
    Kode: program?.Kode ?? '',
    Nama: program?.Nama ?? '',
    Keterangan: program?.Keterangan ?? '',
    JenisReward: program?.JenisReward ?? pilihan.JenisDidukung[0] ?? '',
    NilaiReward: String(program?.NilaiReward ?? 0),
    HariKedaluwarsa: String(program?.HariKedaluwarsa ?? 90),
    Aktif: program?.Aktif ?? false,
  });

  const didukung = pilihan.JenisDidukung.includes(form.data.JenisReward);

  const kirim = (e: FormEvent) => {
    e.preventDefault();

    form.transform((data) => ({
      ...data,
      NilaiReward: Number(data.NilaiReward),
      HariKedaluwarsa: Number(data.HariKedaluwarsa),
    }));

    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!program) form.reset();
      },
    };

    if (program) {
      form.put(`${AKAR}/${program.Kode}`, opsi);
    } else {
      form.post(AKAR, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={program ? 'outline' : 'default'} size={program ? 'sm' : 'default'}>
          {program ? 'Ubah' : 'Tambah Program'}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{program ? 'Ubah Program Referral' : 'Tambah Program Referral'}</DialogTitle>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={kirim} className="grid gap-4">
            <BidangKode
              nilai={form.data.Kode}
              onUbah={(nilai) => form.setData('Kode', nilai)}
              galat={form.errors.Kode}
              contoh="ajak-teman"
            />

            <div className="grid content-start gap-2">
              <Label nama="NamaProgram" htmlFor="NamaProgram">
                Nama
              </Label>
              <Input
                id="NamaProgram"
                value={form.data.Nama}
                onChange={(e) => form.setData('Nama', e.target.value)}
                required
              />
              {form.errors.Nama ? <p className="text-sm text-destructive">{form.errors.Nama}</p> : null}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="grid content-start gap-2">
                <Label nama="JenisReward" htmlFor="JenisReward">
                  Jenis imbalan
                </Label>
                <Select value={form.data.JenisReward} onValueChange={(v) => form.setData('JenisReward', v)}>
                  <SelectTrigger id="JenisReward">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {pilihan.Jenis.map((jenis) => (
                      <SelectItem key={jenis} value={jenis}>
                        {jenis}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="grid content-start gap-2">
                <Label nama="NilaiReward" htmlFor="NilaiReward">
                  Nilai
                </Label>
                <Input
                  id="NilaiReward"
                  type="number"
                  min={0}
                  value={form.data.NilaiReward}
                  onChange={(e) => form.setData('NilaiReward', e.target.value)}
                  required
                />
              </div>
            </div>

            {didukung ? null : (
              <p className="text-sm text-destructive">
                Billing belum dapat memberikan imbalan berbentuk {form.data.JenisReward}. Yang tersedia:{' '}
                {pilihan.JenisDidukung.join(', ')}.
              </p>
            )}

            <div className="grid content-start gap-2">
              <Label nama="HariKedaluwarsa" htmlFor="HariKedaluwarsa">
                Jendela (hari)
              </Label>
              <Input
                id="HariKedaluwarsa"
                type="number"
                min={1}
                value={form.data.HariKedaluwarsa}
                onChange={(e) => form.setData('HariKedaluwarsa', e.target.value)}
                required
              />
              <p className="text-sm text-muted-foreground">
                Referral yang belum dibayar dalam rentang ini ditutup.
              </p>
            </div>

            <div className="grid content-start gap-2">
              <Label nama="KeteranganProgram" htmlFor="KeteranganProgram">
                Keterangan
              </Label>
              <Textarea
                id="KeteranganProgram"
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
              <Button type="submit" disabled={form.processing || !didukung}>
                Simpan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function DialogKode({ program }: { program: ProgramReferral }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ OrganisasiId: '' });

  const kirim = (e: FormEvent) => {
    e.preventDefault();

    form.post(`${AKAR}/${program.Kode}/kode`, {
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
        <Button variant="outline" size="sm">
          Terbitkan Kode
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Terbitkan Kode Referral</DialogTitle>
        </DialogHeader>

        <form onSubmit={kirim} className="grid gap-4">
          <div className="grid content-start gap-2">
            <Label nama="OrganisasiKode" htmlFor="OrganisasiKode">
              ID organisasi pelanggan
            </Label>
            <Input
              id="OrganisasiKode"
              className="font-mono text-xs"
              value={form.data.OrganisasiId}
              onChange={(e) => form.setData('OrganisasiId', e.target.value)}
              required
            />
            <p className="text-sm text-muted-foreground">
              Satu pelanggan satu kode per program; menerbitkan ulang mengembalikan kode yang sama.
            </p>
            {form.errors.OrganisasiId ? (
              <p className="text-sm text-destructive">{form.errors.OrganisasiId}</p>
            ) : null}
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Terbitkan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DaftarReward({ reward }: { reward: RewardReferralRingkas[] }) {
  const konfirmasi = useKonfirmasi();

  const batalkan = async (satu: RewardReferralRingkas) => {
    const setuju = await konfirmasi({
      judul: 'Batalkan imbalan?',
      deskripsi: `Imbalan untuk ${satu.Penerima} tidak akan diberikan.`,
      ragam: 'bahaya',
    });

    if (setuju) {
      router.post(
        `${AKAR}/reward/${satu.Id}/batalkan`,
        { Alasan: 'Dibatalkan dari konsol.' },
        { preserveScroll: true },
      );
    }
  };

  if (reward.length === 0) {
    return <p className="text-sm text-muted-foreground">Belum ada imbalan referral.</p>;
  }

  return (
    <div className="space-y-2">
      {reward.map((satu) => (
        <Card key={satu.Id}>
          <CardContent className="space-y-2 p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div className="min-w-0">
                <p className="truncate text-sm font-medium">{satu.Penerima}</p>
                <p className="text-xs text-muted-foreground">
                  {satu.Jenis} {satu.Nilai} · terbit {waktu(satu.DibuatPada)}
                  {satu.Percobaan > 0 ? ` · percobaan ${satu.Percobaan}` : ''}
                </p>
              </div>
              <div className="flex shrink-0 items-center gap-2">
                <Badge variant={varianStatus(satu.Status)}>{satu.Status}</Badge>
                {satu.Status === 'Tertunda' || satu.Status === 'Gagal' ? (
                  <>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() =>
                        router.post(`${AKAR}/reward/${satu.Id}/proses`, {}, { preserveScroll: true })
                      }
                    >
                      Proses
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => batalkan(satu)}>
                      Batalkan
                    </Button>
                  </>
                ) : null}
              </div>
            </div>

            {satu.Galat ? <p className="text-xs text-destructive">{satu.Galat}</p> : null}
            {satu.Ringkasan ? <p className="text-xs text-muted-foreground">{satu.Ringkasan}</p> : null}
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
