import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type {
  AsetRingkas,
  JenisPerintahKerja,
  KeluhanRingkas,
  LokasiRingkas,
  PrioritasPerintahKerja,
} from '@/features/PerintahKerja/types';
import { DAFTAR_JENIS, DAFTAR_PRIORITAS } from '@/features/PerintahKerja/status';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { TANPA_PILIHAN, opsiDari, opsiKosong, opsiUnitPengelola } from '@/lib/pilihan';
import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { dariMasukanWaktu } from '@/lib/waktu';

export function DialogBuatPerintahKerja({
  keluhan,
  aset,
  lokasi,
  wajib,
  pilihanUnitPengelola = [],
  unitPengelolaDipakai = false,
}: {
  keluhan: KeluhanRingkas[];
  aset: AsetRingkas[];
  lokasi: LokasiRingkas[];
  wajib: AturanWajib;
  /** Unit bertanda Mengelola Aset yang masih aktif (PRD 8.21). */
  pilihanUnitPengelola?: UnitPengelolaRingkas[];
  /** Isian unit pengelola hanya tampil bila organisasi memakai fitur ini. */
  unitPengelolaDipakai?: boolean;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    KeluhanId: TANPA_PILIHAN,
    Jenis: 'Korektif' as JenisPerintahKerja,
    Judul: '',
    Deskripsi: '',
    Prioritas: 'Normal' as PrioritasPerintahKerja,
    LokasiId: '',
    AsetIds: [] as string[],
    MembutuhkanWaktuHenti: false,
    MembutuhkanPersetujuan: false,
    DijadwalkanMulaiPada: '',
    DijadwalkanSelesaiPada: '',
    UnitPengelolaId: TANPA_PILIHAN,
  });

  /**
   * Unit pengelola yang akan diturunkan server bila isian dibiarkan otomatis:
   * keluhan asal lebih dulu, lalu aset utama. Hanya pratinjau; penentunya
   * tetap BuatPerintahKerja di server.
   */
  const keluhanDipilih = keluhan.find((k) => k.Id === form.data.KeluhanId);
  const asetUtamaId = keluhanDipilih?.AsetId ?? form.data.AsetIds[0];
  const unitTurunanId =
    keluhanDipilih?.UnitPengelolaId ?? aset.find((a) => a.Id === asetUtamaId)?.UnitPengelolaId ?? null;
  const unitTurunan = pilihanUnitPengelola.find((u) => u.Id === unitTurunanId);

  const tanganiPilihKeluhan = (keluhanId: string) => {
    if (keluhanId === TANPA_PILIHAN) {
      form.setData({
        ...form.data,
        KeluhanId: TANPA_PILIHAN,
      });
      return;
    }

    const dipilih = keluhan.find((k) => k.Id === keluhanId);
    if (!dipilih) return;

    form.setData({
      ...form.data,
      KeluhanId: keluhanId,
      Judul: form.data.Judul || `Tindak Lanjut: ${dipilih.Judul}`,
      Prioritas: dipilih.Prioritas,
      LokasiId: dipilih.LokasiId ?? form.data.LokasiId,
      AsetIds: dipilih.AsetId ? [dipilih.AsetId] : form.data.AsetIds,
    });
  };

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      KeluhanId: data.KeluhanId === TANPA_PILIHAN ? null : data.KeluhanId,
      LokasiId: data.LokasiId ? data.LokasiId : null,
      UnitPengelolaId: data.UnitPengelolaId === TANPA_PILIHAN ? null : data.UnitPengelolaId,
      DijadwalkanMulaiPada: dariMasukanWaktu(data.DijadwalkanMulaiPada),
      DijadwalkanSelesaiPada: dariMasukanWaktu(data.DijadwalkanSelesaiPada),
    }));
    form.post(rutePerintahKerja.index, {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  const toggleAset = (idAset: string) => {
    const ada = form.data.AsetIds.includes(idAset);
    if (ada) {
      form.setData(
        'AsetIds',
        form.data.AsetIds.filter((id) => id !== idAset),
      );
    } else {
      form.setData('AsetIds', [...form.data.AsetIds, idAset]);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="cursor-pointer">Buat Perintah Kerja</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Buat Perintah Kerja Baru</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="KeluhanId">Terkait Keluhan (Opsional)</Label>
                <Combobox
                  nilai={form.data.KeluhanId}
                  onPilih={tanganiPilihKeluhan}
                  opsi={[
                    opsiKosong('Tanpa keluhan (Pekerjaan Mandiri)'),
                    ...opsiDari(keluhan, (k) => `${k.Nomor} · ${k.Judul}`),
                  ]}
                  placeholder="Pilih keluhan"
                  className="cursor-pointer"
                />
                {form.errors.KeluhanId && <p className="text-sm text-destructive">{form.errors.KeluhanId}</p>}
              </div>

              <div className="space-y-1.5">
                <Label nama="Jenis">Jenis Pekerjaan</Label>
                <Select
                  value={form.data.Jenis}
                  onValueChange={(val) => form.setData('Jenis', val as JenisPerintahKerja)}
                >
                  <SelectTrigger className="w-full cursor-pointer">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {DAFTAR_JENIS.map((jenis) => (
                      <SelectItem key={jenis} value={jenis} className="cursor-pointer">
                        {jenis}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {form.errors.Jenis && <p className="text-sm text-destructive">{form.errors.Jenis}</p>}
              </div>
            </div>

            <div className="space-y-1.5">
              <Label nama="Judul">Judul Pekerjaan</Label>
              <Input
                value={form.data.Judul}
                onChange={(e) => form.setData('Judul', e.target.value)}
                placeholder="Contoh: Perbaikan Pompa Utama Chiller 2"
              />
              {form.errors.Judul && <p className="text-sm text-destructive">{form.errors.Judul}</p>}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="Prioritas">Prioritas</Label>
                <Select
                  value={form.data.Prioritas}
                  onValueChange={(val) => form.setData('Prioritas', val as PrioritasPerintahKerja)}
                >
                  <SelectTrigger className="w-full cursor-pointer">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {DAFTAR_PRIORITAS.map((p) => (
                      <SelectItem key={p} value={p} className="cursor-pointer">
                        {p}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {form.errors.Prioritas && <p className="text-sm text-destructive">{form.errors.Prioritas}</p>}
              </div>

              <div className="space-y-1.5">
                <Label nama="LokasiId">Lokasi</Label>
                <Combobox
                  nilai={form.data.LokasiId}
                  onPilih={(val) => form.setData('LokasiId', val)}
                  opsi={opsiDari(lokasi, (l) => l.Nama)}
                  placeholder="Pilih lokasi kerja"
                  className="cursor-pointer"
                />
                {form.errors.LokasiId && <p className="text-sm text-destructive">{form.errors.LokasiId}</p>}
              </div>
            </div>

            {unitPengelolaDipakai && (
              <div className="space-y-1.5">
                <Label nama="UnitPengelolaId">Unit Pengelola</Label>
                <Combobox
                  nilai={form.data.UnitPengelolaId}
                  onPilih={(val) => form.setData('UnitPengelolaId', val)}
                  opsi={opsiUnitPengelola(pilihanUnitPengelola, 'Otomatis dari keluhan atau aset')}
                  placeholder="Pilih unit pengelola"
                  className="cursor-pointer"
                />
                {form.data.UnitPengelolaId === TANPA_PILIHAN && (
                  <p className="text-xs text-muted-foreground">
                    {unitTurunan
                      ? `Akan masuk antrian ${unitTurunan.Nama}.`
                      : unitTurunanId
                        ? 'Unit pengelola diturunkan dari keluhan atau aset yang dipilih.'
                        : 'Keluhan dan aset yang dipilih belum punya unit pengelola; perintah kerja tercatat tanpa unit pengelola.'}
                  </p>
                )}
                {form.errors.UnitPengelolaId && (
                  <p className="text-sm text-destructive">{form.errors.UnitPengelolaId}</p>
                )}
              </div>
            )}

            <div className="space-y-1.5">
              <Label nama="AsetIds">Aset yang Ditangani (Pilih satu atau lebih)</Label>
              <div className="max-h-36 overflow-y-auto rounded-md border border-input p-2 space-y-1">
                {aset.map((a) => {
                  const dipilih = form.data.AsetIds.includes(a.Id);
                  return (
                    <label
                      key={a.Id}
                      className="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-accent cursor-pointer"
                    >
                      <input
                        type="checkbox"
                        checked={dipilih}
                        onChange={() => toggleAset(a.Id)}
                        className="cursor-pointer rounded border-input text-teknisi-700 focus:ring-teknisi-600"
                      />
                      <span className="font-mono text-xs text-muted-foreground">{a.KodeAset}</span>
                      <span>{a.Nama}</span>
                    </label>
                  );
                })}
              </div>
              {form.errors.AsetIds && <p className="text-sm text-destructive">{form.errors.AsetIds}</p>}
            </div>

            <div className="space-y-1.5">
              <Label nama="Deskripsi">Deskripsi / Instruksi Pekerjaan</Label>
              <Textarea
                rows={3}
                value={form.data.Deskripsi}
                onChange={(e) => form.setData('Deskripsi', e.target.value)}
                placeholder="Jelaskan detail perbaikan, gejala, atau langkah awal yang diharapkan..."
              />
              {form.errors.Deskripsi && <p className="text-sm text-destructive">{form.errors.Deskripsi}</p>}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="DijadwalkanMulaiPada">Jadwal Mulai</Label>
                <Input
                  type="datetime-local"
                  value={form.data.DijadwalkanMulaiPada}
                  onChange={(e) => form.setData('DijadwalkanMulaiPada', e.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="DijadwalkanSelesaiPada">Jadwal Selesai</Label>
                <Input
                  type="datetime-local"
                  value={form.data.DijadwalkanSelesaiPada}
                  onChange={(e) => form.setData('DijadwalkanSelesaiPada', e.target.value)}
                />
              </div>
            </div>

            <div className="flex flex-wrap gap-6 pt-2">
              <label className="flex items-center gap-2 cursor-pointer text-sm font-medium">
                <input
                  type="checkbox"
                  checked={form.data.MembutuhkanWaktuHenti}
                  onChange={(e) => form.setData('MembutuhkanWaktuHenti', e.target.checked)}
                  className="cursor-pointer rounded border-input text-teknisi-700 focus:ring-teknisi-600"
                />
                Membutuhkan Downtime Mesin / Aset
              </label>
              <label className="flex items-center gap-2 cursor-pointer text-sm font-medium">
                <input
                  type="checkbox"
                  checked={form.data.MembutuhkanPersetujuan}
                  onChange={(e) => form.setData('MembutuhkanPersetujuan', e.target.checked)}
                  className="cursor-pointer rounded border-input text-teknisi-700 focus:ring-teknisi-600"
                />
                Perlu Verifikasi / Persetujuan Hasil
              </label>
            </div>

            <DialogFooter>
              <Button type="submit" disabled={form.processing} className="cursor-pointer">
                Simpan Perintah Kerja
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
