<?php

require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/Auth.php';

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
    $escapeValue = static function (?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    };
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
            <li>Meno: <?= $escapeValue($user['name'] ?? null) ?></li>
            <li>Email: <?= $escapeValue($user['email'] ?? null) ?></li>
            <li>Rola: <?= $escapeValue($user['role'] ?? null) ?></li>
          </ul>
        </div>
      </section>

      <section class="section">
        <div class="section-heading">
          <h2>Základné údaje</h2>
          <p>Upravte svoje kontaktné údaje pre prenájmy. Zmeny sa ukladajú automaticky.</p>
          <p id="profile-save-status" aria-live="polite"></p>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h3>Osobné údaje</h3>
            <label>
              Meno
              <input type="text" data-profile-field="first_name" value="<?= $escapeValue($user['first_name'] ?? null) ?>" />
            </label>
            <label>
              Priezvisko
              <input type="text" data-profile-field="last_name" value="<?= $escapeValue($user['last_name'] ?? null) ?>" />
            </label>
            <label>
              Ulica
              <input type="text" data-profile-field="street" value="<?= $escapeValue($user['street'] ?? null) ?>" />
            </label>
            <label>
              PSČ
              <input type="text" data-profile-field="postal_code" value="<?= $escapeValue($user['postal_code'] ?? null) ?>" />
            </label>
            <label>
              Mesto
              <input type="text" data-profile-field="city" value="<?= $escapeValue($user['city'] ?? null) ?>" />
            </label>
            <label>
              Tel. číslo
              <input type="tel" data-profile-field="phone" value="<?= $escapeValue($user['phone'] ?? null) ?>" />
            </label>
            <label>
              Kontaktný email
              <input type="email" data-profile-field="email" value="<?= $escapeValue($user['email'] ?? null) ?>" />
            </label>
          </div>
          <div class="feature-card">
            <h3>Údaje pre firmy (voliteľné)</h3>
            <label>
              IBAN
              <input type="text" data-profile-field="iban" value="<?= $escapeValue($user['iban'] ?? null) ?>" />
            </label>
            <label>
              BIC
              <input type="text" data-profile-field="bic" value="<?= $escapeValue($user['bic'] ?? null) ?>" />
            </label>
            <label>
              Meno majiteľa účtu
              <input type="text" data-profile-field="account_owner" value="<?= $escapeValue($user['account_owner'] ?? null) ?>" />
            </label>
          </div>
        </div>
      </section>
      <script>
        const profileInputs = document.querySelectorAll('[data-profile-field]');
        const statusLine = document.getElementById('profile-save-status');

        const setStatus = (message, isError = false) => {
          if (!statusLine) {
            return;
          }
          statusLine.textContent = message;
          statusLine.style.color = isError ? '#b42318' : '#027a48';
        };

        const updateField = async (field, value) => {
          try {
            setStatus('Ukladám...');
            const response = await fetch('update-profile.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ field, value }),
            });
            const payload = await response.json();
            if (!response.ok) {
              setStatus(payload.error || 'Zmena sa nepodarila.', true);
              return;
            }
            setStatus('Zmeny uložené.');
          } catch (error) {
            setStatus('Zmena sa nepodarila.', true);
          }
        };

        profileInputs.forEach((input) => {
          let lastValue = input.value;
          input.addEventListener('blur', () => {
            if (input.value === lastValue) {
              return;
            }
            lastValue = input.value;
            updateField(input.dataset.profileField, input.value);
          });
        });
      </script>
    <?php
});