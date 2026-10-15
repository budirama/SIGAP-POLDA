<?php
/**
 * SIGAP-POLDA: Konstanta Sistem (Roles, Status, Channels, Labels)
 */

namespace App\Config;

class Constants
{
    // 9 Hak Akses Role RBAC
    public const ROLE_CITIZEN = 'ROLE_CITIZEN';
    public const ROLE_SPKT_OPERATOR = 'ROLE_SPKT_OPERATOR';
    public const ROLE_CYBER_HUMAS = 'ROLE_CYBER_HUMAS';
    public const ROLE_SATKER_HEAD = 'ROLE_SATKER_HEAD';
    public const ROLE_SATKER_ADMIN = 'ROLE_SATKER_ADMIN';
    public const ROLE_INVESTIGATOR = 'ROLE_INVESTIGATOR';
    public const ROLE_INTERNAL_AFFAIRS = 'ROLE_INTERNAL_AFFAIRS';
    public const ROLE_EXECUTIVE = 'ROLE_EXECUTIVE';
    public const ROLE_SUPERADMIN = 'ROLE_SUPERADMIN';

    // Status Penanganan Perkara (Lifecycle)
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_VERIFIED_SPKT = 'VERIFIED_SPKT';
    public const STATUS_DISPATCHED = 'DISPATCHED';
    public const STATUS_ASSIGNED = 'ASSIGNED';
    public const STATUS_INVESTIGATING = 'INVESTIGATING';
    public const STATUS_CLOSED = 'CLOSED';
    public const STATUS_REJECTED = 'REJECTED';

    // Saluran Laporan Masuk (Omnichannel)
    public const CHANNEL_WEB = 'WEB_PORTAL';
    public const CHANNEL_WA = 'WHATSAPP_BOT';
    public const CHANNEL_TWITTER = 'TWITTER_X';
    public const CHANNEL_IG = 'INSTAGRAM';
    public const CHANNEL_FB = 'FACEBOOK';
    public const CHANNEL_TIKTOK = 'TIKTOK';
    public const CHANNEL_110 = 'CALL_CENTER_110';

    // Label Deskriptif Role
    public static function getRoleLabel(string $role): string
    {
        return match ($role) {
            self::ROLE_CITIZEN => 'Warga / Pelapor',
            self::ROLE_SPKT_OPERATOR => 'Operator SPKT / Command Center',
            self::ROLE_CYBER_HUMAS => 'Petugas Humas & Patroli Siber',
            self::ROLE_SATKER_HEAD => 'Pimpinan Satker / Kapolres',
            self::ROLE_SATKER_ADMIN => 'Admin / Kanit Satker',
            self::ROLE_INVESTIGATOR => 'Penyidik / Petugas Lapangan',
            self::ROLE_INTERNAL_AFFAIRS => 'Pengawas Internal (Itwasda/Propam)',
            self::ROLE_EXECUTIVE => 'Executive (Kapolda / PJU)',
            self::ROLE_SUPERADMIN => 'Superadministrator TIK',
            default => 'Pengguna'
        };
    }

    // Badge Warna Status (Tailwind Classes)
    public static function getStatusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-800 text-slate-300 border border-slate-700">Draf</span>',
            self::STATUS_SUBMITTED => '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-950 text-blue-300 border border-blue-800 animate-pulse">Menunggu Verifikasi</span>',
            self::STATUS_VERIFIED_SPKT => '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-indigo-950 text-indigo-300 border border-indigo-800">Terverifikasi SPKT</span>',
            self::STATUS_DISPATCHED => '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-950 text-amber-300 border border-amber-700">Didisposisikan</span>',
            self::STATUS_ASSIGNED => '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-cyan-950 text-cyan-300 border border-cyan-700">Penunjukan Penyidik</span>',
            self::STATUS_INVESTIGATING => '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-yellow-950 text-yellow-300 border border-yellow-600">Proses Penyelidikan</span>',
            self::STATUS_CLOSED => '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-950 text-emerald-300 border border-emerald-600">Selesai (BAP)</span>',
            self::STATUS_REJECTED => '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-950 text-rose-300 border border-rose-800">Ditolak</span>',
            default => '<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-800 text-gray-300">'.$status.'</span>'
        };
    }

    // Badge Kanal Pengaduan
    public static function getChannelBadge(string $channel): string
    {
        return match ($channel) {
            self::CHANNEL_WEB => '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-blue-900/60 text-blue-200 border border-blue-700/50"><i class="fas fa-globe"></i> Web Portal</span>',
            self::CHANNEL_WA => '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-emerald-900/60 text-emerald-200 border border-emerald-700/50"><i class="fab fa-whatsapp"></i> WhatsApp</span>',
            self::CHANNEL_TWITTER => '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-sky-900/60 text-sky-200 border border-sky-700/50"><i class="fab fa-x-twitter"></i> X / Twitter</span>',
            self::CHANNEL_IG => '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-pink-900/60 text-pink-200 border border-pink-700/50"><i class="fab fa-instagram"></i> Instagram</span>',
            self::CHANNEL_FB => '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-indigo-900/60 text-indigo-200 border border-indigo-700/50"><i class="fab fa-facebook"></i> Facebook</span>',
            self::CHANNEL_TIKTOK => '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-slate-900/80 text-slate-200 border border-slate-600"><i class="fab fa-tiktok"></i> TikTok</span>',
            self::CHANNEL_110 => '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-red-900/60 text-red-200 border border-red-700/50"><i class="fas fa-phone-alt"></i> Call Center 110</span>',
            default => '<span class="px-2 py-0.5 text-xs rounded bg-slate-800 text-slate-300">'.$channel.'</span>'
        };
    }
}
