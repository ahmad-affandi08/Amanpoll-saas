import { router } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { useEffect, useMemo } from 'react';
import { ambilPaket } from '@/lib/penyimpanan-offline';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { Banner, PitaInfo } from '@/features/Lapangan/components/Banner';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { Ikon3D } from '@/features/Lapangan/components/Ikon3D';
import { JudulBagian, Kartu, KartuApung } from '@/features/Lapangan/components/Kartu';
import { MenuGrid3D } from '@/features/Lapangan/components/MenuGrid3D';
import { PerhentianJadwal, type HalteJadwal } from '@/features/Lapangan/components/Perhentian';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { ikonKategori } from '@/features/Lapangan/ikon';
import type { PropsBerandaTeknisi, TiketTeknisi } from '@/features/Lapangan/types';
import { jamPendek, tanggalPendek } from '@/features/Lapangan/waktu';
import { useAksiTiket } from '@/features/Lapangan/components/teknisi/aksiTiket';
import { KartuTiketTeknisi } from '@/features/Lapangan/components/teknisi/KartuTiketTeknisi';
import { useFotoTertunda, useKonteksOffline } from '@/features/Lapangan/components/teknisi/sesiKerja';
import { STATUS_SEDANG_DIKERJAKAN, tiketLokal } from '@/features/Lapangan/components/teknisi/statusLokal';
import {
  KUNCI_SIAPKAN_DILEWATI,
  usePeringatanOffline,
  useKirimAntreanSaatBuka,
} from '@/features/Lapangan/components/teknisi/umum';
import {
  STATUS_SELESAI_TEKNISI,
  hariIni,
  labelHalte,
  terlambat,
} from '@/features/Lapangan/components/teknisi/waktuTiket';

/**
 * Beranda Teknisi (DESIGN §36.6 layar 03, dan layar 16 saat offline): jadwal hari ini
 * sebagai perhentian, menu ikon 3D, tiket yang dikerjakan sekarang, dan agenda.
 */
export default function BerandaTeknisi(props: PropsBerandaTeknisi) {
  return (
    <KerangkaLapangan
      judulHalaman="Beranda"
      navAktif="beranda"
      chip={
        props.lokasiSaya ? (
          <ChipStatus warna="putih" ikon={MapPin}>
            {props.lokasiSaya}
          </ChipStatus>
        ) : undefined
      }
    >
      <IsiBeranda {...props} />
    </KerangkaLapangan>
  );
}

function siapkanDilewati(): boolean {
  try {
    return window.sessionStorage.getItem(KUNCI_SIAPKAN_DILEWATI) === '1';
  } catch {
    return false;
  }
}

/** Jam acuan tiket di jadwal: jadwal mulai, lalu batas, lalu waktu lapor. */
function jamAcuan(tiket: TiketTeknisi): string | null {
  return tiket.DijadwalkanMulaiPada ?? tiket.BatasPada ?? tiket.DilaporkanPada;
}

function IsiBeranda({ tiket, selesai, inspeksi }: PropsBerandaTeknisi) {
  usePeringatanOffline();
  useKirimAntreanSaatBuka();
  const { antrian, jumlahBelumTersinkron, jumlahKonflik, daring } = useSinkronisasiOffline();
  const konteks = useKonteksOffline();
  const { mulai, memproses } = useAksiTiket();
  const fotoTertunda = useFotoTertunda(null);

  // Pertama kali di perangkat ini: siapkan data offline lebih dulu (layar 02).
  useEffect(() => {
    if (!konteks || !navigator.onLine || siapkanDilewati()) return;
    ambilPaket(konteks)
      .then((paket) => {
        if (!paket) router.visit(ruteLapangan.teknisi.siapkan, { replace: true });
      })
      .catch(() => undefined);
  }, [konteks]);

  // Foto yang diambil tanpa sinyal diunggah begitu kembali online.
  useEffect(() => {
    if (daring && fotoTertunda.foto.length > 0) void fotoTertunda.unggahSemua();
  }, [daring, fotoTertunda.foto.length]);

  const sekarang = new Date();
  const lokal = useMemo(() => tiket.map((satu) => tiketLokal(satu, antrian)), [tiket, antrian]);
  const aktif = lokal.filter((satu) => !STATUS_SELESAI_TEKNISI.includes(satu.Status));
  const selesaiLokal = lokal.filter((satu) => STATUS_SELESAI_TEKNISI.includes(satu.Status));
  const selesaiHariIni = [
    ...selesaiLokal,
    ...selesai.filter((satu) => hariIni(satu.DiperbaruiPada, sekarang)),
  ];
  const jumlahTerlambat = aktif.filter((satu) => terlambat(satu, sekarang)).length;

  // Jadwal hari ini: yang dijadwalkan/jatuh tempo hari ini dan yang sudah diselesaikan hari ini.
  // Tiket terlambat dari hari sebelumnya hanya dihitung di angka "terlambat".
  const jadwalHariIni = [
    ...selesaiHariIni.map((satu) => ({ tiket: satu, selesai: true })),
    ...aktif
      .filter((satu) => hariIni(jamAcuan(satu), sekarang))
      .map((satu) => ({ tiket: satu, selesai: false })),
  ].sort((a, b) => (jamAcuan(a.tiket) ?? '').localeCompare(jamAcuan(b.tiket) ?? ''));
  const jumlahPekerjaan =
    jadwalHariIni.length +
    aktif.filter((satu) => !hariIni(jamAcuan(satu), sekarang) && terlambat(satu, sekarang)).length;

  const indeksKini = jadwalHariIni.findIndex((satu) => !satu.selesai);
  const halte: HalteJadwal[] = jadwalHariIni.slice(0, 4).map((satu, i) => ({
    jam: jamPendek(jamAcuan(satu.tiket)),
    label: labelHalte(satu.tiket),
    keadaan: satu.selesai ? 'lewat' : i === indeksKini ? 'kini' : 'nanti',
  }));

  const sekarangDikerjakan = aktif.find((satu) => STATUS_SEDANG_DIKERJAKAN.includes(satu.Status)) ?? aktif[0];
  const agenda = [
    ...aktif
      .filter((satu) => satu.DijadwalkanMulaiPada && !hariIni(satu.DijadwalkanMulaiPada, sekarang))
      .filter((satu) => new Date(satu.DijadwalkanMulaiPada ?? 0).getTime() > sekarang.getTime())
      .map((satu) => ({
        kunci: satu.Id,
        warna: 'biru' as const,
        judul: satu.Judul,
        teks: [
          tanggalPendek(satu.DijadwalkanMulaiPada),
          jamPendek(satu.DijadwalkanMulaiPada),
          satu.Lokasi?.Nama,
        ]
          .filter(Boolean)
          .join(' · '),
        ikon: ikonKategori(satu.Aset?.Kategori ?? satu.Aset?.Nama ?? satu.Judul).ikon,
        href: ruteLapangan.teknisi.tugasDetail(satu.Id),
      })),
    ...inspeksi.map((satu) => ({
      kunci: satu.Id,
      warna: 'oranye' as const,
      judul: `Inspeksi ${satu.NamaAset ?? satu.Nomor}`,
      teks: [tanggalPendek(satu.DijadwalkanPada), jamPendek(satu.DijadwalkanPada), satu.Lokasi]
        .filter(Boolean)
        .join(' · '),
      ikon: ikonKategori(satu.NamaAset).ikon,
      href: undefined,
    })),
  ];

  const fotoDiHp = fotoTertunda.foto.length;
  const konflikPertama = antrian.find((satu) => satu.Status === 'Konflik');

  return (
    <>
      <KartuApung className="overflow-hidden">
        <div className="flex items-center justify-between gap-2 px-4 pt-3 pb-1">
          <div className="flex items-center gap-2.5">
            <Ikon3D nama="spiral_calendar" ukuran={30} />
            <h2 className="text-base font-bold tracking-[-0.01em] whitespace-nowrap">Jadwal hari ini</h2>
          </div>
          <span className="min-w-0 text-right text-[13px] leading-tight font-semibold text-lapangan-teks-3 max-[380px]:text-xs">
            {selesaiHariIni.length > 0 ? (
              <>
                <b className="text-lapangan-teks">{selesaiHariIni.length}</b> dari {jadwalHariIni.length}{' '}
                selesai
              </>
            ) : (
              <>
                <b className="text-lapangan-teks">{jumlahPekerjaan}</b> pekerjaan
                {jumlahTerlambat > 0 ? ` · ${jumlahTerlambat} terlambat` : ''}
              </>
            )}
          </span>
        </div>
        {halte.length > 0 ? (
          <PerhentianJadwal halte={halte} className="pt-1.5" />
        ) : (
          <p className="px-4 pt-1 pb-3 text-sm text-lapangan-teks-3">
            Tidak ada pekerjaan terjadwal hari ini.
          </p>
        )}
        <div className="border-t-[1.5px] border-lapangan-garis-2" />
        <MenuGrid3D
          ukuran="ringkas"
          label="Menu teknisi"
          item={[
            {
              label: 'Tiket Saya',
              ikon: 'clipboard',
              tint: 'oranye',
              href: ruteLapangan.teknisi.tugas,
              jumlah: aktif.length,
            },
            { label: 'Pindai Aset', ikon: 'magnifying_glass_tilted_left', href: ruteLapangan.teknisi.pindai },
            {
              label: 'Suku Cadang',
              ikon: 'nut_and_bolt',
              tint: 'ungu',
              href: ruteLapangan.teknisi.sukuCadang,
            },
            {
              label: 'Riwayat',
              ikon: 'card_index_dividers',
              tint: 'hijau',
              href: ruteLapangan.teknisi.tugasTab('selesai'),
            },
          ]}
        />
      </KartuApung>

      {konflikPertama ? (
        <PitaInfo
          nada="merah"
          ikon="warning"
          judul={`${jumlahKonflik} perubahan perlu dipilih versinya`}
          teks="Tiket yang sama diubah juga dari kantor."
          tautan={{ label: 'Pilih', href: ruteLapangan.teknisi.konflik(konflikPertama.KunciOperasi) }}
        />
      ) : jumlahBelumTersinkron + fotoDiHp > 0 ? (
        <PitaInfo
          nada="kuning"
          ikon="satellite_antenna"
          judul={
            jumlahBelumTersinkron > 0
              ? `${jumlahBelumTersinkron} perubahan menunggu dikirim`
              : `${fotoDiHp} foto menunggu dikirim`
          }
          teks={[
            jumlahBelumTersinkron > 0 && fotoDiHp > 0 ? `${fotoDiHp} foto di HP` : null,
            daring ? 'Sedang dikirim ke server…' : 'Terkirim otomatis saat ada sinyal',
          ]
            .filter(Boolean)
            .join(' · ')}
          tautan={{ label: 'Lihat', href: ruteLapangan.akun }}
        />
      ) : null}

      <JudulBagian
        judul={selesaiHariIni.length > 0 ? 'Kerjakan berikutnya' : 'Kerjakan sekarang'}
        tautan={{ label: 'Lihat semua', href: ruteLapangan.teknisi.tugas }}
      />
      {sekarangDikerjakan ? (
        <KartuTiketTeknisi
          tiket={sekarangDikerjakan}
          href={ruteLapangan.teknisi.tugasDetail(sekarangDikerjakan.Id)}
          tampilStatus={false}
          aksi={
            <TombolLapangan
              ukuran="kecil"
              className="h-12 px-6 text-base"
              disabled={memproses === sekarangDikerjakan.Id}
              onClick={() => void mulai(sekarangDikerjakan)}
            >
              {STATUS_SEDANG_DIKERJAKAN.includes(sekarangDikerjakan.Status) ? 'Lanjutkan' : 'Mulai'}
            </TombolLapangan>
          }
        />
      ) : (
        <Kartu>
          <IlustrasiMomen
            ringkas
            jenis="kosong"
            ikon="check_mark_button"
            judul="Semua tiket beres"
            teks="Belum ada tiket baru untukmu. Tiket yang ditugaskan koordinator akan muncul di sini."
          />
        </Kartu>
      )}

      {agenda.length > 0 && (
        <ul
          aria-label="Agenda mendatang"
          className="-mx-4 flex snap-x gap-2.5 overflow-x-auto px-4 pb-1 [scrollbar-width:none]"
        >
          {agenda.map((satu) => (
            <li key={satu.kunci} className="w-[82%] shrink-0 snap-start">
              <Banner
                ringkas
                warna={satu.warna}
                judul={<span className="block truncate">{satu.judul}</span>}
                teks={<span className="block truncate">{satu.teks}</span>}
                ikon={satu.ikon}
                href={satu.href}
              />
            </li>
          ))}
        </ul>
      )}
    </>
  );
}
