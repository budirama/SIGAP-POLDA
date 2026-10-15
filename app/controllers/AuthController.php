<?php
/**
 * SIGAP-POLDA: Auth Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\User;
use App\Models\Throttle;
use App\Config\Config;
use App\Config\Constants;

class AuthController extends Controller
{
    // Hash bcrypt pengganti untuk akun yang tidak ditemukan (penyamaan waktu respons)
    private const DUMMY_HASH = '$2y$10$GhIIUf9MBrFOsjoYru3aruToxMcpxmfIGVIYlADoYzOIFqj4jcRce';

    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(): void
    {
        if (Auth::check()) {
            $this->redirectByRole(Auth::role());
        }

        $this->render('auth/login', [
            'pageTitle' => 'Masuk Backoffice & Personil POLDA'
        ], 'none');
    }

    public function authenticate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/auth/login');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token keamanan CSRF tidak valid. Silakan coba lagi.');
            $this->redirect('/auth/login');
        }

        $identifier = $this->post('identifier', '');
        $password = $this->post('password', '');

        if (empty($identifier) || empty($password)) {
            Session::setFlash('error', 'NRP/Email dan Password wajib diisi.');
            $this->redirect('/auth/login');
        }

        $throttle = new Throttle();
        $idKey = mb_strtolower($identifier);
        $ip = Throttle::clientIp();
        if ($throttle->tooMany('login_id', $idKey) || $throttle->tooMany('login_ip', $ip)) {
            Session::setFlash('error', 'Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam '
                . Throttle::windowMinutes('login_id') . ' menit.');
            $this->redirect('/auth/login');
        }

        $user = $this->userModel->findByEmailOrNrp($identifier);

        // password_verify selalu dijalankan agar waktu respons tidak membocorkan keberadaan akun
        $hash = $user['password_hash'] ?? self::DUMMY_HASH;
        $valid = password_verify($password, $hash) && $user !== null;

        if (!$valid) {
            $throttle->hit('login_id', $idKey);
            $throttle->hit('login_ip', $ip);
            Session::setFlash('error', 'Kombinasi NRP/Email atau Password tidak sesuai.');
            $this->redirect('/auth/login');
        }

        $throttle->clear('login_id', $idKey);

        Auth::login($user);
        Session::setFlash('success', "Selamat datang kembali, {$user['rank']} {$user['name']}!");
        $this->redirectByRole($user['role']);
    }

    /**
     * Switch Role Cepat untuk Keperluan Uji Coba Tim / Pair Programming
     */
    public function quickLogin(string $roleKey = ''): void
    {
        // Hanya tersedia di lingkungan development
        if (!Config::isDevelopment()) {
            Session::setFlash('error', 'Login cepat uji coba tidak tersedia.');
            $this->redirect('/auth/login');
        }

        $roleMap = [
            'superadmin' => 'superadmin@sigap.polda.go.id',
            'kapolda'    => 'kapolda@sigap.polda.go.id',
            'spkt'       => 'spkt@sigap.polda.go.id',
            'humas'      => 'humas@sigap.polda.go.id',
            'kasatker'   => 'dirkrimum@sigap.polda.go.id',
            'kanit'      => 'kanit.krimum@sigap.polda.go.id',
            'penyidik'   => 'penyidik.rian@sigap.polda.go.id',
            'itwasda'    => 'irwasda@sigap.polda.go.id',
            'warga'      => 'budi.santoso95@gmail.com'
        ];

        if (!isset($roleMap[$roleKey])) {
            Session::setFlash('error', 'Role uji coba tidak ditemukan.');
            $this->redirect('/auth/login');
        }

        $user = $this->userModel->findByEmailOrNrp($roleMap[$roleKey]);
        if ($user) {
            Auth::login($user);
            Session::setFlash('success', "Login cepat aktif sebagai: " . Constants::getRoleLabel($user['role']));
            $this->redirectByRole($user['role']);
        }

        $this->redirect('/auth/login');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::setFlash('success', 'Anda telah berhasil keluar dari sistem dinas.');
        $this->redirect('/auth/login');
    }

    private function redirectByRole(?string $role): void
    {
        match ($role) {
            Constants::ROLE_EXECUTIVE => $this->redirect('/executive'),
            Constants::ROLE_CYBER_HUMAS => $this->redirect('/social'),
            Constants::ROLE_SPKT_OPERATOR, Constants::ROLE_SUPERADMIN => $this->redirect('/spkt'),
            Constants::ROLE_SATKER_HEAD, Constants::ROLE_SATKER_ADMIN => $this->redirect('/satker'),
            Constants::ROLE_INVESTIGATOR => $this->redirect('/investigator'),
            Constants::ROLE_INTERNAL_AFFAIRS => $this->redirect('/spkt'),
            Constants::ROLE_CITIZEN => $this->redirect('/report/track'),
            default => $this->redirect('/')
        };
    }
}
