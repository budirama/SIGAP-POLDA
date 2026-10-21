<?php

use App\Core\ModuleManager;
use App\Models\SystemModule;
use Tests\T;
use Tests\Db;

return [
    'ModuleManager mendeteksi modul aktif dan fail-open pada modul tidak terdaftar' => function () {
        ModuleManager::clearCache();

        T::true(ModuleManager::isActive('ONESERVICE'), 'Modul ONESERVICE harus aktif');
        T::true(ModuleManager::isActive('oneservice_hub'), 'Sub-modul oneservice_hub harus aktif');
        T::true(ModuleManager::isActive('MODUL_TIDAK_ADA_XYZ'), 'Modul tidak dikenal tetap fail-open (true)');
    },

    'ModuleManager menolak penonaktifan modul inti (is_core = 1)' => function () {
        $res = ModuleManager::toggle('ONESERVICE', false, 1);
        T::true(!$res, 'Modul inti tidak boleh dapat dinonaktifkan (return false)');
    },

    'ModuleManager mendukung toggle dinamis untuk modul non-inti dan menghormati hierarki' => function () {
        $model = new SystemModule();
        $targetModule = 'oneservice_bhabin'; // non-core sub-module

        // Pastikan awal aktif
        $model->setStatus($targetModule, true, 1);
        ModuleManager::clearCache();
        T::true(ModuleManager::isActive($targetModule), 'Awal harus aktif');

        // Toggle menjadi nonaktif
        $ok = ModuleManager::toggle($targetModule, false, 1);
        T::true($ok, 'Toggle nonaktif berhasil');
        T::true(!ModuleManager::isActive($targetModule), 'isActive harus mengembalikan false');

        // Kembalikan ke aktif
        $ok2 = ModuleManager::toggle($targetModule, true, 1);
        T::true($ok2, 'Toggle aktif kembali berhasil');
        T::true(ModuleManager::isActive($targetModule), 'isActive harus mengembalikan true');
    },

    'SystemModule getStatusMap dan getHierarchical menyajikan data terstruktur' => function () {
        $model = new SystemModule();
        $map = $model->getStatusMap();
        T::true(is_array($map), 'Status map berbentuk array');
        T::true(isset($map['ONESERVICE']), 'Map memuat kunci ONESERVICE');
        T::true(!empty($map['ONESERVICE']['is_active']), 'ONESERVICE bernilai aktif');

        $tree = $model->getHierarchical();
        T::true(is_array($tree) && count($tree) > 0, 'Pohon hierarki memuat grup induk');
        $found = false;
        foreach ($tree as $group) {
            if ($group['module_key'] === 'ONESERVICE') {
                $found = true;
                T::true(!empty($group['children']), 'ONESERVICE memiliki sub-modul children');
                break;
            }
        }
        T::true($found, 'Pohon hierarki memuat induk ONESERVICE');
    }
];
