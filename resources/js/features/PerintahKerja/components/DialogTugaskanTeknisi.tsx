import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
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
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { PerintahKerja, TeknisiOpsi } from '@/features/PerintahKerja/types';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

export function DialogTugaskanTeknisi({
  perintahKerja,
  teknisi,
  wajib,
}: {
  perintahKerja: PerintahKerja;
  teknisi: TeknisiOpsi[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    PenggunaIds: [] as string[],
    PeranTugas: 'Anggota',
    GantiPenugasanAktif: false,
  });

  const toggleTeknisi = (id: string) => {
    if (form.data.PenggunaIds.includes(id)) {
      form.setData(
        'PenggunaIds',
        form.data.PenggunaIds.filter((item) => item !== id),
      );
    } else {
      form.setData('PenggunaIds', [...form.data.PenggunaIds, id]);
    }
  };

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post(rutePerintahKerja.penugasan(perintahKerja.Id), {
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
        <Button size="sm" variant="outline" className="cursor-pointer">
          Tugaskan Teknisi
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Tugaskan Teknisi</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="PeranTugas">Peran Tugas</Label>
              <Select value={form.data.PeranTugas} onValueChange={(val) => form.setData('PeranTugas', val)}>
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Ketua" className="cursor-pointer">
                    Ketua Tim
                  </SelectItem>
                  <SelectItem value="Anggota" className="cursor-pointer">
                    Anggota Teknisi
                  </SelectItem>
                  <SelectItem value="Spesialis" className="cursor-pointer">
                    Spesialis / Vendor
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5">
              <Label nama="PenggunaIds">Pilih Teknisi</Label>
              <div className="max-h-48 overflow-y-auto rounded-md border border-input p-2 space-y-1">
                {teknisi.map((t) => {
                  const dipilih = form.data.PenggunaIds.includes(t.Id);
                  return (
                    <label
                      key={t.Id}
                      className="flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-muted cursor-pointer"
                    >
                      <div className="flex items-center gap-2">
                        <input
                          type="checkbox"
                          checked={dipilih}
                          onChange={() => toggleTeknisi(t.Id)}
                          className="cursor-pointer rounded border-gray-300 text-teknisi-700 focus:ring-teknisi-600"
                        />
                        <span className="font-medium">{t.Nama}</span>
                        {t.Jabatan && <span className="text-xs text-muted-foreground">({t.Jabatan})</span>}
                      </div>
                      <Badge variant={t.BebanAktif > 2 ? 'perhatian' : 'netral'}>
                        {t.BebanAktif} tugas aktif
                      </Badge>
                    </label>
                  );
                })}
              </div>
              {form.errors.PenggunaIds && (
                <p className="text-sm text-destructive">{form.errors.PenggunaIds}</p>
              )}
            </div>

            <label className="flex items-center gap-2 text-sm cursor-pointer pt-1">
              <input
                type="checkbox"
                checked={form.data.GantiPenugasanAktif}
                onChange={(e) => form.setData('GantiPenugasanAktif', e.target.checked)}
                className="cursor-pointer rounded border-gray-300 text-teknisi-700 focus:ring-teknisi-600"
              />
              Gantikan penugasan aktif sebelumnya
            </label>

            <DialogFooter>
              <Button
                type="submit"
                disabled={form.processing || form.data.PenggunaIds.length === 0}
                className="cursor-pointer"
              >
                Simpan Penugasan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
