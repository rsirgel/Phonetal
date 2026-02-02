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
$user = Auth::user();
$step = filter_input(INPUT_GET, 'step', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$step = $step === 'billing' ? 'billing' : 'summary';
$inputSource = $_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET;
$selectedDays = filter_input($inputSource, 'rental_days', FILTER_VALIDATE_INT);
if (!in_array($selectedDays, $durations, true)) {
    $selectedDays = null;
}

$deviceIds = filter_input($inputSource, 'device_id', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
if ($deviceIds === null || $deviceIds === false) {
    $singleDeviceId = filter_input($inputSource, 'device_id', FILTER_VALIDATE_INT);
    $deviceIds = $singleDeviceId ? [$singleDeviceId] : [];
}
$deviceIds = is_array($deviceIds) ? array_values(array_unique(array_filter($deviceIds))) : [];

$orderComplete = false;
$orderMessage = null;
$orderErrors = [];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    if ($selectedDays === null) {
        $orderErrors[] = 'Vyberte platnú dobu prenájmu.';
    }
    if ($deviceIds === []) {
        $orderErrors[] = 'Vyberte zariadenie na prenájom.';
    }

    $consent = filter_input(INPUT_POST, 'consent', FILTER_VALIDATE_BOOLEAN);
    if ($consent !== true) {
        $orderErrors[] = 'Na dokončenie prenájmu musíte súhlasiť s podmienkami.';
    }

    $unavailableDevices = array_filter(
        $selectedDevices,
        static fn(array $device): bool => ($device['status'] ?? 'dostupne') !== 'dostupne'
    );
    if ($unavailableDevices !== []) {
        $orderErrors[] = 'Niektoré vybrané zariadenia už nie sú dostupné.';
    }
    if ($selectedDevices === []) {
        $orderErrors[] = 'Vybrané zariadenie sa nepodarilo načítať. Skúste to prosím znova.';
    } elseif (count($selectedDevices) !== count($deviceIds)) {
        $orderErrors[] = 'Niektoré vybrané zariadenia sa nepodarilo načítať. Skúste to prosím znova.';
    }

    if ($orderErrors === []) {
        if (!$user || empty($user['id'])) {
            $orderErrors[] = 'Prihlásený používateľ nie je dostupný.';
        }
    }

    if ($orderErrors === []) {
        $fullName = trim((string) filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $phone = trim((string) filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $street = trim((string) filter_input(INPUT_POST, 'street', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $city = trim((string) filter_input(INPUT_POST, 'city', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $postalCode = trim((string) filter_input(INPUT_POST, 'postal_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $postalCodeDigits = preg_replace('/\D+/', '', $postalCode ?? '');
        $postalCodeNormalized = null;
        if ($postalCodeDigits !== '') {
            if (strlen($postalCodeDigits) !== 5) {
                $orderErrors[] = 'PSČ musí mať 5 číslic.';
            } else {
                $postalCodeNormalized = $postalCodeDigits;
            }
        }

        $userUpdates = [];
        if ($phone !== '') {
            $userUpdates['telefon'] = $phone;
        }
        if ($street !== '') {
            $userUpdates['ulica'] = $street;
        }
        if ($city !== '') {
            $userUpdates['mesto'] = $city;
        }
        if ($postalCodeNormalized !== null) {
            $userUpdates['psc'] = $postalCodeNormalized;
        }

        if ($orderErrors === []) {
            $items = [];
            foreach ($selectedDevices as $device) {
                $items[] = [
                    'device_id' => (int) $device['id'],
                    'price_per_day' => (float) ($device['price_per_day'] ?? 0.0),
                ];
            }

            try {
                $database = new Database();
                if ($user && $userUpdates !== []) {
                    $database->updateUserFields((int) $user['id'], $userUpdates);
                }

                $startDate = new DateTimeImmutable('today');
                $endDate = $startDate->modify('+' . $selectedDays . ' days');
                $rentalId = $database->createRental(
                    (int) $user['id'],
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                    (float) $totalPrice,
                    $items
                );

                $orderComplete = true;
                $orderMessage = 'Prenájom bol úspešne uložený. Číslo objednávky: #' . $rentalId . '.';
            } catch (Throwable $exception) {
                $errorReference = bin2hex(random_bytes(4));
                error_log(
                    sprintf(
                        'Prenajom sa nepodarilo ulozit (%s): %s',
                        $errorReference,
                        $exception->getMessage()
                    )
                );
                $orderErrors[] = 'Prenájom sa nepodarilo uložiť. Skúste to prosím neskôr. Kód: ' . $errorReference . '.';
                if (Auth::isAdmin()) {
                    $orderErrors[] = 'Detail chyby: ' . $exception->getMessage();
                }
            }
        }
    }
}

$page->render(function () use ($selectedDevices, $durations, $isLoggedIn, $step, $selectedDays, $deviceIds, $totalPerDay, $formattedTotal, $user, $orderComplete, $orderErrors, $orderMessage): void {
    $fullName = $user['name'] ?? '';
    $email = $user['email'] ?? '';
    $phone = $user['phone'] ?? '';
    $street = $user['street'] ?? '';
    $city = $user['city'] ?? '';
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
              <?php if ($orderComplete): ?>
                <div class="feature-card">
                  <h3>Ďakujeme za objednávku</h3>
                  <p><?= htmlspecialchars($orderMessage ?? 'Prenájom bol uložený.', ENT_QUOTES, 'UTF-8') ?></p>
                  <a class="primary-button" href="zariadenia.php">Späť na katalóg</a>
                </div>
              <?php else: ?>
                <?php if ($orderErrors !== []): ?>
                  <div class="feature-card">
                    <h3>Objednávku sa nepodarilo dokončiť</h3>
                    <ul>
                      <?php foreach ($orderErrors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endif; ?>
                <form class="order-form" method="post" action="kosik.php?step=billing">
                  <input type="hidden" name="step" value="confirm" />
                  <input type="hidden" name="rental_days" value="<?= (int) $selectedDays ?>" />
                  <?php foreach ($deviceIds as $deviceId): ?>
                    <input type="hidden" name="device_id[]" value="<?= (int) $deviceId ?>" />
                  <?php endforeach; ?>
                  <div class="form-grid">
                    <label>
                      Meno a priezvisko
                      <input type="text" name="full_name" placeholder="Ján Novák" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" required />
                    </label>
                    <label>
                      Email
                      <input type="email" name="email" placeholder="vas@email.sk" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required />
                    </label>
                    <label>
                      Telefón
                      <input type="tel" name="phone" placeholder="+421 900 000 000" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>" required />
                    </label>
                    <label>
                      Fakturačná adresa
                      <input type="text" name="street" placeholder="Ulica a číslo" value="<?= htmlspecialchars($street, ENT_QUOTES, 'UTF-8') ?>" required />
                    </label>
                    <label>
                      Mesto
                      <input type="text" name="city" placeholder="Bratislava" value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>" required />
                    </label>
                    <label>
                      PSČ
                      <input type="text" name="postal_code" placeholder="811 01" required />
                    </label>
                    <label>
                      Dodacia adresa
                      <input type="text" name="delivery_address" placeholder="Ak je iná ako fakturačná" />
                    </label>
                    <label>
                      Poznámka pre kuriéra
                      <input type="text" name="courier_note" placeholder="Kontaktovať pred doručením" />
                    </label>
                  </div>
                  <div class="checkbox">
                    <input type="checkbox" name="consent" value="1" required />
                    Súhlasím so všeobecnými podmienkami a spracovaním údajov.
                  </div>
                  <button class="primary-button" type="submit">Odoslať objednávku</button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>
      </section>
    <?php
});