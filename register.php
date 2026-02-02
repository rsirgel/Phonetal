<?php
require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/Auth.php';

Auth::init();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim((string) ($_POST['first_name'] ?? ''));
    $lastName = trim((string) ($_POST['last_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($firstName && $lastName && $email && $password) {
        try {
            Auth::register($firstName, $lastName, $email, $password);
            header('Location: kosik.php');
            exit;
        } catch (Throwable $exception) {
            $message = 'Registrácia sa nepodarila. Skúste iný email.';
        }
    } else {
        $message = 'Zadajte meno, priezvisko, email a heslo.';
    }

}

$page = new Page(
    'Phonetal | Registrácia',
    'Vytvorte si účet a dokončite prenájom zariadenia.',
    'register.php'
);

$page->render(function () use ($message): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Registrácia</p>
          <h1>Vytvorte si účet</h1>
          <p>Registrácia vám umožní spravovať prenájmy a platby.</p>
        </div>
        <div class="page-hero-card">
          <h2>Výhody účtu</h2>
          <ul>
            <li>Rýchlejší checkout</li>
            <li>História prenájmov</li>
            <li>Správa fakturácie</li>
          </ul>
        </div>
      </section>

      <section class="section">
        <div class="section-heading">
          <h2>Registrovať sa</h2>
          <p>Vyplňte základné údaje.</p>
        </div>
        <?php if ($message): ?>
          <p class="form-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form class="order-form" method="post">
          <div class="form-grid">
            <label>
              Meno
              <input type="text" name="first_name" placeholder="Ján" required />
            </label>
            <label>
              Priezvisko
              <input type="text" name="last_name" placeholder="Novák" required />
            </label>
            <label>
              Email
              <input type="email" name="email" placeholder="vas@email.sk" required />
            </label>
            <label>
              Heslo
              <input type="password" name="password" placeholder="••••••" required />
            </label>
          </div>
          <button class="primary-button" type="submit">Vytvoriť účet</button>
        </form>
      </section>
    <?php
});