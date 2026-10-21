<?php

use App\Core\OpsTracker;
use App\Core\OpsPdp;
use App\Core\OpsAdapter;
use Tests\T;

return [
    'generator Satu Nomor menghasilkan format atomik POL-YYYYMM-KANAL-XXXXX' => function () {
        $yearMonth = date('Ym');
        
        $numWeb = OpsTracker::generateNumber('WEB_PORTAL');
        T::true(OpsTracker::isValidNumber($numWeb), "Format nomor WEB valid: {$numWeb}");
        T::true(str_starts_with($numWeb, "POL-{$yearMonth}-WEB-"), "Prefix POL-YYYYMM-WEB");

        $num110 = OpsTracker::generateNumber('110_CALL');
        T::true(OpsTracker::isValidNumber($num110), "Format nomor 110 valid: {$num110}");
        T::true(str_starts_with($num110, "POL-{$yearMonth}-110-"), "Prefix POL-YYYYMM-110");

        $numSpkt = OpsTracker::generateNumber('SPKT_LOKET');
        T::true(OpsTracker::isValidNumber($numSpkt), "Format nomor SPKT valid: {$numSpkt}");
        T::true(str_starts_with($numSpkt, "POL-{$yearMonth}-SPKT-"), "Prefix POL-YYYYMM-SPKT");

        $numCyb = OpsTracker::generateNumber('CYBER_CRIME');
        T::true(OpsTracker::isValidNumber($numCyb), "Format nomor CYB valid: {$numCyb}");
        T::true(str_starts_with($numCyb, "POL-{$yearMonth}-CYB-"), "Prefix POL-YYYYMM-CYB");

        $numDms = OpsTracker::generateNumber('DUMAS_PROPAM');
        T::true(OpsTracker::isValidNumber($numDms), "Format nomor DMS valid: {$numDms}");
        T::true(str_starts_with($numDms, "POL-{$yearMonth}-DMS-"), "Prefix POL-YYYYMM-DMS");
    },

    'generator PIN menghasilkan 6 digit numerik rahasia' => function () {
        for ($i = 0; $i < 5; $i++) {
            $pin = OpsTracker::generatePin();
            T::same(6, strlen($pin), "Panjang PIN harus 6");
            T::true(ctype_digit($pin), "PIN harus digit angka");
        }
    },

    'validator nomor Satu Nomor menolak string tidak sesuai format' => function () {
        T::true(!OpsTracker::isValidNumber('POL-202610-110'), 'Kurang random hash');
        T::true(!OpsTracker::isValidNumber('LP/B/123/X/2026/SPKT'), 'Format lama ditolak');
        T::true(!OpsTracker::isValidNumber('POL-202610-110-1234567'), 'Random hash terlalu panjang');
        T::true(!OpsTracker::isValidNumber(''), 'String kosong');
    },

    'enkripsi dan dekripsi AES-256 UU PDP bekerja simetris' => function () {
        $nikAsli = '3201012304950001';
        $encrypted = OpsPdp::encrypt($nikAsli);

        T::true($encrypted !== $nikAsli, 'Hasil enkripsi harus berbeda dari teks asli');
        T::true(!empty($encrypted), 'Ciphertext tidak boleh kosong');

        $decrypted = OpsPdp::decrypt($encrypted);
        T::same($nikAsli, $decrypted, 'Dekripsi harus mengembalikan NIK asli');
    },

    'penyamaran (masking) NIK dan No HP melindungi data pribadi di UI' => function () {
        $nik = '3171012304950001';
        $maskedNik = OpsPdp::maskNik($nik);
        T::same('3171**********01', $maskedNik, 'NIK harus disamarkan 10 digit di tengah');

        $phone = '081298765432';
        $maskedPhone = OpsPdp::maskPhone($phone);
        T::same('0812****5432', $maskedPhone, 'Phone harus disamarkan digit tengah');

        T::same('-', OpsPdp::maskNik(null));
        T::same('-', OpsPdp::maskPhone(''));
    },

    'adapter integrasi dual-mode merespon secara deterministik di sandbox' => function () {
        T::true(OpsAdapter::isSandbox(), 'Pengujian harus berada dalam mode sandbox');

        $cad = OpsAdapter::dispatch110('POL-202610-110-TEST1', ['unit_callsign' => 'PATROLI-01']);
        T::same('SUCCESS', $cad['status']);
        T::same('SANDBOX', $cad['mode']);
        T::same(8, $cad['eta_minutes']);

        $emp = OpsAdapter::syncEmp('POL-202610-SPKT-TEST2', ['stage_code' => 'A2']);
        T::same('SUCCESS', $emp['status']);
        T::true(str_contains($emp['emp_register_no'], 'LP/B/'), 'Register LP format e-MP');

        $bank = OpsAdapter::requestBankFreeze('POL-202610-CYB-TEST3', ['account_number' => '1234567890']);
        T::same('SUCCESS', $bank['status']);
        T::same('FROZEN_CONFIRMED', $bank['freeze_status']);

        $sms = OpsAdapter::sendCitizenNotice('081298765432', 'Pemberitahuan');
        T::same('DELIVERED', $sms['status']);
    }
];
