<?php
/**
 * SIGAP-POLRI: Profil Pejabat (Superadmin TIK)
 *
 * Mengubah nama, pangkat, dan jabatan akun personil — misalnya mengisi pejabat yang sedang
 * menjabat saat sistem dioperasikan resmi. Identitas login (email, NRP, no. HP) dan role
 * tidak dapat diubah dari menu ini.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Constants;
use App\Models\User;

class PejabatController extends Controller
{
    private User $users;

    public function __construct()
    {
        Auth::requireRole([Constants::ROLE_SUPERADMIN]);
        $this->users = new User();
    }

    public function index(): void
    {
        $editId = (int)$this->get('edit', 0);
        $editing = $editId > 0 ? $this->users->findById($editId) : null;
        if ($editing && $editing['role'] === Constants::ROLE_CITIZEN) {
            $editing = null;
        }

        $this->render('backoffice/pejabat', [
            'pageTitle' => 'Profil Pejabat & Personil',
            'search'    => mb_substr((string)$this->get('q', ''), 0, 100),
            'people'    => $this->users->listPersonnel(mb_substr((string)$this->get('q', ''), 0, 100)),
            'editing'   => $editing,
            'ranks'     => User::POLRI_RANKS,
        ], 'admin');
    }

    public function save(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/pejabat');
        }
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/pejabat');
        }

        $id = (int)$this->post('id', 0);
        $before = $this->users->findById($id);
        $name = trim((string)$this->post('name', ''));
        $rank = trim((string)$this->post('rank', ''));
        $position = trim((string)$this->post('position', ''));

        $error = match (true) {
            !$before || $before['role'] === Constants::ROLE_CITIZEN => 'Akun personil tidak ditemukan.',
            mb_strlen($name) < 3 || mb_strlen($name) > 150 => 'Nama wajib diisi (3–150 karakter).',
            $rank !== '' && !in_array($rank, User::POLRI_RANKS, true) => 'Pilih pangkat dari daftar pangkat Polri.',
            mb_strlen($position) > 100 => 'Jabatan maksimal 100 karakter.',
            default => null,
        };
        if ($error !== null) {
            Session::setFlash('error', $error);
            $this->redirect('/pejabat' . ($before ? "?edit={$id}" : ''));
        }

        $this->users->updateProfile($id, $name, $rank !== '' ? $rank : null, $position !== '' ? $position : null);
        Audit::log('user.profile', null, sprintf(
            'Profil #%d (%s): %s %s / %s → %s %s / %s',
            $id, $before['email'] ?? $before['nrp'],
            $before['rank'] ?? '-', $before['name'], $before['position'] ?? '-',
            $rank ?: '-', $name, $position ?: '-'
        ));
        Session::setFlash('success', 'Profil ' . trim(($rank ? $rank . ' ' : '') . $name) . ' tersimpan. Identitas login tidak berubah.');
        $this->redirect('/pejabat');
    }
}
