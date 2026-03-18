<?php

require_once __DIR__ . '/auth.php';



class Page
{
    private string $title;
    private string $description;
    private string $active;

    public function __construct(string $title, string $description, string $active)
    {
        $this->title = $title;
        $this->description = $description;
        $this->active = $active;
    }

    public function render(callable $content): void
    {
        $this->renderHeader();
        echo "<main>\n";
        $content();
        echo "</main>\n";
        $this->renderFooter();
    }

    private function renderHeader(): void
    {
        Auth::init();
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
            $csp = "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; "
                . "img-src 'self' data: https://www.google-analytics.com; "
                . "script-src 'self' 'unsafe-inline' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ https://www.googletagmanager.com; "
                . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
                . "font-src https://fonts.gstatic.com; connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; "
                . "frame-src https://www.google.com/recaptcha/";
            header('Content-Security-Policy: ' . $csp);
        }
        $links = [
            'index.php' => 'Domov',
            'zariadenia.php' => 'Zariadenia',
        ];
        $user = Auth::user();
        if ($user && $user['role'] === 'admin') {
            $links['robots.php'] = 'Roadmap';
        }
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = rtrim($scriptDir, '/');
        if ($basePath !== '' && str_ends_with($basePath, '/models')) {
            $basePath = rtrim(dirname($basePath), '/');
        }
        if ($basePath !== '' && $basePath[0] !== '/') {
            $basePath = '/' . $basePath;
        }
        $searchEndpoint = $basePath === '' ? 'search.php' : $basePath . '/search.php';
        $styleVersion = @filemtime(__DIR__ . '/../design/style.css') ?: time();
        $navbarVersion = @filemtime(__DIR__ . '/../design/navbar.css') ?: $styleVersion;
        $sidemenuVersion = @filemtime(__DIR__ . '/../design/sidemenu.css') ?: $styleVersion;
        ?>
<!DOCTYPE html>
<html lang="sk">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') ?></title>
    <meta
      name="description"
      content="<?= htmlspecialchars($this->description, ENT_QUOTES, 'UTF-8') ?>"
    />
    <meta name="csrf-token" content="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600&family=Roboto:wght@300;400;500;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="design/style.css?v=<?= (int) $styleVersion ?>" />
    <link rel="stylesheet" href="design/navbar.css?v=<?= (int) $navbarVersion ?>" />
    <link rel="stylesheet" href="design/sidemenu.css?v=<?= (int) $sidemenuVersion ?>" />
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-56C8B6NFMM"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-56C8B6NFMM');
    </script>
  </head>
  <body
    data-app-base="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>"
    data-search-endpoint="<?= htmlspecialchars($searchEndpoint, ENT_QUOTES, 'UTF-8') ?>"
  >
    <header class="site-header">
      <nav class="navbar">
        <a class="logo" href="index.php">
          <span class="logo-mark">P</span>
          <span class="logo-text">Phonetal</span>
        </a>
        <div class="nav-links">
          <?php foreach ($links as $href => $label): ?>
            <a class="<?= $href === $this->active ? 'nav-active' : '' ?>" href="<?= $href ?>">
              <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </a>
          <?php endforeach; ?>
        </div>
        <div class="nav-search" role="search">
          <label class="sr-only" for="search-input">Vyhľadávanie zariadení</label>
          <input
            id="search-input"
            type="search"
            name="q"
            placeholder="Hľadať značku alebo model"
            autocomplete="off"
          />
          <div id="search-results" class="search-results" aria-live="polite"></div>
        </div>
        <div class="nav-actions">
          <?php if ($user): ?>
            <span class="nav-user"> <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></span>
            <details class="profile-menu">
              <summary class="ghost-button">Profil</summary>
              <div class="profile-dropdown" role="menu">
                <a class="profile-dropdown-item" role="menuitem" href="user.php#orders">Objednávky</a>
                <a
                  class="profile-dropdown-item"
                  role="menuitem"
                  href="<?= $user['role'] === 'admin' ? 'admin.php' : 'user.php' ?>"
                >
                  <?= $user['role'] === 'admin' ? 'Administrácia' : 'Môj dashboard' ?>
                </a>
                <a class="profile-dropdown-item" role="menuitem" href="logout.php">Odhlásiť</a>
              </div>
            </details>
          <?php else: ?>
            <a class="ghost-button" href="login.php">Prihlásiť</a>
            <a class="primary-button" href="register.php">Registrácia</a>
          <?php endif; ?>
        </div>
      </nav>
    </header>
<?php
    }

    private function renderFooter(): void
    {
        ?>
    <footer class="site-footer">
      <div>
        <strong>Phonetal</strong>
        <p>Prenájom telefónov, tabletov a príslušenstva pre firmy aj jednotlivcov.</p>
      </div>
      <div>
        <h4>O nás</h4>
        <p>Email: info@phonetal.sk</p>
        <p>Tel: +421 902 123 456</p>
        <p><a href="sluzby.php">Služby</a></p>
        <p><a href="kontakt.php">Kontakt</a></p>
      </div>
      <div>
        <h4>Sledujte nás</h4>
        <p>Instagram • Facebook • LinkedIn</p>
      </div>
      <p class="footer-copy">
        <small>&copy; <?= date('Y') ?> Phonetal. Všetky práva vyhradené.</small>
      </p>
    </footer>
    <script>
      const searchInput = document.getElementById('search-input');
      const searchResults = document.getElementById('search-results');


      if (searchInput && searchResults) {
        let controller;
        const minLength = 2;
        const searchEndpoint = document.body.dataset.searchEndpoint || 'search.php';

        const renderResults = (items, emptyMessage = 'Žiadne výsledky.') => {
          if (!items.length) {
            searchResults.innerHTML = `<div class="search-empty">${emptyMessage}</div>`;
            searchResults.classList.add('is-visible');
            return;
          }

          searchResults.innerHTML = items
            .map((item) => {
              if (typeof item === 'string') {
                return `<button type="button" class="search-item" data-label="${item}">${item}</button>`;
              }
              const label = item.label || '';
              const id = Number(item.id);
              return `<button type="button" class="search-item" data-id="${id}" data-label="${label}">${label}</button>`;
            })
            .join('');
          searchResults.classList.add('is-visible');
        };

        const fetchSuggestions = async (value) => {
          if (value.length < minLength) {
            searchResults.innerHTML = '';
            searchResults.classList.remove('is-visible');
            return;
          }

          if (controller) {
            controller.abort();
          }
          controller = new AbortController();

          try {
            const response = await fetch(`${searchEndpoint}?q=${encodeURIComponent(value)}`, {
              signal: controller.signal,
            });
            if (!response.ok) {
              throw new Error('Search failed');
            }
            const items = await response.json();
            renderResults(items);
          } catch (error) {
            renderResults([], 'Vyhľadávanie je momentálne nedostupné.');
          }
        };

        searchInput.addEventListener('input', (event) => {
          fetchSuggestions(event.target.value.trim());
        });

        searchInput.addEventListener('keydown', (event) => {
          if (event.key !== 'Enter') {
            return;
          }
          event.preventDefault();
          fetchSuggestions(event.target.value.trim());
        });

        searchResults.addEventListener('click', (event) => {
          const target = event.target;
          if (target && target.classList.contains('search-item')) {
            const selectedLabel = target.dataset.label || target.textContent;
            searchInput.value = selectedLabel;
            searchResults.innerHTML = '';
            searchResults.classList.remove('is-visible');
            const selectedId = Number(target.dataset.id);
            if (selectedId) {
              const basePath = document.body.dataset.appBase || '';
              const detailUrl = `${basePath ? basePath + '/' : ''}zariadenia.php?id=${selectedId}`;
              window.location.href = detailUrl;
            }
          }
        });
      }

      const filterInputs = document.querySelectorAll('[data-filter-input]');
      if (filterInputs.length) {
        filterInputs.forEach((input) => {
          input.addEventListener('change', () => {
            const form = input.closest('form');
            if (form) {
              form.submit();
            }
          });
        });
      }
    </script>
  </body>
</html>
<?php
    }
}
