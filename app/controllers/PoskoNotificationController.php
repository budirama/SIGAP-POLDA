<?php
/**
 * SIGAP-POLRI: Notifikasi Posko dalam aplikasi (Tahap 5 — P5-05)
 * Route: /posko-notifikasi
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\PoskoAccess;
use App\Core\Session;
use App\Config\Constants;
use App\Models\PoskoNotification;

class PoskoNotificationController extends Controller
{
    private PoskoNotification $notifications;

    public function __construct()
    {
        // Notifikasi hanya untuk akun posko (System Administrator tidak memiliki notifikasi operasional)
        Auth::requireRole(Constants::POSKO_ROLES);
        $this->notifications = new PoskoNotification();
    }

    /**
     * Daftar semua notifikasi pengguna
     */
    public function index(): void
    {
        $this->render('posko/notifications', [
            'pageTitle' => 'Notifikasi Posko',
            'pager'     => $this->notifications->paginate((int)Auth::id(), (int)$this->get('page', 1)),
            'labels'    => PoskoNotification::TYPE_LABELS,
        ], 'admin');
    }

    /**
     * Ringkasan untuk ikon lonceng (polling ringan; klien mengirim header X-SIGAP-Poll)
     */
    public function summary(): void
    {
        $uid = (int)Auth::id();
        $this->json([
            'unread' => $this->notifications->unreadCount($uid),
            'items'  => $this->notifications->latest($uid, 6),
        ]);
    }

    /**
     * Tandai dibaca lalu buka tautan notifikasi (hanya milik sendiri)
     */
    public function open(): void
    {
        $this->requirePost();
        $row = $this->notifications->markRead((int)$this->post('id', 0), (int)Auth::id());
        $link = $row['link'] ?? null;
        // Tautan hanya path internal aplikasi
        $this->redirect($link && preg_match('#^/[A-Za-z0-9/_\-?=&.]*$#', $link) ? $link : '/posko-notifikasi');
    }

    public function readAll(): void
    {
        $this->requirePost();
        $this->notifications->markAllRead((int)Auth::id());
        Session::setFlash('success', 'Semua notifikasi ditandai sudah dibaca.');
        $this->redirect('/posko-notifikasi');
    }

    private function requirePost(): void
    {
        if (!$this->isPost() || !$this->validateCsrf()) {
            Session::setFlash('error', 'Permintaan tidak valid.');
            $this->redirect('/posko-notifikasi');
        }
    }
}
