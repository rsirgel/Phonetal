<?php
require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/device-filter.php';
require_once __DIR__ . '/models/device-card.php';
require_once __DIR__ . '/config/database.php';

$deviceId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pageTitle = $deviceId ? 'Phonetal | Detail zariadenia' : 'Phonetal | Zariadenia na prenájom';
$pageDescription = $deviceId
    ? 'Detail zariadenia s fotkami, popisom a recenziami.'
    : 'Prehľad zariadení na prenájom: mobilné telefóny, tablety, slúchadlá a smart hodinky.';

$page = new Page(
    $pageTitle,
    $pageDescription,
    'zariadenia.php'
);

$deviceId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

function buildDeviceImage(string $title, string $subtitle): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeSubtitle = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="420" viewBox="0 0 640 420">'
        . '<defs><linearGradient id="grad" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0%" stop-color="#f7f7f7" />'
        . '<stop offset="100%" stop-color="#ececec" />'
        . '</linearGradient></defs>'
        . '<rect width="640" height="420" fill="url(#grad)" />'
        . '<rect x="28" y="28" width="584" height="364" rx="24" fill="#ffffff" stroke="#e5e5e5" />'
        . '<text x="320" y="195" text-anchor="middle" font-family="Poppins, Arial, sans-serif" '
        . 'font-size="28" fill="#111111">' . $safeTitle . '</text>'
        . '<text x="320" y="235" text-anchor="middle" font-family="Roboto, Arial, sans-serif" '
        . 'font-size="18" fill="#666666">' . $safeSubtitle . '</text>'
        . '</svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function buildDeviceGallery(string $deviceName): array
{
    return [
        [
            'src' => buildDeviceImage($deviceName, 'Predný pohľad'),
            'alt' => $deviceName . ' predná strana',
            'caption' => 'Predný pohľad a bezrámikový displej.',
        ],
        [
            'src' => buildDeviceImage($deviceName, 'Detail zadnej strany'),
            'alt' => $deviceName . ' zadná strana',
            'caption' => 'Prémiové materiály a ergonomický dizajn.',
        ],
        [
            'src' => buildDeviceImage($deviceName, 'Obsah balenia'),
            'alt' => $deviceName . ' príslušenstvo',
            'caption' => 'Doručenie s ochranným obalom a nabíjačkou.',
        ],
    ];
}

function buildDeviceDetail(array $device): array
{
    $typeLabel = $device['type'] ?? 'zariadenie';
    $detailParts = [];
    if (!empty($device['ram'])) $detailParts[] = $device['ram'] . ' GB RAM';
    if (!empty($device['display'])) $detailParts[] = $device['display'];
    $details = $device['details'] ?? ($detailParts ? implode(' • ', $detailParts) : 'Parametre budú doplnené.');

    return [
        'details' => $details,
        'summary' => sprintf(
            'Získajte %s pripravené na okamžité použitie. Ponúkame flexibilný prenájom, ' .
            'technickú podporu a expresné doručenie do 24 hodín.',
            $device['name']
        ),
        'highlights' => [
            'Flexibilný prenájom od 7 dní.',
            'Poistenie a servis v cene prenájmu.',
            'Doručenie kuriérom alebo osobný odber.',
        ],
        'specs' => [
            'Typ zariadenia' => ucfirst($typeLabel),
            'RAM' => !empty($device['ram']) ? $device['ram'] . ' GB' : '—',
            'Uhlopriečka' => !empty($device['display']) ? $device['display'] : '—',
            'Dostupnosť' => 'Skladom',
            'Cena' => $device['price'],
        ],
        'gallery' => buildDeviceGallery($device['name']),
        'reviews' => [
            [
                'author' => 'Lucia K.',
                'rating' => 5,
                'text' => 'Zariadenie prišlo pripravené na použitie a v top stave. Oceňujem rýchle doručenie.',
            ],
            [
                'author' => 'Martin P.',
                'rating' => 4,
                'text' => 'Prenájom bol bezproblémový. Komunikácia s podporou bola veľmi rýchla.',
            ],
            [
                'author' => 'Simona R.',
                'rating' => 5,
                'text' => 'Skvelá voľba na krátkodobý projekt. Určite využijem znovu.',
            ],
        ],
    ];
}

$fallbackDevices = [
    1 => [
        'id' => 1,
        'name' => 'iPhone 15 Pro',
        'type' => 'telefon',
        'ram' => '8',
        'display' => '6.1"',
        'price' => 'od 19 €/deň',
    ],
    2 => [
        'id' => 2,
        'name' => 'Samsung Galaxy S24',
        'type' => 'telefon',
        'ram' => '8',
        'display' => '6.7"',
        'price' => 'od 16 €/deň',
    ],
    3 => [
        'id' => 3,
        'name' => 'iPad Pro 12.9"',
        'type' => 'tablet',
        'ram' => '16',
        'display' => '12.9"',
        'price' => 'od 18 €/deň',
    ],
    4 => [
        'id' => 4,
        'name' => 'Galaxy Tab S9',
        'type' => 'tablet',
        'ram' => '12',
        'display' => '11"',
        'price' => 'od 14 €/deň',
    ],
    5 => [
        'id' => 5,
        'name' => 'Apple AirPods Max',
        'type' => 'prislusenstvo',
        'ram' => '',
        'display' => '',
        'price' => 'od 8 €/deň',
    ],
];

$device = null;
$devices = [];
$filterOptions = [
    'typy' => [],
    'znacky' => [],
    'ram' => [],
    'uhlopriecky' => [],
];
$selectedFilters = [
    'typy' => [],
    'znacky' => [],
    'ram' => [],
    'uhlopriecky' => [],
];

if ($deviceId && isset($fallbackDevices[$deviceId])) {
    $device = $fallbackDevices[$deviceId];
    $device = array_merge($device, buildDeviceDetail($device));
}

if ($deviceId) {
    try {
        $database = new Database();
        $deviceFromDb = $database->fetchDeviceById($deviceId);
        if ($deviceFromDb) {
            $device = array_merge($deviceFromDb, buildDeviceDetail($deviceFromDb));
        }
    } catch (Throwable $exception) {
        // Fallback data is already set above when available.
    }
} else {
    try {
        $database = new Database();
        $filterOptions = $database->fetchFilterOptions();
        $selectedFilters = [
            'typy' => array_values(filter_input(INPUT_GET, 'typy', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? []),
            'znacky' => array_values(filter_input(INPUT_GET, 'znacky', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? []),
            'ram' => array_values(filter_input(INPUT_GET, 'ram', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? []),
            'uhlopriecky' => array_values(filter_input(INPUT_GET, 'uhlopriecky', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? []),
        ];
        $devices = $database->fetchDevicesByFilters($selectedFilters);
    } catch (Throwable $exception) {
        $devices = [];
    }
}

$page->render(function () use ($device, $deviceId, $devices, $filterOptions, $selectedFilters): void {
    ?>
      <?php if (!$deviceId): ?>
        <section class="page-hero">
          <div>
            <p class="eyebrow">Katalóg zariadení</p>
            <h1>Zariadenia na prenájom</h1>
            <p>
              Vyberte si z dostupných zariadení podľa typu, značky alebo parametrov. Zobrazené sú iba
              položky, ktoré máme momentálne skladom.
            </p>
          </div>
          <div class="page-hero-card">
            <h2>Rýchle filtre</h2>
            <ul>
              <li>Minimálny prenájom: 7 dní</li>
              <li>Maximálny prenájom: 60 dní</li>
              <li>Dostupnosť: skladom</li>
              <li>Platba: Google Pay, Apple Pay, PayPal</li>
            </ul>
          </div>
        </section>

        <section class="section device-section">
          <div class="device-layout">
            <?php renderDeviceFilter($filterOptions, $selectedFilters); ?>
            <div class="device-list">
              <div class="section-heading">
                <h2>Aktuálne dostupné zariadenia</h2>
                <p>Vyberte si zariadenie z databázy podľa parametrov, ktoré vám vyhovujú.</p>
              </div>
              <div class="product-grid">
                <?php if ($devices === []): ?>
                  <div class="feature-card">
                    <h3>Žiadne zariadenia v ponuke</h3>
                    <p>Skúste upraviť filtre alebo sa vráťte neskôr.</p>
                  </div>
                <?php else: ?>
                  <?php foreach ($devices as $deviceItem): ?>
                    <?php renderDeviceCard($deviceItem); ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>
      <?php elseif (!$device): ?>
        <section class="page-hero">
          <div>
            <p class="eyebrow">Detail zariadenia</p>
            <h1>Zariadenie nenájdené</h1>
            <p>Skontrolujte prosím odkaz alebo sa vráťte do katalógu zariadení.</p>
          </div>
          <div class="page-hero-card">
            <h2>Čo získate</h2>
            <ul>
              <li>Flexibilné dĺžky prenájmu</li>
              <li>Expresné doručenie do 24 hodín</li>
              <li>Poistenie a servis v cene</li>
              <li>Možnosť výmeny počas prenájmu</li>
            </ul>
          </div>
        </section>
        <section class="section">
          <div class="feature-card">
            <h2>Zariadenie sa nepodarilo načítať</h2>
            <p>Vráťte sa do katalógu a vyberte si iné zariadenie.</p>
            <a class="primary-button" href="zariadenia.php">Späť na katalóg</a>
          </div>
        </section>
      <?php else: ?>
        <section class="page-hero">
          <div>
            <p class="eyebrow">Detail zariadenia</p>
            <h1><?= htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars($device['summary'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="page-hero-card">
            <h2>Čo získate</h2>
            <ul>
              <li>Flexibilné dĺžky prenájmu</li>
              <li>Expresné doručenie do 24 hodín</li>
              <li>Poistenie a servis v cene</li>
              <li>Možnosť výmeny počas prenájmu</li>
            </ul>
          </div>
        </section>

        <section class="device-detail-hero">
          <div>
            <p class="eyebrow"><?= htmlspecialchars(ucfirst($device['type']), ENT_QUOTES, 'UTF-8') ?></p>
            <h2><?= htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars($device['details'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="device-price"><?= htmlspecialchars($device['price'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="device-cta">
              <a class="primary-button" href="kosik.php?device_id=<?= (int) $device['id'] ?>">Prenajať</a>
              <a class="ghost-button" href="zariadenia.php">Späť na katalóg</a>
            </div>
            <ul class="feature-list">
              <?php foreach ($device['highlights'] as $highlight): ?>
                <li><?= htmlspecialchars($highlight, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="device-gallery">
            <?php foreach ($device['gallery'] as $image): ?>
              <figure>
                <img src="<?= htmlspecialchars($image['src'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($image['alt'], ENT_QUOTES, 'UTF-8') ?>" />
                <figcaption><?= htmlspecialchars($image['caption'], ENT_QUOTES, 'UTF-8') ?></figcaption>
              </figure>
            <?php endforeach; ?>
          </div>
        </section>
        <section class="section device-detail-grid">
          <div class="device-detail-card">
            <h3>Popis zariadenia</h3>
            <p><?= htmlspecialchars($device['summary'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="device-detail-card">
            <h3>Parametre</h3>
            <div class="device-specs">
              <?php foreach ($device['specs'] as $label => $value): ?>
                <div>
                  <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                  <strong><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </section>
        <section class="section">
          <div class="section-heading">
            <h2>Recenzie</h2>
            <p>Skúsenosti klientov s prenájmom tohto zariadenia.</p>
          </div>
          <div class="review-list">
            <?php foreach ($device['reviews'] as $review): ?>
              <article class="review-card">
                <div class="review-rating">
                  <?= str_repeat('★', (int) $review['rating']) ?><?= str_repeat('☆', 5 - (int) $review['rating']) ?>
                </div>
                <strong><?= htmlspecialchars($review['author'], ENT_QUOTES, 'UTF-8') ?></strong>
                <p><?= htmlspecialchars($review['text'], ENT_QUOTES, 'UTF-8') ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    <?php
});