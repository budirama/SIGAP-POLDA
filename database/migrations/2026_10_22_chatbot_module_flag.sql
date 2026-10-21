-- Daftarkan Chatbot Konsultasi pada Manajemen Modul (butuh tabel system_modules dari 2026_10_19_add_system_modules.sql)
-- Modul nonaktif: percakapan Live Chat baru langsung menunggu petugas seperti sebelumnya.

INSERT INTO `system_modules`
(`module_key`, `parent_key`, `name`, `description`, `category`, `route_prefix`, `icon`, `is_active`, `is_core`, `sort_order`)
VALUES
('op_chatbot', 'OPERATIONAL', 'Chatbot Konsultasi', 'Asisten virtual Live Chat: menjawab pertanyaan umum dan meneruskan ke petugas bila diperlukan', 'OPERATIONAL', 'chatbot', 'fas fa-robot', 1, 0, 37)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `route_prefix` = VALUES(`route_prefix`),
  `icon` = VALUES(`icon`),
  `sort_order` = VALUES(`sort_order`);
