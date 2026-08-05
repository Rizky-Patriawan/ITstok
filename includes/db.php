<?php
/**
 * Koneksi database MySQL (PDO) + inisialisasi skema.
 * Dipanggil otomatis oleh setiap halaman lewat require_once.
 * Kredensial diatur lewat includes/config.php (lihat includes/config.sample.php).
 */

function getDb(): PDO
{
    static $db = null;

    if ($db !== null) {
        return $db;
    }

    $config = loadDbConfig();
    $charset = $config['charset'] ?? 'utf8mb4';

    ensureDatabaseExists($config, $charset);

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'] ?? 3306,
        $config['database'],
        $charset
    );

    $db = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Jalankan skema (aman dipanggil berkali-kali karena semua tabel
    // pakai "CREATE TABLE IF NOT EXISTS")
    $schema = file_get_contents(__DIR__ . '/../schema.sql');
    foreach (splitSqlStatements($schema) as $statement) {
        $db->exec($statement);
    }

    // Migrasi otomatis: tambah kolom baru ke tabel yang sudah ada
    migrateBarangColumns($db, $config['database']);

    return $db;
}

function loadDbConfig(): array
{
    $configFile = __DIR__ . '/config.php';
    if (!file_exists($configFile)) {
        throw new RuntimeException(
            'File includes/config.php belum ada. Salin dari includes/config.sample.php ' .
            'lalu isi kredensial database MySQL kamu.'
        );
    }
    return require $configFile;
}

/**
 * MySQL mengharuskan database-nya SUDAH ADA sebelum bisa dikoneksikan.
 * Fungsi ini konek dulu TANPA nama database, lalu bikin database-nya kalau
 * belum ada, supaya pengalamannya tetap "langsung jalan" tanpa setup manual.
 */
function ensureDatabaseExists(array $config, string $charset): void
{
    $dbName = $config['database'];

    if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
        throw new RuntimeException("Nama database di config.php tidak valid: \"$dbName\"");
    }

    $dsnWithoutDbName = sprintf(
        'mysql:host=%s;port=%d;charset=%s',
        $config['host'],
        $config['port'] ?? 3306,
        $charset
    );

    $bootstrap = new PDO($dsnWithoutDbName, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    try {
        $bootstrap->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET $charset");
    } catch (PDOException $e) {
        throw new RuntimeException(
            "Database \"$dbName\" belum ada dan gagal dibuat otomatis (kemungkinan user MySQL " .
            "kamu tidak punya izin CREATE DATABASE). Buat database-nya manual dulu lewat " .
            "phpMyAdmin/cPanel dengan nama persis \"$dbName\", lalu coba lagi. " .
            'Pesan asli: ' . $e->getMessage()
        );
    }
}

/**
 * Migrasi otomatis: tambah kolom baru ke tabel barang kalau belum ada.
 * Dibutuhkan kalau database sudah dibuat dari schema versi sebelumnya
 * (sebelum kolom model dan kondisi ditambahkan).
 */
function migrateBarangColumns(PDO $db, string $dbName): void
{
    $stmt = $db->prepare(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
    );
    $stmt->execute([$dbName, 'barang']);
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('model', $existing)) {
        $db->exec("ALTER TABLE barang ADD COLUMN model VARCHAR(255) NOT NULL DEFAULT '' AFTER nama");
    }
    if (!in_array('kondisi', $existing)) {
        $db->exec("ALTER TABLE barang ADD COLUMN kondisi ENUM('baik','rusak','servis') NOT NULL DEFAULT 'baik' AFTER stok_min");
    }
}

/**
 * Pecah file .sql jadi statement per statement berdasarkan ';', supaya bisa
 * dieksekusi satu-satu lewat exec(). PDO_MYSQL tidak mendukung banyak
 * statement sekaligus dalam satu panggilan exec()/query().
 */
function splitSqlStatements(string $sql): array
{
    $statements = array_map('trim', explode(';', $sql));

    return array_values(array_filter($statements, function (string $statement): bool {
        $withoutComments = preg_replace('/^\s*--.*$/m', '', $statement);
        return trim($withoutComments) !== '';
    }));
}

/**
 * Deteksi error pelanggaran UNIQUE constraint (kode barang/username dobel)
 * lewat SQLSTATE 23000 — kode standar ANSI untuk integrity constraint
 * violation, dipakai MySQL untuk kasus unique key maupun foreign key.
 */
function isUniqueConstraintError(PDOException $e): bool
{
    return $e->getCode() === '23000';
}
