<?php
/**
 * Aset <head> bersama: Tailwind + palet resmi logo "Polisi Bersama Masyarakat",
 * font, ikon, tema & efek hi-tech/3D. Dipakai oleh seluruh layout & halaman mandiri.
 */
?>
  <meta name="theme-color" content="#0C0A08">
  <link rel="icon" type="image/png" href="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // Palet diturunkan dari logo: hitam "POLISI", emas lambang Tribrata, merah marun pita, putih, hijau kapas-padi.
    // slate -> netral hangat (cokelat-hitam), amber -> emas logo, blue -> cokelat dinas Polri.
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            slate: {
              50: '#FAF8F4', 100: '#F3EFE7', 200: '#E4DDD0', 300: '#C9BFAE', 400: '#A1957F',
              500: '#7A6E5B', 600: '#5A503F', 700: '#3F372A', 800: '#2A241B', 900: '#1A1611', 950: '#0C0A08'
            },
            amber: {
              50: '#FFFBEA', 100: '#FFF3C4', 200: '#FDE58A', 300: '#FCD34D', 400: '#F7C21B',
              500: '#E8AA0C', 600: '#C58A06', 700: '#9C6A09', 800: '#7A520E', 900: '#5E3F10', 950: '#352206'
            },
            blue: {
              50: '#F8F3EC', 100: '#EDE1CF', 200: '#DCC4A0', 300: '#C9A574', 400: '#B48752',
              500: '#946A3C', 600: '#7A5530', 700: '#5F4227', 800: '#47321F', 900: '#332417', 950: '#20160E'
            },
            polri: {
              black: '#0C0A08', ink: '#14100C', panel: '#1B1610', line: '#3B2F1C',
              gold: '#F7C21B', 'gold-deep': '#C58A06', maroon: '#8E1B1B', red: '#C8102E', white: '#FFFFFF', green: '#2E7D32'
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
            heading: ['Outfit', '"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace']
          }
        }
      }
    };
  </script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/custom-polri.css">
  <script src="<?= $baseUrl ?>/assets/js/polri-fx.js" defer></script>
