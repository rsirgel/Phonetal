<?php
require_once __DIR__ . '/models/page.php';
require_once __DIR__ . '/models/auth.php';

Auth::init();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
        $message = 'Neplatny bezpecnostny token. Obnovte stranku a skuste znova.';
    } else {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($firstName !== '' && $lastName !== '' && $email !== '' && $password !== '') {
            try {
                Auth::register($firstName, $lastName, $email, $password);
                header('Location: kosik.php');
                exit;
            } catch (InvalidArgumentException $exception) {
                $code = $exception->getMessage();
                if ($code === 'invalid_email') {
                    $message = 'Zadajte platny email.';
                } elseif ($code === 'weak_password') {
                    $message = 'Heslo musi mat aspon 6 znakov.';
                } else {
                    $message = 'Neplatne udaje registracie.';
                }
            } catch (RuntimeException $exception) {
                if ($exception->getMessage() === 'email_exists') {
                    $message = 'Tento email uz je registrovany. Skuste iny email.';
                } else {
                    error_log('Registracia zlyhala: ' . $exception->getMessage());
                    $message = 'Registracia sa nepodarila z technickych dovodov. Skuste to neskor.';
                }
            } catch (Throwable $exception) {
                error_log('Registracia zlyhala: ' . $exception->getMessage());
                $message = 'Registracia sa nepodarila z technickych dovodov. Skuste to neskor.';
            }
        } else {
            $message = 'Zadajte meno, priezvisko, email a heslo.';
        }
    }
}

$page = new Page(
    'Phonetal | Registracia',
    'Vytvorte si ucet a dokoncite prenajom zariadenia.',
    'register.php'
);

$page->render(function () use ($message): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Registracia</p>
          <h1>Vytvorte si ucet</h1>
          <p>Registracia vam umozni spravovat prenajmy a platby.</p>
        </div>
        <div class="page-hero-card">
          <h2>Vyhody uctu</h2>
          <ul>
            <li>Rychlejsi checkout</li>
            <li>Historia prenajmov</li>
            <li>Sprava fakturacie</li>
          </ul>
        </div>
      </section>

      <section class="section">
        <div class="section-heading">
          <h2>Registrovat sa</h2>
          <p>Vyplnte zakladne udaje.</p>
        </div>
        <?php if ($message): ?>
          <p class="form-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form class="order-form" method="post">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>" />
          <div class="form-grid">
            <label>
              Meno
              <input type="text" name="first_name" placeholder="Jan" required />
            </label>
            <label>
              Priezvisko
              <input type="text" name="last_name" placeholder="Novak" required />
            </label>
            <label>
              Email
              <input type="email" name="email" placeholder="vas@email.sk" required />
            </label>
            <label>
              Heslo
              <input type="password" name="password" placeholder="******" required />
            </label>
          </div>
          <button class="primary-button" type="submit">Vytvorit ucet</button>
        </form>
      </section>
    <?php
});
