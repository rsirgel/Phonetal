<?php
require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/Auth.php';

Auth::init();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (Auth::login($email, $password)) {
        header('Location: kosik.php');
        exit;
    }

    $message = 'Nesprávny email alebo heslo. Skúste to znova.';
}

$page = new Page(
    'Phonetal | Prihlásenie',
    'Prihláste sa pre dokončenie objednávky a prístup k profilu.',
    'login.php'
);

$page->render(function () use ($message): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Prihlásenie</p>
          <h1>Vstúpte do svojho účtu</h1>
          <p>Prihlásením získate prístup ku košíku a platbám.</p>
        </div>
        <div class="page-hero-card">
          <h2>Bezpečné prihlásenie</h2>
          <ul>
            <li>Údaje sa overujú v databáze</li>
            <li>Získate rýchlejší checkout</li>
            <li>Prístup k administrácii pre adminov</li>
          </ul>
        </div>
      </section>

      <section class="section">
        <div class="section-heading">
          <h2>Prihlásiť sa</h2>
          <p>Zadajte prihlasovacie údaje.</p>
        </div>
        <?php if ($message): ?>
          <p class="form-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form class="order-form" method="post">
          <div class="form-grid">
            <label>
              Email
              <input type="email" name="email" placeholder="vas@email.sk" required />
            </label>
            <label>
              Heslo
              <input type="password" name="password" placeholder="••••••" required />
            </label>
          </div>
          <button class="primary-button" type="submit">Prihlásiť</button>
        </form>
      </section>
    <?php
});