<?php
require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/Auth.php';

Auth::init();
$user = Auth::user();

$page = new Page(
    'Phonetal | Admin panel',
    'Administrácia zariadení, prenájmov a používateľov.',
    'admin.php'
);

$page->render(function () use ($user): void {
    $isAdmin = $user && $user['role'] === 'admin';
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Administrácia</p>
          <h1>Admin panel</h1>
          <p>Správa zariadení, objednávok a používateľov.</p>
        </div>
        <div class="page-hero-card">
          <h2>Prístup</h2>
          <ul>
            <li>Rola: <?= $user ? htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') : '—' ?></li>
            <li>Stav: <?= $isAdmin ? 'admin' : 'bez prístupu' ?></li>
          </ul>
        </div>
      </section>

      <section class="section">
        <div class="section-heading">
          <h2>Admin nástroje</h2>
          <p>Táto sekcia je dostupná len pre adminov.</p>
        </div>
        <?php if (!$isAdmin): ?>
          <p class="form-message">Nemáte oprávnenie na prístup do admin panelu.</p>
          <a class="primary-button" href="login.php">Prihlásiť ako admin</a>
        <?php else: ?>
          <div class="feature-grid">
            <div class="feature-card">
              <h3>Správa zariadení</h3>
              <p>Pridajte nové zariadenia a aktualizujte dostupnosť.</p>
            </div>
            <div class="feature-card">
              <h3>Objednávky</h3>
              <p>Kontrola prenájmov a schvaľovanie platieb.</p>
            </div>
            <div class="feature-card">
              <h3>Používatelia</h3>
              <p>Správa účtov a rolí.</p>
            </div>
          </div>
        <?php endif; ?>
      </section>
    <?php
});