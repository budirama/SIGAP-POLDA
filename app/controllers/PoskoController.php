<?php
/**
 * SIGAP-POLRI: Posko Controller (Management Posko — Tahap 1: organisasi, pengguna & hak akses berjenjang)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\PoskoAccess;
use App\Core\Session;
use App\Config\Constants;
use App\Models\PoskoDashboard;
use App\Models\Satker;
use App\Models\User;

class PoskoController extends Controller
{
    private const PASSWORD_MIN = 10;
    private const UNIT_TYPES = ['POLDA', 'POLRES', 'POLSEK'];

    private Satker $satkers;
    private User $users;

    public function __construct()
    {
        Auth::requireRole(array_merge(Constants::POSKO_ROLES, [Constants::ROLE_SUPERADMIN]));
        $this->satkers = new Satker();
        $this->users = new User();

        // Akun dengan kata sandi sementara wajib menggantinya sebelum memakai modul
        $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (PoskoAccess::mustChangePassword() && !str_ends_with(rtrim($path, '/'), '/posko/password')) {
            Session::setFlash('error', 'Demi keamanan, ganti kata sandi sementara Anda terlebih dahulu.');
            $this->redirect('/posko/password');
        }
    }

    /**
     * Beranda posko: identitas unit, rantai komando, dan unit di bawahnya
     */
    public function index(): void
    {
        $this->renderUnit($this->homeUnitId());
    }

    /**
     * Drill-down ke unit lain di dalam wilayah pengguna
     */
    public function unit(int $id): void
    {
        if (!PoskoAccess::canViewUnit($id)) {
            Session::setFlash('error', 'Unit tidak ditemukan atau di luar wilayah kewenangan Anda.');
            $this->redirect('/posko');
        }
        $this->renderUnit($id);
    }

    // ------------------------------------------------------------------ Pengguna

    public function users(): void
    {
        $this->requireUserAdmin();
        $scope = PoskoAccess::scopeSatkerIds();
        $pager = $this->users->paginatePoskoUsers($scope, trim((string)$this->get('search', '')), (int)$this->get('page', 1));

        $this->render('posko/users', [
            'pageTitle' => 'Manajemen Pengguna Posko',
            'pager'     => $pager,
            'search'    => (string)$this->get('search', ''),
            'units'     => $this->unitOptions($scope),
            'roles'     => Constants::POSKO_ROLES,
            'tempPass'  => Session::getFlash('temp_password')[0] ?? null,
        ], 'admin');
    }

    public function userStore(): void
    {
        $this->requirePost('/posko/users');
        $this->requireUserAdmin();

        $data = [
            'nrp'       => preg_replace('/\D/', '', (string)$this->post('nrp', '')),
            'name'      => mb_substr(trim((string)$this->post('name', '')), 0, 100),
            'email'     => strtolower(trim((string)$this->post('email', ''))),
            'phone'     => preg_replace('/[^0-9+]/', '', (string)$this->post('phone', '')),
            'role'      => (string)$this->post('role', ''),
            'satker_id' => (int)$this->post('satker_id', 0),
            'rank'      => mb_substr(trim((string)$this->post('rank', '')), 0, 50),
            'position'  => mb_substr(trim((string)$this->post('position', '')), 0, 100),
        ];

        $error = match (true) {
            strlen($data['nrp']) < 6 || strlen($data['nrp']) > 20 => 'NRP harus 6–20 digit.',
            $data['name'] === '' => 'Nama wajib diisi.',
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 100 => 'Email tidak valid.',
            !preg_match('/^\+?[0-9]{8,15}$/', $data['phone']) => 'Nomor HP wajib diisi (8–15 digit); dipakai juga sebagai identitas login.',
            !in_array($data['role'], Constants::POSKO_ROLES, true) => 'Role posko tidak valid.',
            !PoskoAccess::canManageUsersOf($data['satker_id']) => 'Unit di luar wilayah kewenangan Anda.',
            !($unit = $this->satkers->findById($data['satker_id'])) || !(int)$unit['is_active']
                || $unit['type'] === 'DIREKTORAT' => 'Unit tidak aktif atau bukan unit posko.',
            $this->users->identityTaken($data['email'], $data['nrp'], $data['phone']) => 'Email, NRP, atau nomor HP sudah dipakai akun lain.',
            default => null,
        };
        if ($error) {
            Session::setFlash('error', $error);
            $this->redirect('/posko/users');
        }

        $temp = $this->temporaryPassword();
        $id = $this->users->createPoskoUser($data, $temp);
        Audit::log('posko.user.create', null, "Akun #{$id} {$data['role']} @ satker #{$data['satker_id']}");

        Session::setFlash('success', "Akun {$data['name']} dibuat. Sampaikan kata sandi sementara secara aman; wajib diganti saat login pertama.");
        Session::setFlash('temp_password', $temp);
        $this->redirect('/posko/users');
    }

    public function userToggle(): void
    {
        $this->requirePost('/posko/users');
        $target = $this->managedUser();
        if ((int)$target['id'] === Auth::id()) {
            Session::setFlash('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
            $this->redirect('/posko/users');
        }

        $active = !(int)$target['is_active'];
        $this->users->setActive((int)$target['id'], $active);
        Audit::log($active ? 'posko.user.enable' : 'posko.user.disable', null, "Akun #{$target['id']}");
        Session::setFlash('success', "Akun {$target['name']} " . ($active ? 'diaktifkan kembali.' : 'dinonaktifkan; sesi aktifnya segera berakhir.'));
        $this->redirect('/posko/users');
    }

    public function userReset(): void
    {
        $this->requirePost('/posko/users');
        $target = $this->managedUser();

        $temp = $this->temporaryPassword();
        $this->users->setPassword((int)$target['id'], $temp, true);
        Audit::log('posko.user.reset_password', null, "Akun #{$target['id']}");

        Session::setFlash('success', "Kata sandi {$target['name']} direset. Sampaikan kata sandi sementara secara aman.");
        Session::setFlash('temp_password', $temp);
        $this->redirect('/posko/users');
    }

    // ------------------------------------------------------------------ Organisasi

    public function organizations(int $id = 1): void
    {
        $this->requireOrgAdmin();
        $unit = $this->satkers->findById($id);
        if (!$unit) {
            $this->redirect('/posko/organizations');
        }

        $childType = match ($unit['type']) {
            'MABES' => 'POLDA',
            'POLDA' => 'POLRES',
            'POLRES' => 'POLSEK',
            default => null,
        };

        $this->render('posko/organizations', [
            'pageTitle'  => 'Manajemen Organisasi Posko',
            'unit'       => $unit,
            'ancestors'  => $this->satkers->ancestors($id),
            'children'   => array_values(array_filter($this->satkers->childrenWithStats($id), fn($c) => $c['type'] !== 'DIREKTORAT')),
            'childType'  => $childType,
            'moveTargets' => $childType ? $this->satkers->parentCandidates($childType) : [],
        ], 'admin');
    }

    public function orgStore(): void
    {
        $this->requirePost('/posko/organizations');
        $this->requireOrgAdmin();

        $type = (string)$this->post('type', '');
        $parentId = (int)$this->post('parent_id', 0);
        [$code, $name, $address] = $this->unitIdentity();

        $error = match (true) {
            !in_array($type, self::UNIT_TYPES, true) => 'Tipe satuan tidak valid.',
            $name === '' || $code === '' => 'Kode dan nama satuan wajib diisi.',
            !preg_match('/^[A-Z0-9-]{3,50}$/', $code) => 'Kode satuan hanya huruf kapital, angka, dan tanda strip (3–50 karakter).',
            $this->satkers->codeTaken($code) => 'Kode satuan sudah dipakai.',
            default => $this->satkers->validateParent($type, $parentId),
        };
        if ($error) {
            Session::setFlash('error', $error);
            $this->redirect("/posko/organizations/{$parentId}");
        }

        $this->satkers->beginTransaction();
        try {
            $id = $this->satkers->create($type, $code, $name, $parentId, $address);
            $this->satkers->commit();
        } catch (\Throwable $e) {
            $this->satkers->rollBack();
            throw $e;
        }

        Audit::log('posko.org.create', null, "Satker #{$id} {$type} {$code} di bawah #{$parentId}");
        Session::setFlash('success', "Satuan {$name} ditambahkan.");
        $this->redirect("/posko/organizations/{$parentId}");
    }

    public function orgUpdate(): void
    {
        $this->requirePost('/posko/organizations');
        $this->requireOrgAdmin();
        $unit = $this->editableUnit();
        [$code, $name, $address] = $this->unitIdentity();

        $error = match (true) {
            $name === '' || $code === '' => 'Kode dan nama satuan wajib diisi.',
            !preg_match('/^[A-Z0-9-]{3,50}$/', $code) => 'Kode satuan hanya huruf kapital, angka, dan tanda strip (3–50 karakter).',
            $this->satkers->codeTaken($code, (int)$unit['id']) => 'Kode satuan sudah dipakai.',
            default => null,
        };
        if ($error) {
            Session::setFlash('error', $error);
            $this->redirect("/posko/organizations/{$unit['parent_id']}");
        }

        $this->satkers->updateIdentity((int)$unit['id'], $code, $name, $address);
        Audit::log('posko.org.update', null, "Satker #{$unit['id']} → {$code}");
        Session::setFlash('success', "Data satuan {$name} diperbarui.");
        $this->redirect("/posko/organizations/{$unit['parent_id']}");
    }

    public function orgMove(): void
    {
        $this->requirePost('/posko/organizations');
        $this->requireOrgAdmin();
        $unit = $this->editableUnit();
        $newParent = (int)$this->post('parent_id', 0);

        $error = $this->satkers->validateParent($unit['type'], $newParent)
            ?? ($this->satkers->isWithin($newParent, (int)$unit['id']) ? 'Satuan tidak dapat dipindah ke bawah dirinya sendiri.' : null);
        if ($error) {
            Session::setFlash('error', $error);
            $this->redirect("/posko/organizations/{$unit['parent_id']}");
        }

        $this->satkers->beginTransaction();
        try {
            $this->satkers->move((int)$unit['id'], $newParent);
            $this->satkers->commit();
        } catch (\Throwable $e) {
            $this->satkers->rollBack();
            throw $e;
        }

        Audit::log('posko.org.move', null, "Satker #{$unit['id']} dari #{$unit['parent_id']} ke #{$newParent}");
        Session::setFlash('success', "{$unit['name']} dipindahkan; jalur hierarki seluruh unit di bawahnya diperbarui.");
        $this->redirect("/posko/organizations/{$newParent}");
    }

    public function orgToggle(): void
    {
        $this->requirePost('/posko/organizations');
        $this->requireOrgAdmin();
        $unit = $this->editableUnit();

        $active = !(int)$unit['is_active'];
        $this->satkers->setActive((int)$unit['id'], $active);
        Audit::log($active ? 'posko.org.enable' : 'posko.org.disable', null, "Satker #{$unit['id']}");
        Session::setFlash('success', "{$unit['name']} " . ($active
            ? 'diaktifkan kembali.'
            : 'dinonaktifkan; tidak lagi wajib lapor, riwayatnya tetap tersimpan.'));
        $this->redirect("/posko/organizations/{$unit['parent_id']}");
    }

    // ------------------------------------------------------------------ Kata sandi

    public function password(): void
    {
        if (!$this->isPost()) {
            $this->render('posko/password', [
                'pageTitle' => 'Ganti Kata Sandi',
                'forced'    => !empty(Auth::user()['must_change_password']),
            ], 'admin');
            return;
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/posko/password');
        }

        $current = (string)$this->post('current_password', '');
        $new = (string)$this->post('new_password', '');
        $hash = $this->users->passwordHash((int)Auth::id());

        $error = match (true) {
            !$hash || !password_verify($current, $hash) => 'Kata sandi saat ini salah.',
            strlen($new) < self::PASSWORD_MIN => 'Kata sandi baru minimal ' . self::PASSWORD_MIN . ' karakter.',
            !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new) => 'Kata sandi baru harus memuat huruf dan angka.',
            $new !== (string)$this->post('confirm_password', '') => 'Konfirmasi kata sandi tidak cocok.',
            password_verify($new, $hash) => 'Kata sandi baru harus berbeda dari yang lama.',
            default => null,
        };
        if ($error) {
            Session::setFlash('error', $error);
            $this->redirect('/posko/password');
        }

        $this->users->setPassword((int)Auth::id(), $new, false);
        Session::regenerate();
        Audit::log('posko.user.change_password');
        Session::setFlash('success', 'Kata sandi berhasil diganti.');
        $this->redirect('/posko');
    }

    // ------------------------------------------------------------------ Helper

    private function renderUnit(int $id): void
    {
        $unit = $this->satkers->findById($id);
        $scope = PoskoAccess::scopeSatkerIds();
        // Satker direktorat Mabes adalah unit SIGAP (penanganan aduan), bukan unit posko
        $children = array_values(array_filter(
            $this->satkers->childrenWithStats($id),
            fn($c) => in_array((int)$c['id'], $scope, true) && $c['type'] !== 'DIREKTORAT'
        ));

        // Leluhur di luar wilayah pengguna tetap ditampilkan sebagai teks (tanpa tautan)
        $ancestors = array_map(fn($a) => $a + ['linkable' => in_array((int)$a['id'], $scope, true)], $this->satkers->ancestors($id));

        // Dashboard: unit ini + turunannya (id sudah divalidasi berada dalam wilayah pengguna)
        $dashScope = $this->satkers->selfAndDescendantIds($id);
        $dash = new PoskoDashboard();
        $statusByChild = [];
        foreach ($dash->childrenStatus($id) as $row) {
            $statusByChild[$row['id']] = $row;
        }

        $this->render('posko/home', [
            'pageTitle'  => 'Management Posko',
            'unit'       => $unit,
            'isHome'     => $id === $this->homeUnitId(),
            'ancestors'  => $ancestors,
            'children'   => $children,
            'levelCounts' => $this->levelCounts($dashScope),
            'roleLabel'  => PoskoAccess::isPoskoUser()
                ? Constants::poskoRoleLabel((string)Auth::role(), (int)PoskoAccess::level())
                : 'System Administrator',
            'cards'      => $dash->cards($dashScope),
            'periods'    => $dash->activePeriods(),
            'recent'     => $dash->recent($dashScope),
            'events'     => $dash->importantEvents($dashScope),
            'trend'      => $dash->trend($dashScope),
            'childStatus' => $statusByChild,
            'actions'    => PoskoAccess::isOperational() && $id === $this->homeUnitId() ? $dash->actionsFor($id) : null,
        ], 'admin');
    }

    private function homeUnitId(): int
    {
        return PoskoAccess::isPoskoUser() ? (int)Auth::satkerId() : 1;
    }

    /**
     * Jumlah unit aktif per tingkat di dalam sekumpulan id
     *
     * @param int[] $ids
     */
    private function levelCounts(array $ids): array
    {
        $counts = [Constants::LEVEL_POLDA => 0, Constants::LEVEL_POLRES => 0, Constants::LEVEL_POLSEK => 0];
        if (!$ids) {
            return $counts;
        }
        foreach ($this->satkers->getAll() as $s) {
            if ((int)$s['is_active'] && in_array((int)$s['id'], $ids, true) && isset($counts[(int)$s['level']])
                && $s['type'] !== 'DIREKTORAT') {
                $counts[(int)$s['level']]++;
            }
        }
        return $counts;
    }

    /**
     * Pilihan unit untuk formulir akun (hanya unit aktif dalam wilayah, tanpa satker direktorat)
     *
     * @param int[] $scope
     */
    private function unitOptions(array $scope): array
    {
        return array_values(array_filter(
            $this->satkers->getAll(),
            fn($s) => in_array((int)$s['id'], $scope, true) && (int)$s['is_active'] && $s['type'] !== 'DIREKTORAT'
        ));
    }

    private function managedUser(): array
    {
        $this->requireUserAdmin();
        $target = $this->users->findById((int)$this->post('user_id', 0));
        if (!$target || !in_array($target['role'], Constants::POSKO_ROLES, true)
            || !PoskoAccess::canManageUsersOf((int)$target['satker_id'])) {
            Session::setFlash('error', 'Akun tidak ditemukan atau di luar wilayah kewenangan Anda.');
            $this->redirect('/posko/users');
        }
        return $target;
    }

    private function editableUnit(): array
    {
        $unit = $this->satkers->findById((int)$this->post('satker_id', 0));
        if (!$unit || !in_array($unit['type'], self::UNIT_TYPES, true)) {
            Session::setFlash('error', 'Satuan tidak ditemukan atau tidak dapat diubah dari menu ini.');
            $this->redirect('/posko/organizations');
        }
        return $unit;
    }

    private function unitIdentity(): array
    {
        return [
            strtoupper(trim((string)$this->post('code', ''))),
            mb_substr(trim((string)$this->post('name', '')), 0, 150),
            ($a = mb_substr(trim((string)$this->post('address', '')), 0, 255)) !== '' ? $a : null,
        ];
    }

    private function requireUserAdmin(): void
    {
        if (!PoskoAccess::isSystemAdmin() && Auth::role() !== Constants::ROLE_POSKO_ADMIN) {
            Session::setFlash('error', 'Hanya Admin Posko yang dapat mengelola akun pengguna.');
            $this->redirect('/posko');
        }
    }

    private function requireOrgAdmin(): void
    {
        if (!PoskoAccess::canManageOrganization()) {
            Session::setFlash('error', 'Hanya System Administrator & Admin Posko Pusat yang dapat mengelola organisasi.');
            $this->redirect('/posko');
        }
    }

    private function requirePost(string $back): void
    {
        if (!$this->isPost()) {
            $this->redirect($back);
        }
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect($back);
        }
    }

    private function temporaryPassword(): string
    {
        // Tanpa karakter ambigu (0/O, 1/l/I); selalu memuat huruf & angka
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $pass = '';
            for ($i = 0; $i < 12; $i++) {
                $pass .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass));
        return $pass;
    }
}
