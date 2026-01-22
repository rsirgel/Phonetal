<?php
require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/config/database.php';

$page = new Page(
    'Phonetal | Nákupný košík ',
    'Prenájom zariadenia s nastavením počtu dní, kontaktnými údajmi a potvrdením objednávky.',
    'kosik.php'
);

$devices = [];
$durations = [7, 14, 30, 60];
$isLoggedIn = Auth::isLoggedIn();

try {
    $database = new Database();
    $devices = $database->fetchAvailableDevices();
} catch (Throwable $exception) {
    $devices = [
        ['value' => 1, 'label' => 'iPhone 15 Pro'],
        ['value' => 2, 'label' => 'Samsung Galaxy S24'],
    ];
}

$page->render(function () use ($devices, $durations, $isLoggedIn): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Košík</p>
          <h1>Prenájom zariadenia</h1>
          <p>Vyplňte požadované údaje a nastavte dobu prenájmu. Minimum je 7 dní.</p>
        </div>
        <div class="page-hero-card">
          <h2>Vaša objednávka</h2>
          <ul>
            <li>Zariadenie: vyberte z ponuky</li>
            <li>Doba: vyberte z ponuky</li>
            <li>Záruka počas prenájmu</li>
            <li>Doručenie do 24 hodín</li>
          </ul>
        </div>
      </section>

      <section class="section">
        <div class="section-heading">
          <h2>Formulár prenájmu</h2>
          <p>Vyberte počet dní, dôvod prenájmu a potvrďte kontakt.</p>
        </div>
        <?php if (!$isLoggedIn): ?>
          <p class="form-message">
            Pre dokončenie prenájmu sa prosím prihláste alebo zaregistrujte.
          </p>
          <div class="auth-actions">
            <a class="primary-button" href="login.php">Prihlásiť</a>
            <a class="ghost-button" href="register.php">Registrácia</a>
          </div>
        <?php else: ?>
        <form class="order-form">
          <div class="form-grid">
            <label>
              Zariadenie
              <select name="device_id" required>
                <option value="">Vyberte zariadenie</option>
                <?php foreach ($devices as $device): ?>
                  <option value="<?= (int) $device['value'] ?>">
                    <?= htmlspecialchars($device['label'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
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
            <label>
              Email
              <input type="email" placeholder="vas@email.sk" required />
            </label>
            <label>
              Telefón
              <input type="tel" placeholder="+421 900 000 000" required />
            </label>
            <label>
              Rodné číslo
              <input type="number" placeholder="123456/7890" />
            </label>
          </div>
          <div class="checkbox">
            <input type="checkbox" required />
            Súhlasím so všeobecnými podmienkami a spracovaním údajov.
          </div>
          <button class="primary-button" type="submit">Odoslať objednávku</button>
        </form>
        <?php endif; ?>
      </section>
    <?php
});
