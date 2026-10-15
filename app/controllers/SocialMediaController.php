<?php
/**
 * SIGAP-POLDA: Social Media Controller (Omnichannel Ingestion & Triage)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Config;
use App\Config\Constants;
use App\Models\SocialFeed;
use App\Models\Report;

class SocialMediaController extends Controller
{
    private SocialFeed $socialModel;
    private Report $reportModel;

    public function __construct()
    {
        Auth::requireRole([
            Constants::ROLE_CYBER_HUMAS,
            Constants::ROLE_SPKT_OPERATOR,
            Constants::ROLE_SUPERADMIN
        ]);

        $this->socialModel = new SocialFeed();
        $this->reportModel = new Report();
    }

    /**
     * Tampilan Antrean Pengaduan Masuk dari Media Sosial
     */
    public function index(): void
    {
        $feeds = $this->socialModel->getAllFeeds();

        $this->render('backoffice/social_triage', [
            'pageTitle' => 'Omnichannel Media Sosial (X, Instagram, Facebook, TikTok)',
            'feeds'     => $feeds
        ], 'admin');
    }

    /**
     * Konversi Cepat 1-Klik Postingan Medsos Menjadi Laporan Polisi Resmi
     */
    public function convert(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/social');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/social');
        }

        $feedId = (int)$this->post('feed_id', 0);
        $feed = $this->socialModel->findById($feedId);

        if (!$feed) {
            Session::setFlash('error', 'Postingan media sosial tidak ditemukan.');
            $this->redirect('/social');
        }

        if ($feed['status'] !== 'NEW') {
            Session::setFlash('error', 'Postingan ini sudah diproses sebelumnya (dikonversi atau diabaikan).');
            $this->redirect('/social');
        }

        $categoryId = (int)$this->post('category_id', 1); // Default Kriminal Umum
        $validCategories = array_map('intval', array_column($this->reportModel->getCategories(), 'id'));
        if (!in_array($categoryId, $validCategories, true)) {
            Session::setFlash('error', 'Kategori perkara tidak valid.');
            $this->redirect('/social');
        }
        $location = $this->post('incident_location', 'Wilayah Hukum Polda Metro Jaya');
        $title = $this->post('title', "Pengaduan Medsos {$feed['platform']}: @" . $feed['author_username']);
        $description = $feed['post_content'];

        try {
            $ticketNumber = $this->reportModel->createReport([
                'source_channel'    => $feed['platform'],
                'social_feed_id'    => $feed['id'],
                'social_post_url'   => SocialFeed::isSafeUrl($feed['post_url']) ? $feed['post_url'] : null,
                'actor_id'          => Auth::id(),
                'reporter_id'       => null,
                'is_anonymous'      => 0,
                'reporter_name_raw' => $feed['author_name'] ?: $feed['author_username'],
                'reporter_nik_raw'  => null,
                'reporter_phone'    => 'Medsos @' . $feed['author_username'],
                'category_id'       => $categoryId,
                'title'             => $title,
                'description'       => $description,
                'incident_date'     => date('Y-m-d'),
                'incident_time'     => date('H:i:s'),
                'incident_location' => $location,
                'urgency_level'     => 'HIGH' // Laporan medsos viral berkategori tinggi
            ]);

            Session::setFlash('success', "Berhasil! Postingan @{$feed['author_username']} telah dikonversi menjadi Tiket Resmi: {$ticketNumber}");
        } catch (\Throwable $e) {
            error_log('[SIGAP-POLDA] Gagal konversi postingan medsos: ' . $e);
            Session::setFlash('error', 'Gagal mengonversi postingan menjadi tiket. Silakan coba lagi.');
        }

        $this->redirect('/social');
    }

    /**
     * Tandai Postingan Medsos Sebagai Spam / Tidak Perlu Ditindaklanjuti
     */
    public function dismiss(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/social');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/social');
        }

        $feedId = (int)$this->post('feed_id', 0);
        if ($feedId > 0 && $this->socialModel->dismissFeed($feedId, Auth::id())) {
            Session::setFlash('success', 'Postingan telah ditandai sebagai spam / diabaikan.');
        } else {
            Session::setFlash('error', 'Postingan tidak ditemukan atau sudah diproses sebelumnya.');
        }

        $this->redirect('/social');
    }

    /**
     * Simulator Penerimaan Postingan Viral Baru (Untuk Uji Coba Demo)
     */
    public function simulate(): void
    {
        // Alat uji coba: hanya di lingkungan development (cegah injeksi postingan palsu di production)
        if (!Config::isDevelopment() || !$this->isPost()) {
            $this->redirect('/social');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/social');
        }

        $platform = $this->post('platform', Constants::CHANNEL_TWITTER);
        if (!in_array($platform, [Constants::CHANNEL_TWITTER, Constants::CHANNEL_IG, Constants::CHANNEL_FB, Constants::CHANNEL_TIKTOK], true)) {
            Session::setFlash('error', 'Platform media sosial tidak valid.');
            $this->redirect('/social');
        }
        $username = $this->post('username', 'warga_netizen');
        $content = $this->post('content', 'Lapor Pak ada pungli parkir liar meresahkan di depan stasiun!');
        $postUrl = $this->post('post_url', 'https://x.com/warga_netizen/status/' . time());
        if (!SocialFeed::isSafeUrl($postUrl)) {
            Session::setFlash('error', 'URL postingan harus diawali http:// atau https://.');
            $this->redirect('/social');
        }

        $this->socialModel->createMockFeed($platform, $username, $username, $content, $postUrl);

        Session::setFlash('success', "Simulasi webhook berhasil: 1 postingan baru dari {$platform} (@{$username}) telah masuk ke antrean.");
        $this->redirect('/social');
    }
}
