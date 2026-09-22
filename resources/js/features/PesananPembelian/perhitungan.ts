import type { DetailPesananPembelian, PesananPembelian } from './types';

export /** Sisa yang belum diterima per baris PO, dihitung dari seluruh dokumen penerimaan. */
function hitungSisa(pesanan: PesananPembelian, detail: DetailPesananPembelian): number {
  const diterima = (pesanan.Penerimaan ?? []).reduce((jumlah, dokumen) => {
    const baris = (dokumen.Detail ?? []).filter((item) => item.DetailPesananPembelianId === detail.Id);
    return jumlah + baris.reduce((sub, item) => sub + Number(item.JumlahDiterima), 0);
  }, 0);

  return Math.max(Number(detail.Jumlah) - diterima, 0);
}
