<?php

use App\Core\App;
use Tests\T;

return [
    'alias social mengarah ke SocialMediaController' => function () {
        T::same('SocialMediaController', App::resolveControllerName('social'));
        T::same('SocialMediaController', App::resolveControllerName('SOCIAL'));
    },

    'controller yang ada di-resolve dari segmen URL' => function () {
        T::same('ReportController', App::resolveControllerName('report'));
        T::same('FileController', App::resolveControllerName('file'));
    },

    'controller tak dikenal & segmen berbahaya ditolak' => function () {
        foreach (['xyz', '..', '..\\config', 'report.php', 'report/x', '', '1report'] as $segment) {
            T::same(null, App::resolveControllerName($segment), "segmen '{$segment}'");
        }
    },

    'action publik milik controller diizinkan' => function () {
        T::true(App::resolveActionMethod('App\\Controllers\\ReportController', 'track') !== null, 'track');
        T::true(App::resolveActionMethod('App\\Controllers\\InvestigatorController', 'detail') !== null, 'detail');
    },

    'helper warisan, constructor, private & magic method ditolak' => function () {
        foreach (['render', 'json', 'redirect', 'post', 'validateCsrf', '__construct', 'canAccessReport', 'storeClarificationFile', 'nonexistent'] as $action) {
            T::same(null, App::resolveActionMethod('App\\Controllers\\ReportController', $action), "action '{$action}'");
        }
    },

    'parameter int divalidasi' => function () {
        $detail = App::resolveActionMethod('App\\Controllers\\InvestigatorController', 'detail');
        T::same([5], App::resolveParams($detail, ['5']));
        T::same(null, App::resolveParams($detail, []), 'parameter wajib kosong');
        T::same(null, App::resolveParams($detail, ['abc']), 'bukan angka');
        T::same(null, App::resolveParams($detail, ['-1']), 'negatif');
        T::same(null, App::resolveParams($detail, ['99999999999']), 'terlalu besar');
        T::same(null, App::resolveParams($detail, ['1', '2']), 'parameter berlebih');
    },

    'parameter opsional string diteruskan apa adanya' => function () {
        $quick = App::resolveActionMethod('App\\Controllers\\AuthController', 'quickLogin');
        T::same([], App::resolveParams($quick, []));
        T::same(['spkt'], App::resolveParams($quick, ['spkt']));
    },
];
