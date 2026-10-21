<?php
/**
 * SIGAP-POLRI: Layanan PIN Pelapor di Meja Petugas
 *
 * Selama gateway SMS/WhatsApp belum terhubung, petugas SPKT membantu warga yang kehilangan PIN:
 * melihat PIN laporan LP-POLRI (disimpan apa adanya) atau mereset PIN untuk semua jenis tiket
 * (LP-POLRI, HT- Kehilangan & Temuan, POL- Satu Nomor). PIN tiket yang saling terhubung
 * (laporan modul lama ↔ nomor Satu Nomor jembatannya) selalu disamakan.
 */

namespace App\Models;

use App\Core\Model;
use App\Core\OpsPdp;

class PinDesk extends Model
{
    public const TYPE_LP = 'LP';
    public const TYPE_HT = 'HT';
    public const TYPE_POL = 'POL';

    public const TYPE_LABELS = [
        self::TYPE_LP  => 'Laporan Pengaduan (LP-POLRI)',
        self::TYPE_HT  => 'Kehilangan dan Temuan (HT)',
        self::TYPE_POL => 'Satu Nomor, Satu Jejak (POL)',
    ];

    public static function typeOf(string $ticket): ?string
    {
        return match (true) {
            (bool)preg_match('/^LP-POLRI-\d{6}-\d{3,6}$/', $ticket)          => self::TYPE_LP,
            (bool)preg_match('/^HT-\d{6}-\d{5}$/', $ticket)                  => self::TYPE_HT,
            (bool)preg_match('/^POL-\d{6}-[A-Z0-9]{3,4}-[A-Z0-9]{5}$/', $ticket) => self::TYPE_POL,
            default => null,
        };
    }

    /**
     * Ringkasan tiket untuk verifikasi identitas pelapor (data pribadi tersamar), atau null
     */
    public function find(string $ticket): ?array
    {
        $ticket = strtoupper(trim($ticket));
        $type = self::typeOf($ticket);

        $row = match ($type) {
            self::TYPE_LP => $this->fetch(
                "SELECT r.id, r.ticket_number AS ticket, r.title, r.status, r.created_at, r.reporter_name_raw AS reporter_name,
                        r.reporter_phone AS phone, r.reporter_nik_raw AS nik, r.is_anonymous,
                        (SELECT g.tracking_number FROM ops_global_tracks g WHERE g.external_reference_no = r.ticket_number LIMIT 1) AS linked
                 FROM reports r WHERE r.ticket_number = ?",
                [$ticket]
            ),
            self::TYPE_HT => $this->fetch(
                "SELECT id, code AS ticket, title, status, created_at, reporter_name, reporter_phone AS phone,
                        reporter_nik AS nik, 0 AS is_anonymous, NULL AS linked
                 FROM lost_found_items WHERE code = ?",
                [$ticket]
            ),
            self::TYPE_POL => $this->fetch(
                "SELECT id, tracking_number AS ticket, incident_category AS title, current_status AS status, created_at,
                        citizen_name AS reporter_name, citizen_phone AS phone, citizen_nik_encrypted AS nik_encrypted,
                        is_whistleblower AS is_anonymous, external_reference_no AS linked
                 FROM ops_global_tracks WHERE tracking_number = ?",
                [$ticket]
            ),
            default => null,
        };
        if (!$row) {
            return null;
        }

        $nik = $type === self::TYPE_POL ? OpsPdp::decrypt($row['nik_encrypted'] ?? null) : ($row['nik'] ?? null);
        unset($row['nik'], $row['nik_encrypted']);

        return $row + [
            'type'         => $type,
            'type_label'   => self::TYPE_LABELS[$type],
            'phone_masked' => OpsPdp::maskPhone((string)$row['phone']),
            'nik_masked'   => $nik ? OpsPdp::maskNik($nik) : '-',
            'nik_last4'    => $nik ? substr($nik, -4) : null,
            // Hanya PIN laporan LP-POLRI yang tersimpan dapat dibaca; HT & POL disimpan dalam bentuk hash
            'can_reveal'   => $type === self::TYPE_LP,
        ];
    }

    /**
     * PIN laporan LP-POLRI yang tersimpan (null untuk jenis tiket lain)
     */
    public function revealPin(string $ticket): ?string
    {
        if (self::typeOf($ticket) !== self::TYPE_LP) {
            return null;
        }
        $pin = $this->fetch("SELECT security_pin FROM reports WHERE ticket_number = ?", [$ticket])['security_pin'] ?? null;
        return $pin !== null ? (string)$pin : null;
    }

    /**
     * Buat PIN baru: PIN lama langsung tidak berlaku, tiket pasangan ikut diperbarui,
     * dan kunci salah-PIN untuk tiket tersebut dibuka. Mengembalikan PIN baru.
     */
    public function resetPin(string $ticket): ?string
    {
        $item = $this->find($ticket);
        if (!$item) {
            return null;
        }

        $pin = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($pin, PASSWORD_BCRYPT);

        // Pasangan laporan modul lama ↔ Satu Nomor berbagi PIN yang sama
        $lp = $item['type'] === self::TYPE_LP ? $item['ticket'] : ($item['type'] === self::TYPE_POL ? $item['linked'] : null);
        $pol = $item['type'] === self::TYPE_POL ? $item['ticket'] : ($item['type'] === self::TYPE_LP ? $item['linked'] : null);

        $this->beginTransaction();
        try {
            if ($item['type'] === self::TYPE_HT) {
                $this->execute("UPDATE lost_found_items SET security_pin_hash = ? WHERE code = ?", [$hash, $item['ticket']]);
            }
            if ($lp) {
                $this->execute("UPDATE reports SET security_pin = ? WHERE ticket_number = ?", [$pin, $lp]);
            }
            if ($pol) {
                $this->execute("UPDATE ops_global_tracks SET security_pin_hash = ? WHERE tracking_number = ?", [$hash, $pol]);
            }

            foreach (array_filter([$item['ticket'], $lp, $pol]) as $t) {
                $this->execute(
                    "DELETE FROM throttle_events WHERE (bucket = 'pin_ticket' AND throttle_key = ?) OR (bucket = 'pin_ticket_ip' AND throttle_key LIKE ?)",
                    [$t, $t . '|%']
                );
            }

            $this->commit();
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }

        return $pin;
    }
}
