<?php
/**
 * SIGAP-POLRI: Chat Model (Live Chat warga & Omnichat agen Command Center)
 */

namespace App\Models;

use App\Core\Model;

class Chat extends Model
{
    public const MAX_BODY = 1000;
    public const MAX_POLL = 200;

    /**
     * Buka percakapan baru beserta pesan pertama warga; kembalikan ['id', 'token']
     */
    public function start(string $name, ?string $phone, string $firstMessage): array
    {
        $token = bin2hex(random_bytes(32));

        $this->beginTransaction();
        try {
            $this->execute(
                "INSERT INTO chat_conversations (visitor_token, visitor_name, visitor_phone, status, last_message_at)
                 VALUES (?, ?, ?, 'WAITING', NOW())",
                [$token, $name, $phone]
            );
            $id = $this->lastInsertId();

            $this->execute(
                "INSERT INTO chat_messages (conversation_id, sender_type, body) VALUES (?, 'SYSTEM', ?)",
                [$id, 'Terima kasih telah menghubungi SPKT Polri. Mohon tunggu, petugas Command Center akan segera membalas.']
            );
            $this->execute(
                "INSERT INTO chat_messages (conversation_id, sender_type, body) VALUES (?, 'VISITOR', ?)",
                [$id, $firstMessage]
            );

            $this->commit();
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }

        return ['id' => $id, 'token' => $token];
    }

    public function findById(int $id): ?array
    {
        return $this->fetch(
            "SELECT c.*, u.name AS agent_name, u.rank AS agent_rank, r.ticket_number AS report_ticket
             FROM chat_conversations c
             LEFT JOIN users u ON u.id = c.assigned_agent_id
             LEFT JOIN reports r ON r.id = c.report_id
             WHERE c.id = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * Percakapan milik sesi warga (id + token harus cocok)
     */
    public function findForVisitor(int $id, string $token): ?array
    {
        $conv = $id > 0 ? $this->findById($id) : null;
        return $conv && hash_equals($conv['visitor_token'], $token) ? $conv : null;
    }

    public function addMessage(int $conversationId, string $senderType, ?int $senderId, string $body): int
    {
        $this->execute(
            "INSERT INTO chat_messages (conversation_id, sender_type, sender_id, body) VALUES (?, ?, ?, ?)",
            [$conversationId, $senderType, $senderId, $body]
        );
        $id = $this->lastInsertId();
        $this->execute("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?", [$conversationId]);
        return $id;
    }

    public function messagesAfter(int $conversationId, int $afterId = 0): array
    {
        return $this->fetchAll(
            "SELECT m.id, m.sender_type, m.body, m.created_at, u.name AS sender_name, u.rank AS sender_rank
             FROM chat_messages m
             LEFT JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ? AND m.id > ?
             ORDER BY m.id ASC LIMIT " . self::MAX_POLL,
            [$conversationId, $afterId]
        );
    }

    /**
     * Daftar percakapan untuk konsol Omnichat.
     * $scope: 'open' (menunggu + aktif), 'WAITING', 'ACTIVE', 'CLOSED', 'mine'
     */
    public function listForConsole(string $scope, int $agentId): array
    {
        [$where, $params] = match ($scope) {
            'WAITING', 'ACTIVE', 'CLOSED' => ["c.status = ?", [$scope]],
            'mine' => ["c.assigned_agent_id = ? AND c.status <> 'CLOSED'", [$agentId]],
            default => ["c.status <> 'CLOSED'", []],
        };

        // Pesan terakhir & jumlah pesan warga yang belum dibalas (setelah balasan agen terakhir)
        return $this->fetchAll(
            "SELECT c.id, c.channel, c.visitor_name, c.visitor_phone, c.status, c.assigned_agent_id,
                    c.created_at, c.last_message_at, c.closed_at,
                    u.name AS agent_name, u.rank AS agent_rank, r.ticket_number AS report_ticket,
                    (SELECT m.body FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                    (SELECT m.sender_type FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_sender,
                    (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.id AND m.sender_type = 'VISITOR'
                        AND m.id > COALESCE((SELECT MAX(a.id) FROM chat_messages a WHERE a.conversation_id = c.id AND a.sender_type = 'AGENT'), 0)
                    ) AS unanswered
             FROM chat_conversations c
             LEFT JOIN users u ON u.id = c.assigned_agent_id
             LEFT JOIN reports r ON r.id = c.report_id
             WHERE {$where}
             ORDER BY (c.status = 'WAITING') DESC, c.last_message_at DESC, c.id DESC
             LIMIT 100",
            $params
        );
    }

    public function countWaiting(): int
    {
        return (int)($this->fetch("SELECT COUNT(*) AS n FROM chat_conversations WHERE status = 'WAITING'")['n'] ?? 0);
    }

    /**
     * Ambil alih percakapan yang masih menunggu (atomik: hanya satu agen yang berhasil)
     */
    public function claim(int $conversationId, int $agentId): bool
    {
        return $this->affected(
            "UPDATE chat_conversations SET status = 'ACTIVE', assigned_agent_id = ? WHERE id = ? AND status = 'WAITING'",
            [$agentId, $conversationId]
        ) === 1;
    }

    public function close(int $conversationId, string $closedBy): bool
    {
        return $this->affected(
            "UPDATE chat_conversations SET status = 'CLOSED', closed_by = ?, closed_at = NOW()
             WHERE id = ? AND status <> 'CLOSED'",
            [$closedBy, $conversationId]
        ) === 1;
    }

    /**
     * Tautkan tiket hasil konversi (sekali saja per percakapan)
     */
    public function attachReport(int $conversationId, int $reportId): bool
    {
        return $this->affected(
            "UPDATE chat_conversations SET report_id = ? WHERE id = ? AND report_id IS NULL",
            [$reportId, $conversationId]
        ) === 1;
    }

    /**
     * Isi pesan warga sebagai kronologi laporan hasil konversi
     */
    public function visitorTranscript(int $conversationId): string
    {
        $rows = $this->fetchAll(
            "SELECT body, created_at FROM chat_messages WHERE conversation_id = ? AND sender_type = 'VISITOR' ORDER BY id ASC",
            [$conversationId]
        );
        return implode("\n", array_map(fn($r) => '[' . date('d/m H:i', strtotime($r['created_at'])) . '] ' . $r['body'], $rows));
    }
}
