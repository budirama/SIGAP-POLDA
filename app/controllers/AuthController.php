<?php
/**
 * SIGAP-POLRI: Auth Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
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
            'pageTitle' => 'Masuk Backoffice & Personil POLRI'
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
            Audit::log('auth.login_locked', null, 'Identitas: ' . $identifier);
            Session::setFlash('error', 'Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam '
                . Throttle::windowMinutes('login_id') . ' menit.');
            $this->redirect('/auth/login');
        }

        $user = $this->userModel->findByEmailOrNrp($identifier);

        // password_verify selalu dijalankan agar waktu respons tidak membocorkan keberadaan akun
        $hash = $user['password_hash'] ?? self::DUMMY_HASH;
        $valid = password_verify($password, $hash) && $user !== null;

        if (!$valid) {
            Audit::log('auth.login_failed', null, 'Identitas: ' . $identifier);
            $throttle->hit('login_id', $idKey);
            $throttle->hit('login_ip', $ip);
            Session::setFlash('error', 'Kombinasi NRP/Email atau Password tidak sesuai.');
            $this->redirect('/auth/login');
        }

        $throttle->clear('login_id', $idKey);

        Auth::login($user);
        Audit::log('auth.login');
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
            'superadmin' => 'superadmin@sigap.polri.go.id',
            'kapolri'    => 'kapolri@sigap.polri.go.id',
            'spkt'       => 'spkt@sigap.polri.go.id',
            'humas'      => 'humas@sigap.polri.go.id',
            'kasatker'   => 'dirtipidum@sigap.polri.go.id',
            'kanit'      => 'kanit.tipidum@sigap.polri.go.id',
            'penyidik'   => 'penyidik.rian@sigap.polri.go.id',
            'itwasum'    => 'itwasum@sigap.polri.go.id',
            'warga'      => 'budi.santoso95@gmail.com',
            // Management Posko (akun demo per tingkat)
            'poskoAdminPusat' => 'posko.admin.pusat@sigap.polri.go.id',
            'poskoOpPusat'    => 'posko.op.pusat@sigap.polri.go.id',
            'poskoExecPusat'  => 'posko.exec.pusat@sigap.polri.go.id',
            'poskoAdminJatim' => 'posko.admin.jatim@sigap.polri.go.id',
            'poskoOpJatim'    => 'posko.op.jatim@sigap.polri.go.id',
            'poskoExecJatim'  => 'posko.exec.jatim@sigap.polri.go.id',
            'poskoAdminSby'   => 'posko.admin.surabaya@sigap.polri.go.id',
            'poskoOpSby'      => 'posko.op.surabaya@sigap.polri.go.id',
            'poskoExecSby'    => 'posko.exec.surabaya@sigap.polri.go.id',
            'poskoOpBenowo'   => 'posko.op.benowo@sigap.polri.go.id',
            'poskoOpGubeng'   => 'posko.op.gubeng@sigap.polri.go.id',
            'poskoOpMetro'    => 'posko.op.metro@sigap.polri.go.id',
            'poskoOpJaksel'   => 'posko.op.jaksel@sigap.polri.go.id',
            'poskoOpSidoarjo' => 'posko.op.sidoarjo@sigap.polri.go.id',
        ];

        if (!isset($roleMap[$roleKey])) {
            Session::setFlash('error', 'Role uji coba tidak ditemukan.');
            $this->redirect('/auth/login');
        }

        $user = $this->userModel->findByEmailOrNrp($roleMap[$roleKey]);
        if ($user) {
            Auth::login($user);
            Audit::log('auth.quick_login', null, 'Login cepat uji coba (development)');
            Session::setFlash('success', "Login cepat aktif sebagai: " . Constants::getRoleLabel($user['role']));
            $this->redirectByRole($user['role']);
        }

        $this->redirect('/auth/login');
    }

    public function logout(): void
    {
        Audit::log('auth.logout');
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
            Constants::ROLE_POSKO_ADMIN, Constants::ROLE_POSKO_OPERATOR, Constants::ROLE_POSKO_EXECUTIVE => $this->redirect('/posko'),
            default => $this->redirect('/')
        };
    }
}
