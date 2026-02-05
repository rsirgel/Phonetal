<?php
require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/config/database.php';

Auth::init();
$user = Auth::user();
$isAdmin = $user && $user['role'] === 'admin';
$message = '';
$error = '';
$formData = [
    'znacka' => '',
    'model' => '',
    'typ_zariadenia' => '',
    'velkost_displeja' => '',
    'ram' => '',
    'pamat' => '',
    'rok_vydania' => '',
    'softver' => '',
    'cena_za_den' => '',
    'zaloha' => '',
    'popis' => '',
    'stav' => 'dostupne',
];

$deviceTypes = ['telefon', 'tablet', 'hodinky', 'sluchadla', 'prislusenstvo'];
$deviceStatuses = ['dostupne', 'nedostupne'];
$users = [];
$usersError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        $error = 'Na pridanie zariadenia potrebujete admin práva.';
    } elseif (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Neplatný bezpečnostný token. Obnovte stránku a skúste znova.';
    } else {
        $formData = [
            'znacka' => trim((string) ($_POST['znacka'] ?? '')),
            'model' => trim((string) ($_POST['model'] ?? '')),
            'typ_zariadenia' => trim((string) ($_POST['typ_zariadenia'] ?? '')),
            'velkost_displeja' => trim((string) ($_POST['velkost_displeja'] ?? '')),
            'ram' => trim((string) ($_POST['ram'] ?? '')),
            'pamat' => trim((string) ($_POST['pamat'] ?? '')),
            'rok_vydania' => trim((string) ($_POST['rok_vydania'] ?? '')),
            'softver' => trim((string) ($_POST['softver'] ?? '')),
            'cena_za_den' => trim((string) ($_POST['cena_za_den'] ?? '')),
            'zaloha' => trim((string) ($_POST['zaloha'] ?? '')),
            'popis' => trim((string) ($_POST['popis'] ?? '')),
            'stav' => trim((string) ($_POST['stav'] ?? 'dostupne')),
        ];

        if ($formData['znacka'] === '' || $formData['model'] === '' || $formData['typ_zariadenia'] === '' || $formData['cena_za_den'] === '') {
            $error = 'Vyplňte značku, model, typ zariadenia a cenu za deň.';
        } elseif (!in_array($formData['typ_zariadenia'], $deviceTypes, true)) {
            $error = 'Neplatný typ zariadenia.';
        } elseif (!in_array($formData['stav'], $deviceStatuses, true)) {
            $error = 'Neplatný stav zariadenia.';
        } else {
            $payload = [
                'znacka' => $formData['znacka'],
                'model' => $formData['model'],
                'typ_zariadenia' => $formData['typ_zariadenia'],
                'velkost_displeja' => $formData['velkost_displeja'] !== '' ? $formData['velkost_displeja'] : null,
                'ram' => $formData['ram'] !== '' ? (int) $formData['ram'] : null,
                'pamat' => $formData['pamat'] !== '' ? (int) $formData['pamat'] : null,
                'rok_vydania' => $formData['rok_vydania'] !== '' ? (int) $formData['rok_vydania'] : null,
                'softver' => $formData['softver'] !== '' ? $formData['softver'] : null,
                'cena_za_den' => (float) $formData['cena_za_den'],
                'zaloha' => $formData['zaloha'] !== '' ? (float) $formData['zaloha'] : 0.0,
                'popis' => $formData['popis'] !== '' ? $formData['popis'] : null,
                'stav' => $formData['stav'],
            ];

            try {
                $database = new Database();
                $deviceId = $database->createDevice($payload);
                $message = 'Zariadenie bolo pridané (ID: ' . $deviceId . ').';
                $formData = array_merge($formData, ['model' => '', 'znacka' => '', 'velkost_displeja' => '', 'ram' => '', 'pamat' => '', 'rok_vydania' => '', 'softver' => '', 'cena_za_den' => '', 'zaloha' => '', 'popis' => '']);
            } catch (Throwable $exception) {
                $error = 'Zariadenie sa nepodarilo uložiť. Skúste to neskôr.';
            }
        }
    }
}

$database = null;
if ($isAdmin) {
    try {
        $database = new Database();
        $users = $database->fetchUsers();
    } catch (Throwable $exception) {
        $usersError = 'Používateľov sa nepodarilo načítať. Skúste to neskôr.';
    }
}

$page = new Page(
    'Phonetal | Admin panel',
    'Administrácia zariadení, prenájmov a používateľov.',
    'admin.php'
);

$page->render(function () use ($user, $isAdmin, $message, $error, $formData, $deviceTypes, $deviceStatuses, $users, $usersError): void {
    ?>
      <section class="section">
        <?php if (!$isAdmin): ?>
          <p class="form-message">Nemáte oprávnenie na prístup do admin panelu.</p>
          <a class="primary-button" href="login.php">Prihlásiť ako admin</a>
        <?php else: ?>
          <?php if ($message): ?>
            <p class="form-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
          <?php if ($error): ?>
            <p class="form-message"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
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
              <p>Správa účtov.</p>
            </div>
          </div>
          <div class="section-heading">
            <h2>Čo môžete spravovať</h2>
            <p>Kontrola prenájmov a schvaľovanie platieb.</p>
            <p>Správa účtov.</p>
          </div>
          <div class="section-heading">
            <h2>Používatelia v systéme</h2>
            <p>Prehľad všetkých registrovaných účtov.</p>
          </div>
          <?php if ($usersError): ?>
            <p class="form-message"><?= htmlspecialchars($usersError, ENT_QUOTES, 'UTF-8') ?></p>
          <?php elseif ($users === []): ?>
            <p class="form-message">Zatiaľ nie sú registrovaní žiadni používatelia.</p>
          <?php else: ?>
            <div class="feature-grid">
              <?php foreach ($users as $userRow): ?>
                <div class="feature-card">
                  <h3><?= htmlspecialchars(trim(($userRow['meno'] ?? '') . ' ' . ($userRow['priezvisko'] ?? '')), ENT_QUOTES, 'UTF-8') ?></h3>
                  <p><?= htmlspecialchars($userRow['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                  <p><?= htmlspecialchars($userRow['telefon'] ?? 'Bez telefónu', ENT_QUOTES, 'UTF-8') ?></p>
                  <p><?= htmlspecialchars($userRow['mesto'] ?? 'Bez mesta', ENT_QUOTES, 'UTF-8') ?></p>
                  <p><?= htmlspecialchars($userRow['rola'] ?? 'zakaznik', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="section-heading">
            <h2>Pridať nové zariadenie</h2>
            <p>Vyplňte údaje zariadenia, ktoré chcete zaradiť do ponuky.</p>
          </div>
          <form class="order-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>" />
            <div class="form-grid">
              <label>
                Značka
                <input type="text" name="znacka" placeholder="Apple" value="<?= htmlspecialchars($formData['znacka'], ENT_QUOTES, 'UTF-8') ?>" required />
              </label>
              <label>
                Model
                <input type="text" name="model" placeholder="iPhone 15 Pro" value="<?= htmlspecialchars($formData['model'], ENT_QUOTES, 'UTF-8') ?>" required />
              </label>
              <label>
                Typ zariadenia
                <select name="typ_zariadenia" required>
                  <option value="">Vyberte typ</option>
                  <?php foreach ($deviceTypes as $type): ?>
                    <option value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" <?= $formData['typ_zariadenia'] === $type ? 'selected' : '' ?>>
                      <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                Veľkosť displeja
                <input type="text" name="velkost_displeja" placeholder="6.1&quot;" value="<?= htmlspecialchars($formData['velkost_displeja'], ENT_QUOTES, 'UTF-8') ?>" />
              </label>
              <label>
                RAM (GB)
                <input type="number" name="ram" min="0" step="1" placeholder="8" value="<?= htmlspecialchars($formData['ram'], ENT_QUOTES, 'UTF-8') ?>" />
              </label>
              <label>
                Pamäť (GB)
                <input type="number" name="pamat" min="0" step="1" placeholder="128" value="<?= htmlspecialchars($formData['pamat'], ENT_QUOTES, 'UTF-8') ?>" />
              </label>
              <label>
                Rok vydania
                <input type="number" name="rok_vydania" min="2000" step="1" placeholder="2024" value="<?= htmlspecialchars($formData['rok_vydania'], ENT_QUOTES, 'UTF-8') ?>" />
              </label>
              <label>
                Softvér
                <input type="text" name="softver" placeholder="iOS 17" value="<?= htmlspecialchars($formData['softver'], ENT_QUOTES, 'UTF-8') ?>" />
              </label>
              <label>
                Cena za deň (€)
                <input type="number" name="cena_za_den" min="0" step="0.01" placeholder="19.00" value="<?= htmlspecialchars($formData['cena_za_den'], ENT_QUOTES, 'UTF-8') ?>" required />
              </label>
              <label>
                Záloha (€)
                <input type="number" name="zaloha" min="0" step="0.01" placeholder="200.00" value="<?= htmlspecialchars($formData['zaloha'], ENT_QUOTES, 'UTF-8') ?>" />
              </label>
              <label>
                Stav
                <select name="stav">
                  <?php foreach ($deviceStatuses as $status): ?>
                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $formData['stav'] === $status ? 'selected' : '' ?>>
                      <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                Popis
                <input type="text" name="popis" placeholder="Krátky popis zariadenia" value="<?= htmlspecialchars($formData['popis'], ENT_QUOTES, 'UTF-8') ?>" />
              </label>
            </div>
            <button class="primary-button" type="submit">Pridať zariadenie</button>
          </form>
        <?php endif; ?>
      </section>
    <?php
});
