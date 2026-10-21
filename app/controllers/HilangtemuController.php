<?php
/**
 * SIGAP-POLRI: Hilang & Temu (Kehilangan / Temuan Kendaraan, Orang, Barang Berharga, Dokumen, Hewan)
 *
 * Publik  : /hilangtemu (galeri), /hilangtemu/lapor, /hilangtemu/item/{id} (+ klaim), /hilangtemu/status
 * Petugas : /hilangtemu/admin, /hilangtemu/detail/{id} (verifikasi, pencocokan, klaim, serah terima, SKTLK)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\ModuleManager;
use App\Core\Session;
use App\Core\Upload;
use App\Config\Constants;
use App\Models\LostFound;
use App\Models\Throttle;

class HilangtemuController extends Controller
{
    private const MAX_PHOTOS = 4;
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Petugas yang memverifikasi, mencocokkan, dan memutus klaim */
    private const OFFICER_ROLES = [
        Constants::ROLE_SPKT_OPERATOR,
        Constants::ROLE_SATKER_HEAD,
        Constants::ROLE_SATKER_ADMIN,
        Constants::ROLE_CYBER_HUMAS,
        Constants::ROLE_SUPERADMIN,
    ];

    /** Pimpinan & pengawas: hanya melihat */
    private const VIEW_ROLES = [
        Constants::ROLE_EXECUTIVE,
        Constants::ROLE_INTERNAL_AFFAIRS,
    ];

    /** Kolom ciri pengenal per kategori objek */
    private const CATEGORY_FIELDS = [
        'KENDARAAN'       => ['brand', 'color', 'plate_number', 'chassis_number', 'engine_number'],
        'ORANG'           => ['person_name', 'person_age', 'person_gender'],
        'BARANG_BERHARGA' => ['brand', 'color', 'serial_number'],
        'DOKUMEN'         => ['brand', 'color', 'serial_number'],
        'HEWAN'           => ['brand', 'color', 'animal_species'],
        'LAINNYA'         => ['brand', 'color', 'serial_number'],
    ];

    private LostFound $model;
    private Throttle $throttle;

    public function __construct()
    {
        if (class_exists(ModuleManager::class)) {
            ModuleManager::requireActive('op_lostfound');
        }
        $this->model = new LostFound();
        $this->throttle = new Throttle();
    }

    // ==================================================================
    // PUBLIK
    // ==================================================================

    /**
     * Galeri laporan kehilangan & temuan yang telah diverifikasi petugas
     */
    public function index(): void
    {
        $filters = $this->listFilters();
        $pager = $this->model->paginatePublic($filters, (int)$this->get('page', 1));

        $this->render('public/hilangtemu/index', [
            'pageTitle' => 'Hilang & Temu',
            'pager'     => $pager,
            'filters'   => $filters,
        ], 'main');
    }

    /**
     * Formulir laporan kehilangan / temuan (dipakai warga & petugas loket)
     */
    public function lapor(): void
    {
        $old = Session::get('_old_lf', []);
        Session::remove('_old_lf');

        $type = strtoupper((string)$this->get('type', $old['report_type'] ?? 'LOST'));
        $category = strtoupper((string)$this->get('category', $old['item_category'] ?? 'KENDARAAN'));

        $this->render('public/hilangtemu/form', [
            'pageTitle'     => 'Lapor Kehilangan / Temuan',
            'old'           => is_array($old) ? $old : [],
            'type'          => isset(LostFound::TYPES[$type]) ? $type : 'LOST',
            'category'      => isset(LostFound::CATEGORIES[$category]) ? $category : 'KENDARAAN',
            'isOfficer'     => $this->isOfficer(),
        ], 'main');
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/hilangtemu/lapor');
        }
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token keamanan CSRF tidak valid. Silakan ulangi pengisian.');
            $this->redirect('/hilangtemu/lapor');
        }

        $isOfficer = $this->isOfficer();
        $ip = Throttle::clientIp();
        if (!$isOfficer && $this->throttle->tooMany('lostfound_ip', $ip)) {
            Session::setFlash('error', 'Batas pengiriman laporan dari jaringan Anda tercapai. Silakan coba lagi dalam '
                . Throttle::windowMinutes('lostfound_ip') . ' menit.');
            $this->redirect('/hilangtemu/lapor');
        }

        $data = $this->collectItemInput();
        $error = $this->validateItemInput($data);
        if ($error !== null) {
            $this->failWithOld($error, $data);
        }

        $photos = [];
        $rejected = 0;
        if (!empty($_FILES['photos']['name'][0])) {
            $total = count($_FILES['photos']['name']);
            if ($total > self::MAX_PHOTOS) {
                $this->failWithOld('Maksimal ' . self::MAX_PHOTOS . ' foto per laporan.', $data);
            }
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $stored = Upload::store(
                    $_FILES['photos']['tmp_name'][$i],
                    $_FILES['photos']['error'][$i],
                    (int)$_FILES['photos']['size'][$i],
                    self::IMAGE_MIMES
                );
                if ($stored === null) {
                    $rejected++;
                    continue;
                }
                $photos[] = ['file_path' => $stored['file_path'], 'file_size_kb' => (int)round($stored['size'] / 1024)];
            }
        }

        if ($isOfficer) {
            $data['created_by_officer_id'] = Auth::id();
        }

        try {
            $result = $this->model->create($data, $photos);
        } catch (\Throwable $e) {
            error_log('[SIGAP-POLRI] Gagal membuat laporan Hilang & Temu: ' . $e);
            $this->failWithOld('Gagal menyimpan laporan. Silakan coba lagi atau hubungi Call Center 110.', $data);
        }

        if (!$isOfficer) {
            $this->throttle->hit('lostfound_ip', $ip);
        }
        Audit::log('lostfound.create', null, "{$result['code']} ({$data['report_type']}/{$data['item_category']})");

        $this->grantAccess($result['code']);
        Session::set('_lf_new', ['code' => $result['code'], 'pin' => $result['pin']]);
        if ($rejected > 0) {
            Session::setFlash('error', "{$rejected} foto tidak dapat dilampirkan (format harus JPG/PNG/WEBP, maksimal 20 MB per file).");
        }
        $this->redirect('/hilangtemu/sukses');
    }

    /**
     * Tanda terima: kode & PIN ditampilkan sekali setelah laporan dibuat
     */
    public function sukses(): void
    {
        $new = Session::get('_lf_new');
        Session::remove('_lf_new');
        $item = is_array($new) ? $this->model->findByCode($new['code']) : null;
        if (!$item) {
            $this->redirect('/hilangtemu');
        }

        $this->render('public/hilangtemu/success', [
            'pageTitle' => 'Laporan Hilang & Temu Terdaftar',
            'item'      => $item,
            'pin'       => $new['pin'],
        ], 'main');
    }

    /**
     * Detail publik satu laporan (+ formulir klaim untuk barang temuan)
     */
    public function item(int $id): void
    {
        $item = $this->model->findById($id);
        if (!$item || !in_array($item['status'], LostFound::PUBLIC_STATUSES, true)) {
            $this->notFound();
        }

        $old = Session::get('_old_claim', []);
        Session::remove('_old_claim');

        $this->render('public/hilangtemu/item', [
            'pageTitle' => $item['title'] . ' - Hilang & Temu',
            'item'      => $item,
            'photos'    => $this->model->photos($id),
            'old'       => is_array($old) ? $old : [],
        ], 'main');
    }

    /**
     * Klaim kepemilikan barang temuan oleh warga
     */
    public function klaim(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/hilangtemu');
        }

        $itemId = (int)$this->post('item_id', 0);
        $back = "/hilangtemu/item/{$itemId}";
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token keamanan CSRF tidak valid.');
            $this->redirect($back);
        }

        $item = $this->model->findById($itemId);
        if (!$item || $item['report_type'] !== 'FOUND' || $item['status'] !== 'PUBLISHED') {
            Session::setFlash('error', 'Barang temuan ini tidak sedang menerima klaim.');
            $this->redirect($itemId > 0 && $item ? $back : '/hilangtemu');
        }

        $ip = Throttle::clientIp();
        if ($this->throttle->tooMany('lostfound_claim_ip', $ip)) {
            Session::setFlash('error', 'Batas pengajuan klaim dari jaringan Anda tercapai. Silakan coba lagi dalam '
                . Throttle::windowMinutes('lostfound_claim_ip') . ' menit.');
            $this->redirect($back);
        }

        $data = [
            'claimant_name'     => mb_substr((string)$this->post('claimant_name', ''), 0, 100),
            'claimant_phone'    => preg_replace('/[\s-]/', '', (string)$this->post('claimant_phone', '')),
            'claimant_nik'      => preg_replace('/\s+/', '', (string)$this->post('claimant_nik', '')),
            'proof_description' => (string)$this->post('proof_description', ''),
        ];

        $error = match (true) {
            mb_strlen($data['claimant_name']) < 3 => 'Nama pengaju klaim wajib diisi (min. 3 karakter).',
            !preg_match('/^\d{16}$/', $data['claimant_nik']) => 'NIK harus 16 digit angka.',
            !preg_match('/^\+?\d{9,15}$/', $data['claimant_phone']) => 'Nomor HP tidak valid.',
            mb_strlen($data['proof_description']) < 20 => 'Jelaskan ciri khusus / bukti kepemilikan minimal 20 karakter.',
            mb_strlen($data['proof_description']) > 3000 => 'Penjelasan bukti kepemilikan maksimal 3000 karakter.',
            default => null,
        };
        if ($error !== null) {
            Session::set('_old_claim', $data);
            Session::setFlash('error', $error);
            $this->redirect($back);
        }

        if (!empty($_FILES['proof_file']['name']) && $_FILES['proof_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $stored = Upload::store(
                $_FILES['proof_file']['tmp_name'],
                $_FILES['proof_file']['error'],
                (int)$_FILES['proof_file']['size'],
                [...self::IMAGE_MIMES, 'application/pdf']
            );
            if ($stored === null) {
                Session::set('_old_claim', $data);
                Session::setFlash('error', 'File bukti kepemilikan harus JPG/PNG/WEBP/PDF, maksimal 20 MB.');
                $this->redirect($back);
            }
            $data['proof_file_path'] = $stored['file_path'];
        }

        $claimId = $this->model->createClaim($itemId, $data);
        $this->throttle->hit('lostfound_claim_ip', $ip);
        Audit::log('lostfound.claim', null, "{$item['code']} klaim #{$claimId}");

        Session::setFlash('success', 'Klaim kepemilikan terkirim. Petugas akan menghubungi Anda melalui nomor HP untuk verifikasi & serah terima. Bawa KTP asli dan bukti kepemilikan.');
        $this->redirect($back);
    }

    /**
     * Cek status laporan oleh pelapor dengan kode + PIN
     */
    public function status(): void
    {
        $code = strtoupper(trim((string)($this->isPost() ? $this->post('code', '') : $this->get('code', ''))));
        $errorMessage = null;
        $item = null;

        if ($this->isPost()) {
            $ip = Throttle::clientIp();
            if (!$this->validateCsrf()) {
                $errorMessage = 'Token keamanan CSRF tidak valid.';
            } elseif ($this->throttle->tooMany('lostfound_pin_ip', $ip)) {
                $errorMessage = 'Terlalu banyak percobaan. Silakan coba lagi dalam ' . Throttle::windowMinutes('lostfound_pin_ip') . ' menit.';
            } else {
                $item = $this->model->verifyPin($code, (string)$this->post('pin', ''));
                if ($item) {
                    $this->grantAccess($item['code']);
                } else {
                    $this->throttle->hit('lostfound_pin_ip', $ip);
                    $errorMessage = 'Kode laporan atau PIN tidak sesuai.';
                }
            }
        } elseif ($code !== '') {
            $found = $this->model->findByCode($code);
            $item = ($found && $this->hasAccess($found['code'])) ? $found : null;
        }

        $this->render('public/hilangtemu/status', [
            'pageTitle'    => 'Cek Status Hilang & Temu',
            'code'         => $code,
            'item'         => $item,
            'photos'       => $item ? $this->model->photos((int)$item['id']) : [],
            'confirmed'    => $item ? $this->model->confirmedMatchCount((int)$item['id']) : 0,
            'errorMessage' => $errorMessage,
        ], 'main');
    }

    /**
     * Foto objek: publik untuk laporan yang tayang; petugas & pelapor untuk semua status
     */
    public function foto(int $id): void
    {
        $photo = $this->model->findPhoto($id);
        $allowed = $photo && (
            in_array($photo['status'], LostFound::PUBLIC_STATUSES, true)
            || $this->canView()
            || $this->hasAccess($photo['code'])
        );
        if (!$allowed) {
            $this->notFound();
        }

        Upload::stream($photo['file_path'], $photo['code'] . '-' . $id . '.' . pathinfo($photo['file_path'], PATHINFO_EXTENSION));
        $this->notFound();
    }

    /**
     * Surat Keterangan Tanda Lapor Kehilangan (SKTLK): petugas atau pelapor (setelah PIN)
     */
    public function sktlk(int $id): void
    {
        $item = $this->model->findById($id);
        $allowed = $item && ($this->canView() || $this->hasAccess($item['code']));
        if (!$allowed || $item['report_type'] !== 'LOST' || in_array($item['status'], ['PENDING', 'REJECTED'], true)) {
            $this->notFound();
        }

        Audit::log('lostfound.sktlk', null, $item['code']);
        $this->render('public/hilangtemu/sktlk', ['item' => $item], 'none');
    }

    // ==================================================================
    // PETUGAS
    // ==================================================================

    public function admin(): void
    {
        $this->requireViewer();

        $filters = $this->listFilters();
        $filters['status'] = (string)$this->get('status', 'PENDING');
        if ($filters['status'] === 'ALL') {
            $filters['status'] = '';
        }

        $this->render('backoffice/hilangtemu_list', [
            'pageTitle' => 'Hilang & Temu',
            'pager'     => $this->model->paginateAdmin($filters, (int)$this->get('page', 1)),
            'filters'   => $filters,
            'counts'    => $this->model->countByStatus(),
            'canAct'    => $this->isOfficer(),
        ], 'admin');
    }

    public function detail(int $id): void
    {
        $this->requireViewer();

        $item = $this->model->findById($id);
        if (!$item) {
            Session::setFlash('error', 'Laporan Hilang & Temu tidak ditemukan.');
            $this->redirect('/hilangtemu/admin');
        }

        $this->render('backoffice/hilangtemu_detail', [
            'pageTitle' => "Hilang & Temu {$item['code']}",
            'item'      => $item,
            'photos'    => $this->model->photos($id),
            'matches'   => $this->model->matchesFor($id),
            'claims'    => $item['report_type'] === 'FOUND' ? $this->model->claimsFor($id) : [],
            'canAct'    => $this->isOfficer(),
        ], 'admin');
    }

    /**
     * Verifikasi & publikasikan laporan ke galeri publik
     */
    public function verify(): void
    {
        $item = $this->officerItemFromPost();
        $done = $this->model->publish((int)$item['id'], (int)Auth::id(), mb_substr((string)$this->post('custody_location', ''), 0, 255));
        if ($done) {
            $this->model->refreshMatches((int)$item['id']);
            Audit::log('lostfound.verify', null, $item['code']);
        }
        $this->backToDetail($item, $done, 'Laporan diverifikasi dan ditayangkan di galeri publik.', 'Laporan sudah diverifikasi sebelumnya.');
    }

    public function reject(): void
    {
        $item = $this->officerItemFromPost();
        $note = (string)$this->post('officer_note', '');
        if (mb_strlen($note) < 5) {
            Session::setFlash('error', 'Alasan penolakan wajib diisi.');
            $this->redirect("/hilangtemu/detail/{$item['id']}");
        }
        $done = $this->model->reject((int)$item['id'], (int)Auth::id(), mb_substr($note, 0, 1000));
        if ($done) {
            Audit::log('lostfound.reject', null, $item['code']);
        }
        $this->backToDetail($item, $done, 'Laporan ditolak dan tidak ditayangkan.', 'Laporan tidak dapat ditolak pada status ini.');
    }

    /**
     * Serah terima kepada pemilik / orang hilang ditemukan → selesai
     */
    public function resolve(): void
    {
        $item = $this->officerItemFromPost();
        $note = (string)$this->post('officer_note', '');
        if (mb_strlen($note) < 5) {
            Session::setFlash('error', 'Catatan serah terima wajib diisi (penerima, waktu, tempat).');
            $this->redirect("/hilangtemu/detail/{$item['id']}");
        }
        $done = $this->model->resolve((int)$item['id'], (int)Auth::id(), mb_substr($note, 0, 1000));
        if ($done) {
            Audit::log('lostfound.resolve', null, $item['code']);
        }
        $this->backToDetail($item, $done, 'Laporan dinyatakan selesai (serah terima tercatat).', 'Laporan harus terverifikasi sebelum diselesaikan.');
    }

    /**
     * Hitung ulang saran kecocokan
     */
    public function rematch(): void
    {
        $item = $this->officerItemFromPost();
        $n = $this->model->refreshMatches((int)$item['id']);
        Session::setFlash('success', "Pencocokan ulang selesai: {$n} kandidat di atas ambang skor " . LostFound::MATCH_THRESHOLD . '.');
        $this->redirect("/hilangtemu/detail/{$item['id']}");
    }

    /**
     * Konfirmasi / abaikan saran kecocokan hilang ↔ temuan
     */
    public function match(): void
    {
        $item = $this->officerItemFromPost();
        $match = $this->model->findMatch((int)$this->post('match_id', 0));
        if (!$match || !in_array((int)$item['id'], [(int)$match['lost_item_id'], (int)$match['found_item_id']], true)) {
            Session::setFlash('error', 'Saran kecocokan tidak ditemukan.');
            $this->redirect("/hilangtemu/detail/{$item['id']}");
        }

        $confirm = $this->post('decision') === 'confirm';
        $done = $this->model->reviewMatch((int)$match['id'], (int)Auth::id(), $confirm);
        if ($done) {
            Audit::log('lostfound.match', null, "{$item['code']} pasangan #{$match['id']} " . ($confirm ? 'CONFIRMED' : 'DISMISSED'));
        }
        $this->backToDetail(
            $item,
            $done,
            $confirm ? 'Kecocokan dikonfirmasi. Hubungi kedua pelapor untuk verifikasi & serah terima.' : 'Saran kecocokan diabaikan.',
            'Saran kecocokan sudah diputus sebelumnya.'
        );
    }

    /**
     * Putuskan klaim kepemilikan barang temuan
     */
    public function claim(): void
    {
        $item = $this->officerItemFromPost();
        $claim = $this->model->findClaim((int)$this->post('claim_id', 0));
        if (!$claim || (int)$claim['item_id'] !== (int)$item['id']) {
            Session::setFlash('error', 'Klaim tidak ditemukan.');
            $this->redirect("/hilangtemu/detail/{$item['id']}");
        }

        $approve = $this->post('decision') === 'approve';
        $note = mb_substr((string)$this->post('review_note', ''), 0, 500);
        if (!$approve && mb_strlen($note) < 5) {
            Session::setFlash('error', 'Alasan penolakan klaim wajib diisi.');
            $this->redirect("/hilangtemu/detail/{$item['id']}");
        }

        $done = $this->model->reviewClaim((int)$claim['id'], (int)Auth::id(), $approve, $note);
        if ($done) {
            Audit::log('lostfound.claim_review', null, "{$item['code']} klaim #{$claim['id']} " . ($approve ? 'APPROVED' : 'REJECTED'));
        }
        $this->backToDetail(
            $item,
            $done,
            $approve ? 'Klaim disetujui. Lakukan serah terima dengan pemeriksaan KTP & bukti asli.' : 'Klaim ditolak.',
            'Klaim sudah diputus sebelumnya.'
        );
    }

    /**
     * Berkas bukti kepemilikan pengaju klaim (hanya petugas)
     */
    public function bukti(int $claimId): void
    {
        $claim = $this->canView() ? $this->model->findClaim($claimId) : null;
        if (!$claim || empty($claim['proof_file_path'])) {
            $this->notFound();
        }
        Audit::log('lostfound.claim_file', null, "Klaim #{$claimId}");
        Upload::stream($claim['proof_file_path'], 'bukti-klaim-' . $claimId . '.' . pathinfo($claim['proof_file_path'], PATHINFO_EXTENSION));
        $this->notFound();
    }

    // ==================================================================
    // Helper
    // ==================================================================

    private function isOfficer(): bool
    {
        return Auth::hasRole(self::OFFICER_ROLES);
    }

    private function canView(): bool
    {
        return Auth::hasRole([...self::OFFICER_ROLES, ...self::VIEW_ROLES]);
    }

    private function requireViewer(): void
    {
        Auth::requireRole([...self::OFFICER_ROLES, ...self::VIEW_ROLES]);
    }

    /**
     * Guard aksi petugas: POST + CSRF + role, kembalikan laporan yang dituju
     */
    private function officerItemFromPost(): array
    {
        Auth::requireRole(self::OFFICER_ROLES);
        if (!$this->isPost()) {
            $this->redirect('/hilangtemu/admin');
        }

        $item = $this->model->findById((int)$this->post('item_id', 0));
        if (!$item) {
            Session::setFlash('error', 'Laporan Hilang & Temu tidak ditemukan.');
            $this->redirect('/hilangtemu/admin');
        }
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect("/hilangtemu/detail/{$item['id']}");
        }
        return $item;
    }

    private function backToDetail(array $item, bool $done, string $ok, string $fail): never
    {
        Session::setFlash($done ? 'success' : 'error', $done ? $ok : $fail);
        $this->redirect("/hilangtemu/detail/{$item['id']}");
        exit;
    }

    private function listFilters(): array
    {
        return [
            'type'     => strtoupper((string)$this->get('type', '')),
            'category' => strtoupper((string)$this->get('category', '')),
            'q'        => mb_substr((string)$this->get('q', ''), 0, 100),
        ];
    }

    private function collectItemInput(): array
    {
        $str = fn(string $key, int $max) => mb_substr((string)$this->post($key, ''), 0, $max);
        $nullable = fn(string $key, int $max) => ($v = $str($key, $max)) === '' ? null : $v;

        $age = $this->post('person_age', '');
        $gender = $this->post('person_gender', '');
        $lat = $this->post('latitude', '');
        $lng = $this->post('longitude', '');

        $data = [
            'report_type'      => strtoupper((string)$this->post('report_type', '')),
            'item_category'    => strtoupper((string)$this->post('item_category', '')),
            'title'            => $str('title', 150),
            'description'      => (string)$this->post('description', ''),
            'brand'            => $nullable('brand', 100),
            'color'            => $nullable('color', 50),
            'plate_number'     => $nullable('plate_number', 20),
            'chassis_number'   => $nullable('chassis_number', 40),
            'engine_number'    => $nullable('engine_number', 40),
            'serial_number'    => $nullable('serial_number', 60),
            'person_name'      => $nullable('person_name', 100),
            'person_age'       => is_numeric($age) ? max(0, min(120, (int)$age)) : null,
            'person_gender'    => in_array($gender, ['L', 'P'], true) ? $gender : null,
            'animal_species'   => $nullable('animal_species', 60),
            'event_date'       => (string)$this->post('event_date', ''),
            'event_location'   => $str('event_location', 255),
            'latitude'         => is_numeric($lat) && abs((float)$lat) <= 90 ? (float)$lat : null,
            'longitude'        => is_numeric($lng) && abs((float)$lng) <= 180 ? (float)$lng : null,
            'custody_location' => $nullable('custody_location', 255),
            'reporter_name'    => $str('reporter_name', 100),
            'reporter_phone'   => preg_replace('/[\s-]/', '', (string)$this->post('reporter_phone', '')),
            'reporter_nik'     => ($nik = preg_replace('/\s+/', '', (string)$this->post('reporter_nik', ''))) === '' ? null : $nik,
        ];

        // Kolom ciri yang tidak relevan dengan kategori dikosongkan (formulir menyembunyikan, bukan menghapus isinya)
        $relevant = self::CATEGORY_FIELDS[$data['item_category']] ?? [];
        foreach (array_unique(array_merge(...array_values(self::CATEGORY_FIELDS))) as $field) {
            if (!in_array($field, $relevant, true)) {
                $data[$field] = null;
            }
        }
        if ($data['report_type'] !== 'FOUND') {
            $data['custody_location'] = null;
        }

        return $data;
    }

    private function validateItemInput(array $d): ?string
    {
        $date = \DateTime::createFromFormat('Y-m-d', $d['event_date']);
        $dateValid = $date && $date->format('Y-m-d') === $d['event_date'];

        return match (true) {
            !isset(LostFound::TYPES[$d['report_type']]) => 'Pilih jenis laporan: kehilangan atau temuan.',
            !isset(LostFound::CATEGORIES[$d['item_category']]) => 'Pilih kategori objek yang valid.',
            mb_strlen($d['title']) < 5 => 'Judul laporan minimal 5 karakter.',
            mb_strlen($d['description']) < 20 => 'Deskripsi / ciri-ciri minimal 20 karakter.',
            mb_strlen($d['description']) > 5000 => 'Deskripsi maksimal 5000 karakter.',
            $d['item_category'] === 'ORANG' && $d['person_name'] === null && $d['report_type'] === 'LOST'
                => 'Nama orang hilang wajib diisi.',
            $d['item_category'] === 'KENDARAAN' && $d['plate_number'] === null && $d['chassis_number'] === null && $d['engine_number'] === null
                => 'Isi minimal salah satu: plat nomor, nomor rangka, atau nomor mesin kendaraan.',
            !$dateValid => 'Tanggal kejadian tidak valid.',
            $d['event_date'] > date('Y-m-d') => 'Tanggal kejadian tidak boleh di masa depan.',
            mb_strlen($d['event_location']) < 5 => 'Lokasi kejadian wajib diisi.',
            mb_strlen($d['reporter_name']) < 3 => 'Nama pelapor wajib diisi (min. 3 karakter).',
            !preg_match('/^\+?\d{9,15}$/', $d['reporter_phone']) => 'Nomor HP pelapor tidak valid.',
            $d['reporter_nik'] !== null && !preg_match('/^\d{16}$/', $d['reporter_nik']) => 'NIK harus 16 digit angka.',
            default => null,
        };
    }

    private function failWithOld(string $error, array $data): never
    {
        Session::set('_old_lf', $data);
        Session::setFlash('error', $error);
        $this->redirect('/hilangtemu/lapor');
        exit;
    }

    private function grantAccess(string $code): void
    {
        $granted = Session::get('lostfound_access', []);
        $granted[$code] = true;
        Session::set('lostfound_access', $granted);
        Session::regenerate();
    }

    private function hasAccess(string $code): bool
    {
        return !empty(Session::get('lostfound_access', [])[$code]);
    }

    private function notFound(): never
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Data tidak ditemukan.';
        exit;
    }
}
