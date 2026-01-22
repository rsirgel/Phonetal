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
        $links = [
            'index.php' => 'Domov',
            'zariadenia.php' => 'Zariadenia',
            'sluzby.php' => 'Služby',
            'kontakt.php' => 'Kontakt',
            'robots.php' => 'Roadmap',
        ];
        $user = Auth::user();
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
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600&family=Roboto:wght@300;400;500;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="design/style.css" />
    <link rel="stylesheet" href="design/navbar.css" />
    <link rel="stylesheet" href="design/sidemenu.css" />
  </head>
  <body>
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
            <span class="nav-user">Ahoj, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></span>
            <details class="profile-menu">
              <summary class="ghost-button">Profil</summary>
              <div class="profile-dropdown" role="menu">
                <a class="profile-dropdown-item" role="menuitem" href="kosik.php">Objednávky</a>
                <a
                  class="profile-dropdown-item"
                  role="menuitem"
                  href="<?= $user['role'] === 'admin' ? 'admin.php' : 'user.php' ?>"
                >
                  Účet zákazníka
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
        <h4>Kontakt</h4>
        <p>Email: info@phonetal.sk</p>
        <p>Tel: +421 902 123 456</p>
      </div>
      <div>
        <h4>Sledujte nás</h4>
        <p>Instagram • Facebook • LinkedIn</p>
      </div>
    </footer>
    <script>
      const searchInput = document.getElementById('search-input');
      const searchResults = document.getElementById('search-results');

      if (searchInput && searchResults) {
        let controller;
        searchInput.addEventListener('input', async (event) => {
          const value = event.target.value.trim();
          if (value.length < 3) {
            searchResults.innerHTML = '';
            searchResults.classList.remove('is-visible');
            return;
          }

          if (controller) {
            controller.abort();
          }
          controller = new AbortController();

          try {
            const response = await fetch(`search.php?q=${encodeURIComponent(value)}`, {
              signal: controller.signal,
            });
            const items = await response.json();
            searchResults.innerHTML = items
              .map((item) => `<button type="button" class="search-item">${item}</button>`)
              .join('');
            searchResults.classList.toggle('is-visible', items.length > 0);
          } catch (error) {
            searchResults.innerHTML = '';
            searchResults.classList.remove('is-visible');
          }
        });

        searchResults.addEventListener('click', (event) => {
          const target = event.target;
          if (target && target.classList.contains('search-item')) {
            searchInput.value = target.textContent;
            searchResults.innerHTML = '';
            searchResults.classList.remove('is-visible');
          }
        });
      }
    </script>
  </body>
</html>
<?php
    }
}