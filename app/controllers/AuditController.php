<?php
/**
 * SIGAP-POLDA: Audit Controller (Jejak Audit untuk Superadmin & Pengawas Internal)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Config\Constants;
use App\Models\AuditLog;

class AuditController extends Controller
{
    private AuditLog $auditModel;

    public function __construct()
    {
        Auth::requireRole([
            Constants::ROLE_SUPERADMIN,
            Constants::ROLE_INTERNAL_AFFAIRS
        ]);

        $this->auditModel = new AuditLog();
    }

    public function index(): void
    {
        $action = (string)$this->get('action', '');
        $keyword = (string)$this->get('q', '');

        $this->render('backoffice/audit_log', [
            'pageTitle' => 'Jejak Audit Akses & Tindakan Personil',
            'logs'      => $this->auditModel->search($action, $keyword),
            'actions'   => $this->auditModel->actions(),
            'action'    => $action,
            'keyword'   => $keyword
        ], 'admin');
    }
}
