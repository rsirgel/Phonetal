<?php

require_once __DIR__ . '/models/page.php';
require_once __DIR__ . '/models/auth.php';
require_once __DIR__ . '/config/database.php';

Auth::init();
$user = Auth::user();

if (!$user) {
    header('Location: login.php');
    exit;
}

$rentals = [];
$rentalsError = '';

try {
    $database = new Database();
    $rentals = $database->fetchUserRentals((int) ($user['id'] ?? 0));
} catch (Throwable $exception) {
    $rentalsError = 'Objednávky sa momentálne nepodarilo načítať. Skúste to prosím neskôr.';
}

$today = new DateTimeImmutable('today');
$activeRentals = [];
$historyRentals = [];
$totalSpent = 0.0;

foreach ($rentals as $rental) {
    $totalSpent += (float) ($rental['total_price_raw'] ?? 0.0);
    $endDate = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($rental['end_date_raw'] ?? '')) ?: $today;
    $isHistory = ($rental['status'] ?? '') === 'zruseny'
        || ($rental['status'] ?? '') === 'ukonceny'
        || $endDate < $today;

    if ($isHistory) {
        $historyRentals[] = $rental;
        continue;
    }

    $activeRentals[] = $rental;
}

$profileFields = [
    'first_name' => $user['first_name'] ?? null,
    'last_name' => $user['last_name'] ?? null,
    'email' => $user['email'] ?? null,
    'phone' => $user['phone'] ?? null,
    'street' => $user['street'] ?? null,
    'city' => $user['city'] ?? null,
    'postal_code' => $user['postal_code'] ?? null,
    'iban' => $user['iban'] ?? null,
    'bic' => $user['bic'] ?? null,
    'account_owner' => $user['account_owner'] ?? null,
];

$filledProfileFields = 0;
foreach ($profileFields as $value) {
    if (trim((string) $value) !== '') {
        $filledProfileFields++;
    }
}
$profileCompletion = (int) round(($filledProfileFields / max(count($profileFields), 1)) * 100);

$page = new Page(
    'Phonetal | Dashboard používateľa',
    'Používateľský dashboard s aktívnymi objednávkami, históriou prenájmov a fakturačnými údajmi.',
    'user.php'
);

$page->render(function () use ($user, $activeRentals, $historyRentals, $rentalsError, $totalSpent, $profileCompletion): void {
    $escapeValue = static function (?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    };

    $renderStatusBadge = static function (string $status): array {
        return match ($status) {
            'ukonceny' => ['label' => 'Ukončená', 'class' => 'status-badge status-completed'],
            'zruseny' => ['label' => 'Zrušená', 'class' => 'status-badge status-cancelled'],
            default => ['label' => 'Aktívna', 'class' => 'status-badge status-active'],
        };
    };
    ?>
      <section class="page-hero dashboard-hero">
        <div>
          <p class="eyebrow">Môj dashboard</p>
          <h1>Vitajte späť, <?= htmlspecialchars($user['first_name'] ?? $user['name'] ?? 'zákazník', ENT_QUOTES, 'UTF-8') ?>.</h1>
          <p>
            Na jednom mieste vidíte aktívne objednávky, históriu prenájmov aj pripravenosť
            profilu na ďalší nákup.
          </p>
          <div class="hero-actions">
            <a class="primary-button" href="#orders">Zobraziť objednávky</a>
            <a class="ghost-button" href="zariadenia.php">Prenajať ďalšie zariadenie</a>
          </div>
        </div>
        <div class="page-hero-card dashboard-summary-card">
          <h2>Rýchly prehľad</h2>
          <ul>
            <li>Aktívne objednávky: <strong><?= count($activeRentals) ?></strong></li>
            <li>V histórii: <strong><?= count($historyRentals) ?></strong></li>
            <li>Kompletnosť profilu: <strong><?= $profileCompletion ?> %</strong></li>
            <li>Celkom minuté: <strong><?= number_format($totalSpent, 2, ',', ' ') ?> €</strong></li>
          </ul>
        </div>
      </section>

      <section class="section" id="orders">
        <div class="section-heading">
          <h2>Prehľad objednávok</h2>
          <p>Aktuálne prenájmy sledujte hore, staršie objednávky nájdete v histórii nižšie.</p>
        </div>

        <div class="dashboard-stats-grid">
          <article class="dashboard-stat-card">
            <span>Aktívne objednávky</span>
            <strong><?= count($activeRentals) ?></strong>
            <p>Zariadenia, ktoré sú práve v prenájme alebo čakajú na ukončenie.</p>
          </article>
          <article class="dashboard-stat-card">
            <span>História objednávok</span>
            <strong><?= count($historyRentals) ?></strong>
            <p>Dokončené alebo staršie prenájmy, ku ktorým sa viete kedykoľvek vrátiť.</p>
          </article>
          <article class="dashboard-stat-card">
            <span>Pripravenosť profilu</span>
            <strong><?= $profileCompletion ?> %</strong>
            <p>Doplnený profil zrýchli checkout a zníži počet krokov pri ďalšej objednávke.</p>
          </article>
        </div>

        <?php if ($rentalsError): ?>
          <p class="form-message"><?= htmlspecialchars($rentalsError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <div class="dashboard-order-layout">
          <div class="feature-card profile-card dashboard-order-column">
            <div class="dashboard-column-header">
              <h3>Aktívne objednávky</h3>
              <a href="zariadenia.php">+ Nová objednávka</a>
            </div>
            <?php if ($activeRentals === []): ?>
              <div class="dashboard-empty-state">
                <h4>Zatiaľ nemáte aktívny prenájom</h4>
                <p>Vyberte si zariadenie z katalógu a nová objednávka sa zobrazí práve tu.</p>
              </div>
            <?php else: ?>
              <div class="dashboard-order-list">
                <?php foreach ($activeRentals as $rental): ?>
                  <?php $statusBadge = $renderStatusBadge((string) ($rental['status'] ?? 'aktivny')); ?>
                  <article class="dashboard-order-card">
                    <div class="dashboard-order-topline">
                      <strong>Objednávka #<?= (int) $rental['id'] ?></strong>
                      <span class="<?= $statusBadge['class'] ?>"><?= htmlspecialchars($statusBadge['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <p><?= htmlspecialchars($rental['date_range'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="dashboard-order-meta">
                      <span><?= htmlspecialchars($rental['timeline_label'], ENT_QUOTES, 'UTF-8') ?></span>
                      <span><?= htmlspecialchars($rental['total_price'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="feature-card profile-card dashboard-order-column">
            <div class="dashboard-column-header">
              <h3>História objednávok</h3>
              <span>Vaše minulé prenájmy</span>
            </div>
            <?php if ($historyRentals === []): ?>
              <div class="dashboard-empty-state">
                <h4>História je zatiaľ prázdna</h4>
                <p>Po ukončení prvej objednávky sa tu zobrazí prehľad minulých prenájmov.</p>
              </div>
            <?php else: ?>
              <div class="dashboard-order-list">
                <?php foreach ($historyRentals as $rental): ?>
                  <?php $statusBadge = $renderStatusBadge((string) ($rental['status'] ?? 'ukonceny')); ?>
                  <article class="dashboard-order-card dashboard-order-card-history">
                    <div class="dashboard-order-topline">
                      <strong>Objednávka #<?= (int) $rental['id'] ?></strong>
                      <span class="<?= $statusBadge['class'] ?>"><?= htmlspecialchars($statusBadge['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <p><?= htmlspecialchars($rental['date_range'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="dashboard-order-meta">
                      <span><?= htmlspecialchars($rental['timeline_label'], ENT_QUOTES, 'UTF-8') ?></span>
                      <span><?= htmlspecialchars($rental['total_price'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="section dashboard-tools-section">
        <div class="section-heading">
          <h2>Ďalšie užitočné sekcie</h2>
          <p>Pridal som aj veci, ktoré zákazníkovi pomôžu rýchlejšie pokračovať v ďalšej objednávke.</p>
        </div>
        <div class="feature-grid dashboard-tools-grid">
          <article class="feature-card profile-card">
            <h3>Odporúčané ďalšie kroky</h3>
            <ul class="dashboard-bullet-list">
              <li>Doplňte všetky fakturačné údaje, aby ste pri ďalšom prenájme vypĺňali minimum polí.</li>
              <li>Skontrolujte telefón a adresu, aby kuriér vedel zásielku doručiť bez zdržania.</li>
              <li>Ak plánujete firemný prenájom, kontaktujte podporu cez stránku Kontakt.</li>
            </ul>
          </article>
          <article class="feature-card profile-card">
            <h3>Rýchle akcie</h3>
            <div class="auth-actions">
              <a class="primary-button" href="zariadenia.php">Nový prenájom</a>
              <a class="ghost-button" href="kontakt.php">Kontaktovať podporu</a>
            </div>
          </article>
        </div>
      </section>

      <section class="section">
        <div class="section-heading">
          <h2>Fakturačné údaje</h2>
          <p>Údaje sa ukladajú po opustení poľa, aby bol ďalší checkout čo najrýchlejší.</p>
        </div>
        <div class="feature-grid">
          <div class="feature-card profile-card">
            <h3>Osobné a platobné údaje</h3>
            <div class="profile-form profile-grid">
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
        </div>
      </section>
      <script>
        const profileInputs = document.querySelectorAll('[data-profile-field]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const updateField = async (field, value) => {
          try {
            const response = await fetch('update-profile.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
              },
              body: JSON.stringify({ field, value }),
            });
            if (!response.ok) {
              return;
            }
            await response.json();
          } catch (error) {
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
