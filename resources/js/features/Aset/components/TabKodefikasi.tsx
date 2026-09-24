import { useCallback, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Label } from '@/components/ui/label';
import { http } from '@/lib/http';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { useIzin } from '@/hooks/use-izin';
import { ruteKodefikasi } from '@/features/Kodefikasi/api';
import type { PenetapanKodeAset } from '@/features/Kodefikasi/types';
import type { Aset } from '@/features/Aset/types';

interface KodeRingkas {
  Id: string;
  Kode: string;
  Uraian: string;
}

const STANDAR = [
  { nilai: 'SimakBmn', label: 'SIMAK BMN (PMK 29/2010)' },
  { nilai: 'Simbada', label: 'SIMBADA (Permendagri 108/2016)' },
];

export function TabKodefikasi({ aset }: { aset: Aset }) {
  const { boleh } = useIzin();
  const bolehKelola = boleh('Aset.Ubah');

  const [penetapan, setPenetapan] = useState<PenetapanKodeAset[]>([]);
  const [standar, setStandar] = useState(STANDAR[0].nilai);
  const [kodeTerpilih, setKodeTerpilih] = useState(TANPA_PILIHAN);
  const [hasil, setHasil] = useState<KodeRingkas[]>([]);
  const [memuat, setMemuat] = useState(false);
  const penunda = useRef<ReturnType<typeof setTimeout> | null>(null);

  const muat = useCallback(() => {
    http
      .get<PenetapanKodeAset[]>(ruteKodefikasi.untukAset(aset.Id))
      .then((res) => setPenetapan(Array.isArray(res.data) ? res.data : []));
  }, [aset.Id]);

  useEffect(muat, [muat]);

  const cari = useCallback(
    (kueri: string) => {
      if (penunda.current) clearTimeout(penunda.current);

      penunda.current = setTimeout(() => {
        setMemuat(true);
        http
          .get<KodeRingkas[]>(
            `${ruteKodefikasi.cariKatalog}?standar=${standar}&q=${encodeURIComponent(kueri)}`,
          )
          .then((res) => setHasil(Array.isArray(res.data) ? res.data : []))
          .catch(() => setHasil([]))
          .finally(() => setMemuat(false));
      }, 250);
    },
    [standar],
  );

  const tetapkan = () => {
    if (kodeTerpilih === TANPA_PILIHAN) return;

    router.post(
      ruteKodefikasi.penetapan,
      { AsetId: aset.Id, KodeBarangId: kodeTerpilih },
      {
        preserveScroll: true,
        onSuccess: () => {
          setKodeTerpilih(TANPA_PILIHAN);
          muat();
        },
      },
    );
  };

  return (
    <div className="space-y-6">
      {penetapan.length === 0 ? (
        <p className="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
          Aset ini belum punya kode barang. Tanpa kode, ia tidak dapat dilaporkan ke SIMAK BMN maupun SIMBADA.
        </p>
      ) : (
        <div className="divide-y divide-border rounded-md border border-border">
          {penetapan.map((satu) => (
            <div key={satu.Id} className="space-y-1 p-3">
              <div className="flex flex-wrap items-center gap-2">
                <Badge variant="outline">{satu.LabelStandar}</Badge>
                <span className="font-mono text-sm">{satu.Kode}</span>
                <span className="text-sm text-muted-foreground">{satu.Uraian}</span>
              </div>
              <div className="text-sm">
                <span className="text-muted-foreground">NUP </span>
                <span className="font-mono">{String(satu.Nup).padStart(6, '0')}</span>
              </div>
              {satu.KodeRegistrasi ? (
                <div className="text-sm">
                  <span className="text-muted-foreground">Kode registrasi </span>
                  <span className="font-mono">{satu.KodeRegistrasi}</span>
                </div>
              ) : (
                satu.AlasanBelumLengkap && (
                  <p className="text-sm text-destructive">{satu.AlasanBelumLengkap}</p>
                )
              )}
            </div>
          ))}
        </div>
      )}

      {bolehKelola && (
        <div className="space-y-3 rounded-md border border-border p-5">
          <h3 className="text-sm font-semibold text-foreground">Tetapkan kode barang</h3>
          <div className="space-y-2">
            <Label nama="standar">Standar</Label>
            <Combobox
              nilai={standar}
              onPilih={(v) => {
                setStandar(v);
                setKodeTerpilih(TANPA_PILIHAN);
                setHasil([]);
              }}
              opsi={STANDAR}
            />
          </div>
          <div className="space-y-2">
            <Label nama="KodeBarangId">Kode barang</Label>
            <Combobox
              nilai={kodeTerpilih}
              onPilih={setKodeTerpilih}
              opsi={hasil.map((h) => ({ nilai: h.Id, label: `${h.Kode} — ${h.Uraian}` }))}
              onCari={cari}
              memuat={memuat}
              placeholder="Belum dipilih"
              placeholderCari="Cari kode atau uraian…"
              pesanKosong="Tidak ada kode yang cocok. Impor katalog standar ini lebih dulu bila masih kosong."
            />
          </div>
          <p className="text-sm text-muted-foreground">
            NUP diterbitkan otomatis, berurut per kode barang. Mengganti ke kode lain menerbitkan NUP baru
            karena nomor lama tidak berlaku di bawah kode yang berbeda.
          </p>
          <Button onClick={tetapkan} disabled={kodeTerpilih === TANPA_PILIHAN}>
            Tetapkan
          </Button>
        </div>
      )}
    </div>
  );
}
