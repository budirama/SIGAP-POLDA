<?php
/**
 * SIGAP-POLRI: Model Hilang & Temu (Kehilangan / Temuan, Pencocokan Otomatis, Klaim Pemilik)
 */

namespace App\Models;

use App\Core\Model;

class LostFound extends Model
{
    public const TYPES = [
        'LOST'  => 'Kehilangan',
        'FOUND' => 'Temuan',
    ];

    public const CATEGORIES = [
        'KENDARAAN'       => ['Kendaraan', 'fa-car-side'],
        'ORANG'           => ['Orang Hilang', 'fa-person-circle-question'],
        'BARANG_BERHARGA' => ['Barang Berharga', 'fa-gem'],
        'DOKUMEN'         => ['Dokumen / Surat', 'fa-id-card'],
        'HEWAN'           => ['Hewan', 'fa-paw'],
        'LAINNYA'         => ['Lainnya', 'fa-box-open'],
    ];

    public const STATUSES = [
        'PENDING'   => 'Menunggu Verifikasi',
        'PUBLISHED' => 'Dipublikasikan',
        'MATCHED'   => 'Cocok / Proses Serah Terima',
        'RESOLVED'  => 'Selesai',
        'REJECTED'  => 'Ditolak',
    ];

    /** Status yang tampil di galeri publik */
    public const PUBLIC_STATUSES = ['PUBLISHED', 'MATCHED'];

    /** Status yang masih ikut dicocokkan */
    public const OPEN_STATUSES = ['PENDING', 'PUBLISHED', 'MATCHED'];

    /** Skor minimal agar pasangan hilang ↔ temuan disarankan ke petugas */
    public const MATCH_THRESHOLD = 35;

    private const IDENTIFIER_FIELDS = [
        'plate_number'   => ['Plat nomor', 70],
        'chassis_number' => ['No. rangka', 70],
        'engine_number'  => ['No. mesin', 60],
        'serial_number'  => ['IMEI / no. seri', 70],
    ];

    /**
     * Simpan laporan baru beserta foto. Mengembalikan ['id', 'code', 'pin'].
     * Petugas yang menginput di loket langsung mempublikasikan laporan.
     */
    public function create(array $data, array $photos = []): array
    {
        $pin = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $officerId = $data['created_by_officer_id'] ?? null;

        $this->beginTransaction();
        try {
            $this->execute(
                "INSERT INTO lost_found_items
                    (security_pin_hash, report_type, item_category, title, description,
                     brand, color, plate_number, chassis_number, engine_number, serial_number,
                     person_name, person_age, person_gender, animal_species,
                     event_date, event_location, latitude, longitude, custody_location,
                     reporter_name, reporter_phone, reporter_nik, created_by_officer_id,
                     status, verified_by, verified_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    password_hash($pin, PASSWORD_BCRYPT),
                    $data['report_type'],
                    $data['item_category'],
                    $data['title'],
                    $data['description'],
                    $data['brand'] ?? null,
                    $data['color'] ?? null,
                    self::normalizeId($data['plate_number'] ?? null),
                    self::normalizeId($data['chassis_number'] ?? null),
                    self::normalizeId($data['engine_number'] ?? null),
                    self::normalizeId($data['serial_number'] ?? null),
                    $data['person_name'] ?? null,
                    $data['person_age'] ?? null,
                    $data['person_gender'] ?? null,
                    $data['animal_species'] ?? null,
                    $data['event_date'],
                    $data['event_location'],
                    $data['latitude'] ?? null,
                    $data['longitude'] ?? null,
                    $data['custody_location'] ?? null,
                    $data['reporter_name'],
                    $data['reporter_phone'],
                    $data['reporter_nik'] ?? null,
                    $officerId,
                    $officerId ? 'PUBLISHED' : 'PENDING',
                    $officerId,
                    $officerId ? date('Y-m-d H:i:s') : null,
                ]
            );

            $id = $this->lastInsertId();
            // Nomor turunan dari ID auto-increment: unik & aman untuk laporan bersamaan
            $code = sprintf('HT-%s-%05d', date('Ym'), $id);
            $this->execute("UPDATE lost_found_items SET code = ? WHERE id = ?", [$code, $id]);

            foreach ($photos as $photo) {
                $this->execute(
                    "INSERT INTO lost_found_photos (item_id, file_path, file_size_kb) VALUES (?, ?, ?)",
                    [$id, $photo['file_path'], $photo['file_size_kb']]
                );
            }

            $this->commit();
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }

        $this->refreshMatches($id);

        return ['id' => $id, 'code' => $code, 'pin' => $pin];
    }

    public function findById(int $id): ?array
    {
        return $this->fetch(
            "SELECT i.*, v.name AS verifier_name, v.rank AS verifier_rank, v.nrp AS verifier_nrp, o.name AS officer_name
             FROM lost_found_items i
             LEFT JOIN users v ON v.id = i.verified_by
             LEFT JOIN users o ON o.id = i.created_by_officer_id
             WHERE i.id = ?",
            [$id]
        );
    }

    public function findByCode(string $code): ?array
    {
        return $this->fetch("SELECT * FROM lost_found_items WHERE code = ?", [strtoupper(trim($code))]);
    }

    /**
     * Laporan milik pelapor bila kode & PIN cocok
     */
    public function verifyPin(string $code, string $pin): ?array
    {
        $item = $this->findByCode($code);
        return ($item && password_verify($pin, $item['security_pin_hash'])) ? $item : null;
    }

    public function photos(int $itemId): array
    {
        return $this->fetchAll("SELECT * FROM lost_found_photos WHERE item_id = ? ORDER BY id", [$itemId]);
    }

    public function findPhoto(int $photoId): ?array
    {
        return $this->fetch(
            "SELECT p.*, i.status, i.code FROM lost_found_photos p
             JOIN lost_found_items i ON i.id = p.item_id WHERE p.id = ?",
            [$photoId]
        );
    }

    /**
     * Galeri publik: hanya laporan terverifikasi yang masih berjalan
     */
    public function paginatePublic(array $filters, int $page, int $perPage = 12): array
    {
        $filters['statuses'] = self::PUBLIC_STATUSES;
        return $this->paginate($filters, $page, $perPage);
    }

    public function paginateAdmin(array $filters, int $page, int $perPage = 25): array
    {
        return $this->paginate($filters, $page, $perPage);
    }

    private function paginate(array $filters, int $page, int $perPage): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $total = (int)($this->fetch("SELECT COUNT(*) AS c FROM lost_found_items i WHERE 1=1 {$where}", $params)['c'] ?? 0);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(
            "SELECT i.*,
                    (SELECT p.id FROM lost_found_photos p WHERE p.item_id = i.id ORDER BY p.id LIMIT 1) AS cover_photo_id,
                    (SELECT COUNT(*) FROM lost_found_matches m
                      WHERE (m.lost_item_id = i.id OR m.found_item_id = i.id) AND m.status = 'SUGGESTED') AS suggested_matches,
                    (SELECT COUNT(*) FROM lost_found_claims c WHERE c.item_id = i.id AND c.status = 'PENDING') AS pending_claims
             FROM lost_found_items i
             WHERE 1=1 {$where}
             ORDER BY i.created_at DESC, i.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    private function buildFilters(array $filters): array
    {
        $sql = '';
        $params = [];

        if (!empty($filters['statuses'])) {
            $sql .= 'AND i.status IN (' . implode(',', array_fill(0, count($filters['statuses']), '?')) . ') ';
            array_push($params, ...$filters['statuses']);
        }
        if (!empty($filters['status']) && isset(self::STATUSES[$filters['status']])) {
            $sql .= 'AND i.status = ? ';
            $params[] = $filters['status'];
        }
        if (!empty($filters['type']) && isset(self::TYPES[$filters['type']])) {
            $sql .= 'AND i.report_type = ? ';
            $params[] = $filters['type'];
        }
        if (!empty($filters['category']) && isset(self::CATEGORIES[$filters['category']])) {
            $sql .= 'AND i.item_category = ? ';
            $params[] = $filters['category'];
        }
        if (!empty($filters['q'])) {
            $like = '%' . $filters['q'] . '%';
            $id = self::normalizeId($filters['q']);
            $sql .= 'AND (i.title LIKE ? OR i.description LIKE ? OR i.event_location LIKE ? OR i.brand LIKE ?
                      OR i.person_name LIKE ? OR i.code = ? OR i.plate_number = ? OR i.serial_number = ?) ';
            array_push($params, $like, $like, $like, $like, $like, strtoupper($filters['q']), $id, $id);
        }

        return [$sql, $params];
    }

    public function countByStatus(): array
    {
        $rows = $this->fetchAll("SELECT status, COUNT(*) AS c FROM lost_found_items GROUP BY status");
        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int)$row['c'];
        }
        return $counts;
    }

    // ------------------------------------------------------------------
    // Alur status oleh petugas
    // ------------------------------------------------------------------

    public function publish(int $id, int $officerId, ?string $custody): bool
    {
        return $this->affected(
            "UPDATE lost_found_items
             SET status = 'PUBLISHED', verified_by = ?, verified_at = NOW(),
                 custody_location = COALESCE(NULLIF(?, ''), custody_location)
             WHERE id = ? AND status = 'PENDING'",
            [$officerId, (string)$custody, $id]
        ) > 0;
    }

    public function reject(int $id, int $officerId, string $note): bool
    {
        return $this->affected(
            "UPDATE lost_found_items SET status = 'REJECTED', verified_by = ?, verified_at = NOW(), officer_note = ?
             WHERE id = ? AND status IN ('PENDING', 'PUBLISHED')",
            [$officerId, $note, $id]
        ) > 0;
    }

    /**
     * Serah terima / dinyatakan selesai. Pasangan yang terkonfirmasi ikut diselesaikan.
     */
    public function resolve(int $id, int $officerId, string $note): bool
    {
        $this->beginTransaction();
        try {
            $done = $this->affected(
                "UPDATE lost_found_items SET status = 'RESOLVED', resolved_at = NOW(), officer_note = ?
                 WHERE id = ? AND status IN ('PUBLISHED', 'MATCHED')",
                [$note, $id]
            ) > 0;

            if ($done) {
                $this->execute(
                    "UPDATE lost_found_items i
                     JOIN lost_found_matches m ON m.status = 'CONFIRMED'
                          AND ((m.lost_item_id = ? AND m.found_item_id = i.id) OR (m.found_item_id = ? AND m.lost_item_id = i.id))
                     SET i.status = 'RESOLVED', i.resolved_at = NOW(), i.officer_note = ?
                     WHERE i.status IN ('PUBLISHED', 'MATCHED')",
                    [$id, $id, $note]
                );
                // Klaim lain yang belum diputus otomatis ditolak
                $this->execute(
                    "UPDATE lost_found_claims SET status = 'REJECTED', reviewed_by = ?, reviewed_at = NOW(),
                            review_note = 'Barang telah diserahterimakan kepada pemilik sah'
                     WHERE item_id = ? AND status = 'PENDING'",
                    [$officerId, $id]
                );
            }

            $this->commit();
            return $done;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Pencocokan otomatis hilang ↔ temuan
    // ------------------------------------------------------------------

    /**
     * Hitung ulang saran kecocokan untuk satu laporan terhadap laporan berlawanan
     * (kategori sama, masih terbuka, maks. 1 tahun). Keputusan petugas tidak ditimpa.
     */
    public function refreshMatches(int $itemId): int
    {
        $item = $this->findById($itemId);
        if (!$item || !in_array($item['status'], self::OPEN_STATUSES, true)) {
            return 0;
        }

        $opposite = $item['report_type'] === 'LOST' ? 'FOUND' : 'LOST';
        $candidates = $this->fetchAll(
            "SELECT * FROM lost_found_items
             WHERE report_type = ? AND item_category = ? AND status IN ('PENDING', 'PUBLISHED', 'MATCHED')
               AND created_at > NOW() - INTERVAL 365 DAY
             ORDER BY id DESC LIMIT 500",
            [$opposite, $item['item_category']]
        );

        $saved = 0;
        foreach ($candidates as $candidate) {
            [$lost, $found] = $item['report_type'] === 'LOST' ? [$item, $candidate] : [$candidate, $item];
            [$score, $reasons] = self::score($lost, $found);
            if ($score < self::MATCH_THRESHOLD) {
                continue;
            }

            $this->execute(
                "INSERT INTO lost_found_matches (lost_item_id, found_item_id, score, reasons)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    score = IF(status = 'SUGGESTED', VALUES(score), score),
                    reasons = IF(status = 'SUGGESTED', VALUES(reasons), reasons)",
                [(int)$lost['id'], (int)$found['id'], $score, mb_substr(implode('; ', $reasons), 0, 255)]
            );
            $saved++;
        }

        return $saved;
    }

    /**
     * Skor kecocokan 0-100 antara laporan kehilangan dan temuan beserta alasannya.
     * Nomor identitas (plat/rangka/mesin/IMEI) paling menentukan; ciri lain menambah bobot.
     *
     * @return array{0:int, 1:string[]}
     */
    public static function score(array $lost, array $found): array
    {
        $score = 0;
        $reasons = [];

        foreach (self::IDENTIFIER_FIELDS as $field => [$label, $weight]) {
            $a = self::normalizeId($lost[$field] ?? null);
            $b = self::normalizeId($found[$field] ?? null);
            if ($a === null || $b === null) {
                continue;
            }
            if ($a === $b) {
                $score += $weight;
                $reasons[] = "{$label} sama";
            } else {
                $score -= 40;
            }
        }

        $personA = self::normalizeText($lost['person_name'] ?? '');
        $personB = self::normalizeText($found['person_name'] ?? '');
        if ($personA !== '' && $personB !== '') {
            similar_text($personA, $personB, $pct);
            if ($pct >= 80) {
                $score += 40;
                $reasons[] = 'Nama orang mirip';
            }
        }

        if (!empty($lost['person_gender']) && !empty($found['person_gender'])) {
            if ($lost['person_gender'] !== $found['person_gender']) {
                $score -= 40;
            } elseif (!empty($lost['person_age']) && !empty($found['person_age'])
                && abs((int)$lost['person_age'] - (int)$found['person_age']) <= 3) {
                $score += 15;
                $reasons[] = 'Jenis kelamin & usia sesuai';
            }
        }

        foreach (['brand' => 'Merk', 'color' => 'Warna', 'animal_species' => 'Jenis hewan'] as $field => $label) {
            $a = self::normalizeText($lost[$field] ?? '');
            $b = self::normalizeText($found[$field] ?? '');
            if ($a !== '' && $b !== '' && (str_contains($a, $b) || str_contains($b, $a))) {
                $score += $field === 'animal_species' ? 15 : 10;
                $reasons[] = "{$label} sama";
            }
        }

        // Temuan wajar terjadi setelah kehilangan (toleransi 1 hari, maks. 90 hari)
        if (!empty($lost['event_date']) && !empty($found['event_date'])) {
            $days = (int)floor((strtotime($found['event_date']) - strtotime($lost['event_date'])) / 86400);
            if ($days >= -1 && $days <= 90) {
                $score += 10;
                $reasons[] = 'Rentang tanggal sesuai';
            } elseif ($days < -1) {
                $score -= 20;
            }
        }

        $overlap = self::tokenOverlap(
            ($lost['title'] ?? '') . ' ' . ($lost['description'] ?? '') . ' ' . ($lost['event_location'] ?? ''),
            ($found['title'] ?? '') . ' ' . ($found['description'] ?? '') . ' ' . ($found['event_location'] ?? '')
        );
        if ($overlap >= 0.15) {
            $score += (int)round(min($overlap, 1) * 30);
            $reasons[] = 'Deskripsi mirip';
        }

        return [max(0, min(100, $score)), $reasons];
    }

    /**
     * Ciri pengenal yang aman ditampilkan ke publik.
     * Kehilangan: plat utuh (membantu warga mengenali), nomor lain disamarkan.
     * Temuan: semua nomor disamarkan agar hanya pemilik asli yang dapat membuktikannya saat klaim.
     *
     * @return array<int, array{0:string, 1:string}>
     */
    public static function publicIdentifiers(array $item): array
    {
        $isLost = $item['report_type'] === 'LOST';
        $rows = [];
        foreach (['brand' => 'Merk / Jenis', 'color' => 'Warna', 'animal_species' => 'Jenis Hewan'] as $field => $label) {
            if (!empty($item[$field])) {
                $rows[] = [$label, (string)$item[$field]];
            }
        }
        if (!empty($item['person_name'])) {
            $rows[] = ['Nama', (string)$item['person_name']];
        }
        if (!empty($item['person_age'])) {
            $rows[] = ['Usia', (int)$item['person_age'] . ' tahun'];
        }
        if (!empty($item['person_gender'])) {
            $rows[] = ['Jenis Kelamin', $item['person_gender'] === 'L' ? 'Laki-laki' : 'Perempuan'];
        }
        foreach (self::IDENTIFIER_FIELDS as $field => [$label]) {
            if (empty($item[$field])) {
                continue;
            }
            $value = ($isLost && $field === 'plate_number') ? (string)$item[$field] : self::mask((string)$item[$field]);
            $rows[] = [$label, $value];
        }
        return $rows;
    }

    /** Sisakan 2 karakter terakhir: B1234XYZ → ******YZ */
    public static function mask(string $value): string
    {
        $len = strlen($value);
        return $len <= 2 ? str_repeat('*', $len) : str_repeat('*', $len - 2) . substr($value, -2);
    }

    /**
     * Huruf/angka kapital tanpa spasi/tanda baca (B 1234 XYZ → B1234XYZ); null bila kosong
     */
    public static function normalizeId(?string $value): ?string
    {
        $value = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$value));
        return $value === '' ? null : $value;
    }

    private static function normalizeText(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower($value)));
    }

    /** Koefisien Jaccard kata bermakna (≥4 huruf) dari dua teks */
    private static function tokenOverlap(string $a, string $b): float
    {
        $tokens = function (string $text): array {
            preg_match_all('/[\p{L}\p{N}]{4,}/u', mb_strtolower($text), $m);
            return array_unique($m[0]);
        };
        $ta = $tokens($a);
        $tb = $tokens($b);
        if (!$ta || !$tb) {
            return 0.0;
        }
        return count(array_intersect($ta, $tb)) / count(array_unique(array_merge($ta, $tb)));
    }

    /**
     * Pasangan kecocokan suatu laporan, beserta ringkasan laporan pasangannya
     */
    public function matchesFor(int $itemId): array
    {
        return $this->fetchAll(
            "SELECT m.*, o.id AS other_id, o.code AS other_code, o.title AS other_title, o.status AS other_status,
                    o.event_date AS other_event_date, o.event_location AS other_location, o.report_type AS other_type,
                    o.reporter_name AS other_reporter, o.reporter_phone AS other_phone
             FROM lost_found_matches m
             JOIN lost_found_items o ON o.id = IF(m.lost_item_id = ?, m.found_item_id, m.lost_item_id)
             WHERE m.lost_item_id = ? OR m.found_item_id = ?
             ORDER BY FIELD(m.status, 'CONFIRMED', 'SUGGESTED', 'DISMISSED'), m.score DESC",
            [$itemId, $itemId, $itemId]
        );
    }

    public function findMatch(int $matchId): ?array
    {
        return $this->fetch("SELECT * FROM lost_found_matches WHERE id = ?", [$matchId]);
    }

    /**
     * Petugas mengonfirmasi / menolak saran. Konfirmasi menandai kedua laporan MATCHED.
     */
    public function reviewMatch(int $matchId, int $officerId, bool $confirm): bool
    {
        $this->beginTransaction();
        try {
            $match = $this->fetch("SELECT * FROM lost_found_matches WHERE id = ? FOR UPDATE", [$matchId]);
            if (!$match || $match['status'] !== 'SUGGESTED') {
                $this->rollBack();
                return false;
            }

            $this->execute(
                "UPDATE lost_found_matches SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?",
                [$confirm ? 'CONFIRMED' : 'DISMISSED', $officerId, $matchId]
            );

            if ($confirm) {
                $this->execute(
                    "UPDATE lost_found_items SET status = 'MATCHED',
                            verified_by = COALESCE(verified_by, ?), verified_at = COALESCE(verified_at, NOW())
                     WHERE id IN (?, ?) AND status IN ('PENDING', 'PUBLISHED')",
                    [$officerId, (int)$match['lost_item_id'], (int)$match['found_item_id']]
                );
            }

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Ringkasan untuk pelapor: jumlah pasangan yang sudah dikonfirmasi petugas
     */
    public function confirmedMatchCount(int $itemId): int
    {
        return (int)($this->fetch(
            "SELECT COUNT(*) AS c FROM lost_found_matches
             WHERE (lost_item_id = ? OR found_item_id = ?) AND status = 'CONFIRMED'",
            [$itemId, $itemId]
        )['c'] ?? 0);
    }

    // ------------------------------------------------------------------
    // Klaim kepemilikan barang temuan
    // ------------------------------------------------------------------

    public function createClaim(int $itemId, array $data): int
    {
        $this->execute(
            "INSERT INTO lost_found_claims (item_id, claimant_name, claimant_phone, claimant_nik, proof_description, proof_file_path)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$itemId, $data['claimant_name'], $data['claimant_phone'], $data['claimant_nik'],
             $data['proof_description'], $data['proof_file_path'] ?? null]
        );
        return $this->lastInsertId();
    }

    public function claimsFor(int $itemId): array
    {
        return $this->fetchAll(
            "SELECT c.*, u.name AS reviewer_name FROM lost_found_claims c
             LEFT JOIN users u ON u.id = c.reviewed_by
             WHERE c.item_id = ? ORDER BY c.id DESC",
            [$itemId]
        );
    }

    public function findClaim(int $claimId): ?array
    {
        return $this->fetch("SELECT * FROM lost_found_claims WHERE id = ?", [$claimId]);
    }

    /**
     * Setujui / tolak klaim. Klaim disetujui menandai barang MATCHED (menunggu serah terima).
     */
    public function reviewClaim(int $claimId, int $officerId, bool $approve, string $note): bool
    {
        $this->beginTransaction();
        try {
            $claim = $this->fetch("SELECT * FROM lost_found_claims WHERE id = ? FOR UPDATE", [$claimId]);
            if (!$claim || $claim['status'] !== 'PENDING') {
                $this->rollBack();
                return false;
            }

            $this->execute(
                "UPDATE lost_found_claims SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_note = ? WHERE id = ?",
                [$approve ? 'APPROVED' : 'REJECTED', $officerId, $note !== '' ? $note : null, $claimId]
            );

            if ($approve) {
                $this->execute(
                    "UPDATE lost_found_items SET status = 'MATCHED' WHERE id = ? AND status = 'PUBLISHED'",
                    [(int)$claim['item_id']]
                );
            }

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }
}
