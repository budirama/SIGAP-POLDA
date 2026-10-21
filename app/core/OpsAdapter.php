<?php
/**
 * SIGAP-POLRI: Dual-Mode Integration Adapter
 * Mendukung Sandbox/Mock (Lokal/Pengujian) & Live HTTP API (Produksi)
 */

declare(strict_types=1);

namespace App\Core;

use App\Config\Config;

class OpsAdapter
{
    /**
     * Cek apakah sistem sedang dalam mode sandbox
     */
    public static function isSandbox(): bool
    {
        return strtolower(Config::env('OPS_INTEGRATION_MODE', 'sandbox')) === 'sandbox';
    }

    /**
     * Dispatch Panggilan 110 ke CAD Unit Patroli Terdekat
     */
    public static function dispatch110(string $trackingNumber, array $cadData): array
    {
        if (self::isSandbox()) {
            return [
                'status' => 'SUCCESS',
                'mode' => 'SANDBOX',
                'tracking_number' => $trackingNumber,
                'cad_ref_id' => 'CAD-SIM-' . random_int(10000, 99999),
                'assigned_unit' => $cadData['unit_callsign'] ?? 'PATROLI-SAMAPTA-01',
                'eta_minutes' => 8,
                'dispatched_at' => date('Y-m-d H:i:s'),
                'message' => 'Simulasi unit patroli berhasil menerima penugasan CAD'
            ];
        }

        // Live Mode via cURL
        $url = Config::env('OPS_110_ENDPOINT', 'https://api-110.polri.go.id/v1') . '/dispatch';
        return self::sendHttpRequest($url, 'POST', $cadData);
    }

    /**
     * Sinkronisasi berkas perkara ke e-MP (e-Manajemen Penyidikan Bareskrim)
     */
    public static function syncEmp(string $trackingNumber, array $caseData): array
    {
        if (self::isSandbox()) {
            return [
                'status' => 'SUCCESS',
                'mode' => 'SANDBOX',
                'tracking_number' => $trackingNumber,
                'emp_register_no' => 'LP/B/' . random_int(100, 999) . '/' . date('m/Y') . '/SPKT/BARESKRIM',
                'synced_at' => date('Y-m-d H:i:s'),
                'message' => 'Simulasi sinkronisasi berkas e-MP berhasil'
            ];
        }

        $url = Config::env('OPS_EMP_ENDPOINT', 'https://emp.bareskrim.polri.go.id/api/v2') . '/sync';
        return self::sendHttpRequest($url, 'POST', $caseData);
    }

    /**
     * Kirim permintaan pemblokiran rekening darurat ke Konsorsium Bank / OJK
     */
    public static function requestBankFreeze(string $trackingNumber, array $fraudData): array
    {
        if (self::isSandbox()) {
            return [
                'status' => 'SUCCESS',
                'mode' => 'SANDBOX',
                'tracking_number' => $trackingNumber,
                'bank_ticket_id' => 'OJK-FREEZE-' . strtoupper(substr(md5($fraudData['account_number'] ?? 'ACC'), 0, 8)),
                'bank_name' => $fraudData['bank_name'] ?? 'BANK_BCA',
                'account_number' => $fraudData['account_number'] ?? '0000000000',
                'freeze_status' => 'FROZEN_CONFIRMED',
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Simulasi konfirmasi pemblokiran sementara rekening penipu'
            ];
        }

        $url = Config::env('OPS_BANK_FREEZE_ENDPOINT', 'https://cekrekening.id/api/v1') . '/freeze';
        return self::sendHttpRequest($url, 'POST', $fraudData);
    }

    /**
     * Kirim Notifikasi SMS / WhatsApp Resmi ke Pelapor Warga
     */
    public static function sendCitizenNotice(string $phone, string $message): array
    {
        if (self::isSandbox()) {
            return [
                'status' => 'DELIVERED',
                'mode' => 'SANDBOX',
                'recipient' => OpsPdp::maskPhone($phone),
                'message_preview' => substr($message, 0, 60) . '...',
                'sent_at' => date('Y-m-d H:i:s')
            ];
        }

        $url = Config::env('OPS_SMS_GATEWAY_URL', 'https://sms-presisi.polri.go.id/send');
        return self::sendHttpRequest($url, 'POST', ['to' => $phone, 'text' => $message]);
    }

    /**
     * Helper HTTP Request cURL aman dengan timeout singkat
     */
    private static function sendHttpRequest(string $url, string $method, array $data): array
    {
        $ch = curl_init();
        $payload = json_encode($data);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3, // Maksimal 3 detik demi performa UI
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Polri-Client: SIGAP-ONE-SERVICE-V1.3',
                'Authorization: Bearer ' . Config::env('OPS_API_SECRET_TOKEN', 'token-dummy')
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            return ['status' => 'ERROR', 'error' => $error];
        }

        $decoded = json_decode((string)$response, true);
        return is_array($decoded) ? $decoded : ['status' => 'RAW_RESPONSE', 'code' => $httpCode, 'data' => $response];
    }
}
