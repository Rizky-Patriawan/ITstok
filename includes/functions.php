<?php
/**
 * Fungsi bantu bersama yang dipakai di berbagai halaman.
 */

/** Daftar departemen untuk form Barang Keluar. Sengaja list tetap (bukan
 * tabel master) supaya tetap simpel — bisa diupgrade jadi tabel kalau nanti
 * daftarnya perlu dikelola dinamis. */
const DEPARTEMEN = [
    'IT', 'Keuangan', 'HR & GA', 'Marketing', 'Operations',
    'Customer Service', 'IT Infrastructure', 'Legal', 'Procurement', 'Direksi',
];

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** Tampilkan badge kondisi barang dengan warna yang sesuai. */
function kondisiBadge(string $kondisi): string
{
    $map = [
        'baik'   => ['label' => 'Baik',   'class' => 'badge-ok'],
        'rusak'  => ['label' => 'Rusak',  'class' => 'badge-rusak'],
        'servis' => ['label' => 'Servis', 'class' => 'badge-servis'],
    ];
    $item = $map[$kondisi] ?? ['label' => e($kondisi), 'class' => 'badge-ok'];
    return '<span class="badge ' . $item['class'] . '">' . $item['label'] . '</span>';
}

/** Format kolom DATE ("Y-m-d") jadi "29 Jul 2026". */
function formatTanggal(string $dateStr): string
{
    $dt = new DateTime($dateStr);
    return $dt->format('d M Y');
}

/** Format kolom DATETIME (created_at, UTC) jadi "29 Jul 2026, 14:30" WIB. */
function formatWaktu(string $datetimeStr): string
{
    $dt = new DateTime($datetimeStr, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
    return $dt->format('d M Y, H:i');
}

/**
 * Statistik ringkasan untuk Dashboard: total barang, transaksi hari ini,
 * stok total, daftar stok rendah, dan aktivitas terbaru (gabungan masuk+keluar).
 */
function getDashboardStats(PDO $db): array
{
    $totalBarang = (int) $db->query('SELECT COUNT(*) FROM barang')->fetchColumn();
    $stokTotal = (int) $db->query('SELECT COALESCE(SUM(stok), 0) FROM barang')->fetchColumn();

    $masukHariIni = $db->query(
        "SELECT COUNT(*) AS jml_transaksi, COALESCE(SUM(jumlah), 0) AS total_qty
         FROM barang_masuk WHERE tanggal = CURDATE()"
    )->fetch();

    $keluarHariIni = $db->query(
        "SELECT COUNT(*) AS jml_transaksi, COALESCE(SUM(jumlah), 0) AS total_qty
         FROM barang_keluar WHERE tanggal = CURDATE()"
    )->fetch();

    $stokRendah = $db->query(
        'SELECT id, kode, nama, stok, stok_min FROM barang
         WHERE stok <= stok_min ORDER BY stok ASC LIMIT 10'
    )->fetchAll();

    $aktivitasTerbaru = $db->query(
        "(SELECT 'masuk' AS tipe, bm.id, bm.jumlah, bm.created_at, b.nama AS nama_barang,
                 bm.supplier AS pihak
          FROM barang_masuk bm JOIN barang b ON b.id = bm.barang_id)
         UNION ALL
         (SELECT 'keluar' AS tipe, bk.id, bk.jumlah, bk.created_at, b.nama AS nama_barang,
                 bk.penerima AS pihak
          FROM barang_keluar bk JOIN barang b ON b.id = bk.barang_id)
         ORDER BY created_at DESC LIMIT 8"
    )->fetchAll();

    return [
        'total_barang' => $totalBarang,
        'stok_total' => $stokTotal,
        'masuk_hari_ini' => $masukHariIni,
        'keluar_hari_ini' => $keluarHariIni,
        'stok_rendah' => $stokRendah,
        'aktivitas_terbaru' => $aktivitasTerbaru,
    ];
}
