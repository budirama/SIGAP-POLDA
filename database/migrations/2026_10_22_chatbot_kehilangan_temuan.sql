-- Ganti sebutan layanan "Hilang & Temu" menjadi "Kehilangan dan Temuan" pada jawaban chatbot yang sudah terpasang
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_22_chatbot_kehilangan_temuan.sql
-- Hanya frasa tersebut yang diganti; bagian lain jawaban yang sudah diubah petugas tetap utuh. Aman dijalankan ulang.

UPDATE `chatbot_intents`
SET `label`      = REPLACE(`label`, 'Hilang & Temu', 'Kehilangan dan Temuan'),
    `answer`     = REPLACE(`answer`, 'Hilang & Temu', 'Kehilangan dan Temuan'),
    `link_label` = REPLACE(`link_label`, 'Hilang & Temu', 'Kehilangan dan Temuan')
WHERE `label` LIKE '%Hilang & Temu%'
   OR `answer` LIKE '%Hilang & Temu%'
   OR `link_label` LIKE '%Hilang & Temu%';
