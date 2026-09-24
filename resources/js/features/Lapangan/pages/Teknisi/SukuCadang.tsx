import { Link } from '@inertiajs/react';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { WadahIkon3D } from '@/components/shared/Ikon3D';
import { Kartu } from '@/features/Lapangan/components/Kartu';
import type { PropsSukuCadangTeknisi, WarnaChip } from '@/features/Lapangan/types';
import { tanggalPendek } from '@/features/Lapangan/waktu';
import { usePeringatanOffline } from '@/features/Lapangan/components/teknisi/umum';

const STATUS: Record<string, { teks: string; warna: WarnaChip }> = {
  Aktif: { teks: 'Disiapkan gudang', warna: 'kuning' },
  Dipakai: { teks: 'Diserahkan', warna: 'hijau' },
  Dilepas: { teks: 'Dibatalkan', warna: 'abu' },
  Kadaluarsa: { teks: 'Kedaluwarsa', warna: 'abu' },
};

/** Suku cadang yang diminta teknisi untuk tiket-tiketnya; hanya permintaan, tidak mengubah stok. */
export default function SukuCadangTeknisi(props: PropsSukuCadangTeknisi) {
  return (
    <KerangkaLapangan
      varian="appbar"
      judulHalaman="Suku cadang"
      judul="Suku cadang"
      subjudul="Permintaan untuk tiketmu"
      kembali={ruteLapangan.teknisi.beranda}
    >
      <IsiSukuCadang {...props} />
    </KerangkaLapangan>
  );
}

function IsiSukuCadang({ permintaan }: PropsSukuCadangTeknisi) {
  usePeringatanOffline();

  return (
    <>
      <PitaInfo
        nada="biru"
        ikon="package"
        judul="Minta dari layar kerja tiket"
        teks="Petugas gudang menyiapkan barangnya. Stok baru berkurang setelah barang diserahkan."
      />
      {permintaan.length === 0 ? (
        <Kartu>
          <IlustrasiMomen
            ringkas
            ikon="nut_and_bolt"
            judul="Belum ada permintaan"
            teks="Suku cadang yang kamu minta saat mengerjakan tiket akan tampil di sini beserta statusnya."
          />
        </Kartu>
      ) : (
        <Kartu className="overflow-hidden">
          <ul>
            {permintaan.map((satu) => {
              const status = STATUS[satu.Status] ?? { teks: satu.Status, warna: 'abu' as WarnaChip };
              return (
                <li
                  key={satu.Id}
                  className="flex items-start gap-3 px-4 py-3.5 [&+&]:border-t-[1.5px] [&+&]:border-lapangan-garis-2"
                >
                  <WadahIkon3D nama="nut_and_bolt" tint="ungu" ukuran="kecil" />
                  <div className="min-w-0 flex-1">
                    <b className="block text-[15px] leading-snug font-bold">{satu.NamaSukuCadang}</b>
                    <span className="block text-[13px] text-lapangan-teks-3">
                      {satu.Jumlah} {satu.Satuan ?? ''} · {satu.NamaGudang} · {tanggalPendek(satu.DibuatPada)}
                    </span>
                    {satu.PerintahKerjaId && (
                      <Link
                        href={ruteLapangan.teknisi.tugasDetail(satu.PerintahKerjaId)}
                        className="-my-1 inline-flex min-h-8 items-center text-[13px] font-bold text-lapangan-biru-600"
                      >
                        {satu.NomorTiket} · {satu.JudulTiket}
                      </Link>
                    )}
                  </div>
                  <ChipStatus warna={status.warna} ukuran="kecil">
                    {status.teks}
                  </ChipStatus>
                </li>
              );
            })}
          </ul>
        </Kartu>
      )}
    </>
  );
}
