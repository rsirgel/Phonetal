<?php
require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/config/database.php';

$page = new Page(
    'Phonetal | Nákupný košík ',
    'Prenájom zariadenia s nastavením počtu dní, kontaktnými údajmi a potvrdením objednávky.',
    'kosik.php'
);

$durations = [7, 14, 30, 60];
$isLoggedIn = Auth::isLoggedIn();
$step = filter_input(INPUT_GET, 'step', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$step = $step === 'billing' ? 'billing' : 'summary';
$selectedDays = filter_input(INPUT_GET, 'rental_days', FILTER_VALIDATE_INT);
if (!in_array($selectedDays, $durations, true)) {
    $selectedDays = null;
}

$deviceIds = filter_input(INPUT_GET, 'device_id', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
if ($deviceIds === null || $deviceIds === false) {
    $singleDeviceId = filter_input(INPUT_GET, 'device_id', FILTER_VALIDATE_INT);
    $deviceIds = $singleDeviceId ? [$singleDeviceId] : [];
}
$deviceIds = is_array($deviceIds) ? array_values(array_unique(array_filter($deviceIds))) : [];

$fallbackDevices = [
    1 => [
        'id' => 1,
        'name' => 'iPhone 15 Pro',
        'details' => '6.1" • 8 GB RAM',
        'price_per_day' => 19.0,
        'price' => 'od 19,00 €/deň',
    ],
    2 => [
        'id' => 2,
        'name' => 'Samsung Galaxy S24',
        'details' => '6.7" • 8 GB RAM',
        'price_per_day' => 16.0,
        'price' => 'od 16,00 €/deň',
    ],
    3 => [
        'id' => 3,
        'name' => 'iPad Pro 12.9"',
        'details' => '12.9" • 16 GB RAM',
        'price_per_day' => 18.0,
        'price' => 'od 18,00 €/deň',
    ],
    4 => [
        'id' => 4,
        'name' => 'Galaxy Tab S9',
        'details' => '11" • 12 GB RAM',
        'price_per_day' => 14.0,
        'price' => 'od 14,00 €/deň',
    ],
    5 => [
        'id' => 5,
        'name' => 'Apple AirPods Max',
        'details' => 'Prémiové slúchadlá',
        'price_per_day' => 8.0,
        'price' => 'od 8,00 €/deň',
    ],
];

$selectedDevices = [];
try {
    $database = new Database();
    foreach ($deviceIds as $deviceId) {
        $device = $database->fetchDeviceById((int) $deviceId);
        if (!$device && isset($fallbackDevices[$deviceId])) {
            $device = $fallbackDevices[$deviceId];
        }
        if ($device) {
            $selectedDevices[] = $device;
        }
    }
} catch (Throwable $exception) {
    foreach ($deviceIds as $deviceId) {
        if (isset($fallbackDevices[$deviceId])) {
            $selectedDevices[] = $fallbackDevices[$deviceId];
        }
    }
}

$totalPerDay = 0.0;
foreach ($selectedDevices as $device) {
    $totalPerDay += isset($device['price_per_day']) ? (float) $device['price_per_day'] : 0.0;
}
$totalPrice = $selectedDays ? $totalPerDay * $selectedDays : 0.0;
$formattedTotal = $totalPrice > 0
    ? number_format($totalPrice, 2, ',', ' ') . ' €'
    : '—';

$page->render(function () use ($selectedDevices, $durations, $isLoggedIn, $step, $selectedDays, $deviceIds, $totalPerDay, $formattedTotal): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Košík</p>
          <h1>Vybrané zariadenia</h1>
          <p>Skontrolujte zvolené zariadenia, nastavte dobu prenájmu a pokračujte k fakturácii.</p>
        </div>
        <div class="page-hero-card">
          <h2>Vaša objednávka</h2>
          <ul>
            <li>Zariadenia: podľa výberu</li>
            <li>Doba: minimum 7 dní</li>
            <li>Záruka počas prenájmu</li>
            <li>Doručenie do 24 hodín</li>
          </ul>
        </div>
      </section>

      <section class="section">
        <?php if (!$isLoggedIn): ?>
          <p class="form-message">
            Pre dokončenie prenájmu sa prosím prihláste alebo zaregistrujte.
          </p>
          <div class="auth-actions">
            <a class="primary-button" href="login.php">Prihlásiť</a>
            <a class="ghost-button" href="register.php">Registrácia</a>
          </div>
        <?php else: ?>
          <?php if ($step === 'summary'): ?>
            <div class="section-heading">
              <h2>Košík</h2>
              <p>V košíku vidíte iba zariadenia, ktoré ste si zvolili na prenájom.</p>
            </div>
            <?php if ($selectedDevices === []): ?>
              <div class="feature-card">
                <h3>Košík je zatiaľ prázdny</h3>
                <p>V katalógu si vyberte zariadenie a pridajte ho do prenájmu.</p>
                <a class="primary-button" href="zariadenia.php">Prejsť na katalóg</a>
              </div>
            <?php else: ?>
              <div class="review-list">
                <?php foreach ($selectedDevices as $device): ?>
                  <article class="review-card">
                    <strong><?= htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if (!empty($device['details'])): ?>
                      <p><?= htmlspecialchars($device['details'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <div class="review-rating"><?= htmlspecialchars($device['price'], ENT_QUOTES, 'UTF-8') ?></div>
                  </article>
                <?php endforeach; ?>
              </div>
              <form class="order-form" method="get" action="kosik.php">
                <input type="hidden" name="step" value="billing" />
                <?php foreach ($deviceIds as $deviceId): ?>
                  <input type="hidden" name="device_id[]" value="<?= (int) $deviceId ?>" />
                <?php endforeach; ?>
                <div class="form-grid">
                  <label>
                    Doba prenájmu (dni)
                    <select name="rental_days" required>
                      <option value="">Vyberte dobu</option>
                      <?php foreach ($durations as $days): ?>
                        <option value="<?= (int) $days ?>"><?= (int) $days ?> dní</option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label>
                    Dôvod prenájmu
                    <input type="text" placeholder="Testovanie, firemné použitie" />
                  </label>
                </div>
                <button class="primary-button" type="submit">Pokračovať ďalej</button>
              </form>
            <?php endif; ?>
          <?php else: ?>
            <div class="section-heading">
              <h2>Fakturačné a dodacie údaje</h2>
              <p>Skontrolujte sumu a doplňte fakturačné a dodacie informácie.</p>
            </div>
            <?php if ($selectedDevices === []): ?>
              <div class="feature-card">
                <h3>Košík je prázdny</h3>
                <p>Najprv si vyberte zariadenie na prenájom.</p>
                <a class="primary-button" href="zariadenia.php">Prejsť na katalóg</a>
              </div>
            <?php elseif (!$selectedDays): ?>
              <div class="feature-card">
                <h3>Chýba doba prenájmu</h3>
                <p>Pred pokračovaním vyberte dobu prenájmu.</p>
                <a class="primary-button" href="kosik.php?<?= http_build_query(['device_id' => $deviceIds]) ?>">Späť do košíka</a>
              </div>
            <?php else: ?>
              <div class="page-hero-card">
                <h3>Sumár objednávky</h3>
                <ul>
                  <?php foreach ($selectedDevices as $device): ?>
                    <li><?= htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8') ?></li>
                  <?php endforeach; ?>
                  <li>Doba prenájmu: <?= (int) $selectedDays ?> dní</li>
                  <li>Medzisúčet / deň: <?= number_format($totalPerDay, 2, ',', ' ') ?> €</li>
                  <li>Celková suma: <?= htmlspecialchars($formattedTotal, ENT_QUOTES, 'UTF-8') ?></li>
                </ul>
              </div>
              <form class="order-form">
                <div class="form-grid">
                  <label>
                    Meno a priezvisko
                    <input type="text" placeholder="Ján Novák" required />
                  </label>
                  <label>
                    Email
                    <input type="email" placeholder="vas@email.sk" required />
                  </label>
                  <label>
                    Telefón
                    <input type="tel" placeholder="+421 900 000 000" required />
                  </label>
                  <label>
                    Fakturačná adresa
                    <input type="text" placeholder="Ulica a číslo" required />
                  </label>
                  <label>
                    Mesto
                    <input type="text" placeholder="Bratislava" required />
                  </label>
                  <label>
                    PSČ
                    <input type="text" placeholder="811 01" required />
                  </label>
                  <label>
                    Dodacia adresa
                    <input type="text" placeholder="Ak je iná ako fakturačná" />
                  </label>
                  <label>
                    Poznámka pre kuriéra
                    <input type="text" placeholder="Kontaktovať pred doručením" />
                  </label>
                </div>
                <div class="checkbox">
                  <input type="checkbox" required />
                  Súhlasím so všeobecnými podmienkami a spracovaním údajov.
                </div>
                <button class="primary-button" type="submit">Odoslať objednávku</button>
              </form>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>
      </section>
    <?php
});
