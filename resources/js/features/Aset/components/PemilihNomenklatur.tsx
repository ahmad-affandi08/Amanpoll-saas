import { useCallback, useRef, useState } from 'react';
import { Combobox } from '@/components/ui/combobox';
import { http } from '@/lib/http';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { ruteAspak } from '@/features/Aspak/api';

interface AlkesRingkas {
  Id: string;
  Kode: string;
  Nama: string;
  Kelompok: string | null;
}

interface Props {
  nilai: string;
  onPilih: (nilai: string) => void;
  /** Nomenklatur yang sudah tersimpan, supaya labelnya tampil sebelum daftar dimuat. */
  terpasang?: { Id: string; Kode: string | null; Nama: string | null } | null;
}

/**
 * Pemilih nomenklatur alkes standar Kemenkes.
 *
 * Katalognya dicari di server, bukan dikirim penuh bersama halaman: satu
 * katalog ASPAK berisi ribuan alkes dan memuat semuanya di tiap halaman aset
 * membuat halamannya berat tanpa alasan.
 */
export function PemilihNomenklatur({ nilai, onPilih, terpasang = null }: Props) {
  const [hasil, setHasil] = useState<AlkesRingkas[]>([]);
  const [memuat, setMemuat] = useState(false);
  const penunda = useRef<ReturnType<typeof setTimeout> | null>(null);

  const cari = useCallback((kueri: string) => {
    if (penunda.current) clearTimeout(penunda.current);

    // Ditunda supaya tiap ketukan tombol tidak menjadi satu permintaan.
    penunda.current = setTimeout(() => {
      setMemuat(true);
      http
        .get<AlkesRingkas[]>(`${ruteAspak.cariKatalog}?q=${encodeURIComponent(kueri)}`)
        .then((res) => setHasil(Array.isArray(res.data) ? res.data : []))
        .catch(() => setHasil([]))
        .finally(() => setMemuat(false));
    }, 250);
  }, []);

  const opsi = [
    { nilai: TANPA_PILIHAN, label: 'Belum ditentukan' },
    // Yang tersimpan ikut dimasukkan agar labelnya tetap terbaca walau tidak
    // termasuk 50 hasil pencarian yang sedang tampil.
    ...(terpasang && !hasil.some((h) => h.Id === terpasang.Id)
      ? [{ nilai: terpasang.Id, label: `${terpasang.Kode ?? ''} — ${terpasang.Nama ?? ''}` }]
      : []),
    ...hasil.map((h) => ({
      nilai: h.Id,
      label: `${h.Kode} — ${h.Nama}`,
      keterangan: h.Kelompok ?? undefined,
    })),
  ];

  return (
    <Combobox
      nilai={nilai}
      onPilih={onPilih}
      opsi={opsi}
      onCari={cari}
      memuat={memuat}
      placeholder="Belum ditentukan"
      placeholderCari="Cari nama atau kode alkes…"
      pesanKosong="Tidak ada alkes yang cocok. Impor katalog ASPAK lebih dulu bila masih kosong."
    />
  );
}
