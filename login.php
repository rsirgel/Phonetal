<?php
require_once __DIR__ . '/models/page.php';
require_once __DIR__ . '/models/auth.php';
require_once __DIR__ . '/models/recaptcha.php';
require_once __DIR__ . '/models/google-auth.php';

Auth::init();
$message = '';
$recaptchaEnabled = Recaptcha::isConfigured();
$recaptchaSiteKey = Recaptcha::siteKey();

$authError = (string) ($_GET['auth_error'] ?? '');
if ($authError !== '') {
    $googleErrors = [
        'google_not_configured' => 'Google prihlasenie nie je nakonfigurovane.',
        'google_state' => 'Prihlasovanie cez Google vyprsalo. Skuste to znova.',
        'google_code' => 'Google neposlal autorizacny kod.',
        'google_failed' => 'Nepodarilo sa overit Google ucet.',
        'google_email' => 'Google ucet nema overeny email.',
        'google_no_account' => 'Pre tento Google email neexistuje ucet v systeme.',
    ];
    if (isset($googleErrors[$authError])) {
        $message = $googleErrors[$authError];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
        $message = 'Neplatny bezpecnostny token. Obnovte stranku a skuste znova.';
    } else {
        if ($recaptchaEnabled) {
            $captchaToken = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
            $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;
            if (!Recaptcha::verifyToken($captchaToken, is_string($remoteIp) ? $remoteIp : null, 'login', 0.5)) {
                $message = 'Overenie CAPTCHA zlyhalo. Skuste to znova.';
            }
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($message === '' && Auth::login($email, $password)) {
            header('Location: kosik.php');
            exit;
        }

        if ($message === '') {
            $message = 'Nespravny email alebo heslo. Skuste to znova.';
        }
    }
}

$page = new Page(
    'Phonetal | Prihlasenie',
    'Prihlaste sa pre dokoncenie objednavky a pristup k profilu.',
    'login.php'
);

$page->render(function () use ($message, $recaptchaEnabled, $recaptchaSiteKey): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Prihlasenie</p>
          <h1>Vstupte do svojho uctu</h1>
          <p>Prihlasenim ziskate pristup ku kosiku a platbam.</p>
        </div>
        <div class="page-hero-card">
          <h2>Bezpecne prihlasenie</h2>
          <ul>
            <li>Udaje sa overuju v databaze</li>
            <li>Ziskate rychlejsi checkout</li>
            <li>Pristup k administracii pre adminov</li>
          </ul>
        </div>
      </section>

      <section class="section">
        <div class="section-heading">
          <h2>Prihlasit sa</h2>
          <p>Zadajte prihlasovacie udaje.</p>
        </div>
        <div class="social-login-wrap">
          <a class="ghost-button auth-google-button" href="google-login.php">Prihlasit cez Google</a>
          <p class="social-login-divider">alebo pokracujte emailom a heslom</p>
        </div>
        <?php if ($message): ?>
          <p class="form-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form class="order-form" method="post">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>" />
          <?php if ($recaptchaEnabled): ?>
            <input type="hidden" name="g-recaptcha-response" value="" />
          <?php endif; ?>
          <div class="form-grid">
            <label>
              Email
              <input type="email" name="email" placeholder="vas@email.sk" required />
            </label>
            <label>
              Heslo
              <input type="password" name="password" placeholder="******" required />
            </label>
          </div>
          <button class="primary-button" type="submit">Prihlasit</button>
        </form>
        <?php if ($recaptchaEnabled): ?>
          <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8') ?>"></script>
          <script>
            (() => {
              const form = document.querySelector('form.order-form');
              if (!form || typeof grecaptcha === 'undefined') {
                return;
              }

              const tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
              if (!tokenInput) {
                return;
              }

              form.addEventListener('submit', (event) => {
                if (form.dataset.recaptchaSubmitting === '1') {
                  form.dataset.recaptchaSubmitting = '0';
                  return;
                }

                event.preventDefault();
                grecaptcha.ready(() => {
                  grecaptcha.execute('<?= htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8') ?>', { action: 'login' })
                    .then((token) => {
                      tokenInput.value = token;
                      form.dataset.recaptchaSubmitting = '1';
                      if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                      } else {
                        form.submit();
                      }
                    });
                });
              });
            })();
          </script>
        <?php endif; ?>
      </section>
    <?php
});
