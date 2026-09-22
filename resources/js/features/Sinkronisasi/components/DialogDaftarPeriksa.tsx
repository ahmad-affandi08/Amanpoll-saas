import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { CheckCircle2, ClipboardList } from 'lucide-react';
import type { FungsiAntrikan } from '@/hooks/use-sinkronisasi-offline';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DaftarPeriksaOffline, JawabanDaftarPeriksaOffline } from '@/features/Sinkronisasi/types';

function nilaiJawaban(jawaban: JawabanDaftarPeriksaOffline | undefined): string {
  if (!jawaban) return '';
  if (jawaban.NilaiTeks != null) return jawaban.NilaiTeks;
  if (jawaban.NilaiAngka != null) return String(jawaban.NilaiAngka);
  if (jawaban.NilaiBoolean != null) return jawaban.NilaiBoolean ? 'Ya' : 'Tidak';
  return '';
}

export function DialogDaftarPeriksa({
  daftarPeriksa,
  onTutup,
  onAntrikan,
}: {
  daftarPeriksa: DaftarPeriksaOffline | null;
  onTutup: () => void;
  onAntrikan: FungsiAntrikan;
}) {
  const [isian, setIsian] = useState<Record<string, string>>({});

  useEffect(() => {
    if (!daftarPeriksa) return;
    const awal: Record<string, string> = {};
    for (const butir of daftarPeriksa.Butir) {
      awal[butir.Id] = nilaiJawaban(
        daftarPeriksa.Jawaban.find((j) => j.ButirTemplatDaftarPeriksaId === butir.Id),
      );
    }
    setIsian(awal);
  }, [daftarPeriksa?.Id]);

  if (!daftarPeriksa) return null;

  const jawabanTerisi = () =>
    daftarPeriksa.Butir.filter((butir) => (isian[butir.Id] ?? '').trim() !== '').map((butir) => {
      const nilai = (isian[butir.Id] ?? '').trim();

      if (butir.TipeJawaban === 'Angka') {
        return { ButirTemplatDaftarPeriksaId: butir.Id, NilaiAngka: Number(nilai) };
      }
      if (butir.TipeJawaban === 'YaTidak') {
        return {
          ButirTemplatDaftarPeriksaId: butir.Id,
          NilaiBoolean: ['ya', 'true', '1'].includes(nilai.toLowerCase()),
        };
      }

      return { ButirTemplatDaftarPeriksaId: butir.Id, NilaiTeks: nilai };
    });

  return (
    <Dialog open onOpenChange={(terbuka) => !terbuka && onTutup()}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{daftarPeriksa.NamaTemplat ?? 'Daftar periksa'}</DialogTitle>
          <DialogDescription>
            Jawaban disimpan di perangkat dan diterapkan server setelah antrean terkirim.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          {daftarPeriksa.Butir.map((butir) => (
            <div key={butir.Id} className="space-y-1.5">
              <Label htmlFor={`butir-${butir.Id}`}>
                {butir.Urutan}. {butir.Pertanyaan}
                {butir.Wajib && <span className="text-bahaya-600"> *</span>}
                {butir.Satuan && <span className="text-muted-foreground"> ({butir.Satuan})</span>}
              </Label>
              <Input
                id={`butir-${butir.Id}`}
                type={butir.TipeJawaban === 'Angka' ? 'number' : 'text'}
                value={isian[butir.Id] ?? ''}
                onChange={(e) => setIsian((lama) => ({ ...lama, [butir.Id]: e.target.value }))}
                placeholder={butir.TipeJawaban === 'YaTidak' ? 'Ya / Tidak' : 'Jawaban'}
              />
            </div>
          ))}
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            onClick={() =>
              void onAntrikan({
                Operasi: 'DaftarPeriksa.SimpanJawaban',
                EntitasId: daftarPeriksa.Id,
                VersiKlien: null,
                MuatanData: { Jawaban: jawabanTerisi() },
                Label: `${daftarPeriksa.NamaTemplat ?? 'Daftar periksa'}: simpan jawaban`,
              }).then(() => {
                toast.success('Jawaban daftar periksa masuk antrean.');
                onTutup();
              })
            }
            disabled={jawabanTerisi().length === 0}
          >
            <ClipboardList className="size-4" />
            Antrikan jawaban
          </Button>
          <Button
            onClick={() =>
              void onAntrikan({
                Operasi: 'DaftarPeriksa.Finalisasi',
                EntitasId: daftarPeriksa.Id,
                VersiKlien: null,
                MuatanData: { Catatan: null },
                Label: `${daftarPeriksa.NamaTemplat ?? 'Daftar periksa'}: finalisasi`,
              }).then(() => {
                toast.success('Finalisasi daftar periksa masuk antrean.');
                onTutup();
              })
            }
          >
            <CheckCircle2 className="size-4" />
            Antrikan finalisasi
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
