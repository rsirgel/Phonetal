<?php
require_once __DIR__ . '/models/page.php';
require_once __DIR__ . '/models/auth.php';

Auth::init();
$user = Auth::user();

if (!$user) {
    header('Location: login.php');
    exit;
}

$page = new Page(
    'Phonetal | Profil používateľa',
    'Zobrazenie profilu používateľa a prehľad prenájmov.',
    'user.php'
);

$page->render(function () use ($user): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Profil</p>
          <h1>Váš účet</h1>
          <p>Spravujte svoje prenájmy a osobné údaje.</p>
        </div>
        <div class="page-hero-card">
          <h2>Stav účtu</h2>
          <ul>
            <li>Prihlásený: áno</li>
            <li>Meno: <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></li>
            <li>Email: <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></li>
            <li>Rola: <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></li>
          </ul>
        </div>
      </section>

      <section class="section">
        <div class="section-heading">
          <h2>Prehľad</h2>
          <p>Tu bude história prenájmov a nastavenia profilu.</p>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h3>Moje prenájmy</h3>
            <p>Aktuálne nemáte žiadne aktívne prenájmy.</p>
          </div>
          <div class="feature-card">
            <h3>Fakturačné údaje</h3>
            <p>Skontrolujte uložené kontaktné informácie.</p>
          </div>
        </div>
      </section>
    <?php
}); 