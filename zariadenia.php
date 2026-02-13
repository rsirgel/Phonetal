<?php
require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/device-filter.php';
require_once __DIR__ . '/models/device-card.php';
require_once __DIR__ . '/models/Auth.php';
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

Auth::init();
$currentUser = Auth::user();
$isAdmin = Auth::isAdmin();
$reviewNotice = null;
$reviewErrors = [];
$reviewForm = [
    'rating' => '',
    'comment' => '',
];
$editNotice = null;
$editErrors = [];
$openEditModal = false;
$editForm = [
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

function buildGalleryFromPaths(array $paths, string $deviceName): array
{
    $gallery = [];
    foreach ($paths as $index => $path) {
        $gallery[] = [
            'src' => $path,
            'alt' => $deviceName . ' fotografia ' . ($index + 1),
            'caption' => 'Fotografia zariadenia.',
        ];
    }
    return $gallery;
}

function buildDeviceDetail(array $device, ?array $reviews = null): array
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
        'reviews' => $reviews ?? [
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
}

if ($deviceId) {
    try {
        $database = new Database();
        $deviceFromDb = $database->fetchDeviceById($deviceId);
        if ($deviceFromDb) {
            $device = $deviceFromDb;
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

if ($deviceId && $device) {
    $editForm = [
        'znacka' => (string) ($device['brand'] ?? ''),
        'model' => (string) ($device['model'] ?? ''),
        'typ_zariadenia' => (string) ($device['type'] ?? ''),
        'velkost_displeja' => (string) ($device['display'] ?? ''),
        'ram' => (string) ($device['ram'] ?? ''),
        'pamat' => (string) ($device['memory'] ?? ''),
        'rok_vydania' => (string) ($device['release_year'] ?? ''),
        'softver' => (string) ($device['software'] ?? ''),
        'cena_za_den' => isset($device['price_per_day']) ? (string) $device['price_per_day'] : '',
        'zaloha' => isset($device['deposit']) ? (string) $device['deposit'] : '',
        'popis' => (string) ($device['description'] ?? ''),
        'stav' => (string) ($device['status'] ?? 'dostupne'),
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW);
        if ($action === 'add_review') {
            if (!$currentUser) {
                $reviewErrors[] = 'Na pridanie recenzie sa prosím prihláste.';
            }

            $rating = filter_input(
                INPUT_POST,
                'rating',
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 5]]
            );
            $comment = trim((string) filter_input(INPUT_POST, 'comment', FILTER_UNSAFE_RAW));

            $reviewForm = [
                'rating' => $rating ? (string) $rating : '',
                'comment' => $comment,
            ];

            if (!$rating) {
                $reviewErrors[] = 'Vyberte hodnotenie od 1 do 5 hviezdičiek.';
            }

            if ($comment === '') {
                $reviewErrors[] = 'Napíšte krátku recenziu k zariadeniu.';
            }

            if ($reviewErrors === [] && $currentUser) {
                try {
                    $database = new Database();
                    $database->createReview(
                        (int) $currentUser['id'],
                        (int) $deviceId,
                        (int) $rating,
                        mb_substr($comment, 0, 1000)
                    );
                    header('Location: zariadenia.php?id=' . (int) $deviceId . '&review=success');
                    exit;
                } catch (Throwable $exception) {
                    $reviewErrors[] = 'Recenziu sa nepodarilo uložiť. Skúste to prosím neskôr.';
                }
            }
        } elseif ($action === 'update_device') {
            if (!$isAdmin) {
                $editErrors[] = 'Na upravu zariadenia potrebujete admin prava.';
            } elseif (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
                $editErrors[] = 'Neplatny bezpecnostny token. Obnovte stranku a skuste znova.';
            } else {
                $editForm = [
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

                if ($editForm['znacka'] === '' || $editForm['model'] === '' || $editForm['typ_zariadenia'] === '' || $editForm['cena_za_den'] === '') {
                    $editErrors[] = 'Vyplnte znacku, model, typ zariadenia a cenu za den.';
                }
                if (!in_array($editForm['typ_zariadenia'], $deviceTypes, true)) {
                    $editErrors[] = 'Neplatny typ zariadenia.';
                }
                if (!in_array($editForm['stav'], $deviceStatuses, true)) {
                    $editErrors[] = 'Neplatny stav zariadenia.';
                }

                if ($editErrors === []) {
                    $payload = [
                        'znacka' => $editForm['znacka'],
                        'model' => $editForm['model'],
                        'typ_zariadenia' => $editForm['typ_zariadenia'],
                        'velkost_displeja' => $editForm['velkost_displeja'] !== '' ? $editForm['velkost_displeja'] : null,
                        'ram' => $editForm['ram'] !== '' ? (int) $editForm['ram'] : null,
                        'pamat' => $editForm['pamat'] !== '' ? (int) $editForm['pamat'] : null,
                        'rok_vydania' => $editForm['rok_vydania'] !== '' ? (int) $editForm['rok_vydania'] : null,
                        'softver' => $editForm['softver'] !== '' ? $editForm['softver'] : null,
                        'cena_za_den' => (float) $editForm['cena_za_den'],
                        'zaloha' => $editForm['zaloha'] !== '' ? (float) $editForm['zaloha'] : 0.0,
                        'popis' => $editForm['popis'] !== '' ? $editForm['popis'] : null,
                        'stav' => $editForm['stav'],
                    ];

                    try {
                        $database = new Database();
                        $database->updateDevice((int) $deviceId, $payload);
                        header('Location: zariadenia.php?id=' . (int) $deviceId . '&edit=success');
                        exit;
                    } catch (Throwable $exception) {
                        $editErrors[] = 'Zariadenie sa nepodarilo ulozit. Skuste to neskor.';
                    }
                }
            }

            if ($editErrors !== []) {
                $openEditModal = true;
            }
        }
    }

    try {
        $database = new Database();
        $reviewsFromDb = $database->fetchReviewsByDeviceId((int) $deviceId);
    } catch (Throwable $exception) {
        $reviewsFromDb = null;
    }

    $deviceImages = [];
    try {
        $database = new Database();
        $deviceImages = $database->fetchDeviceImages((int) $deviceId);
    } catch (Throwable $exception) {
        $deviceImages = [];
    }

    $device = array_merge($device, buildDeviceDetail($device, $reviewsFromDb));
    if ($deviceImages !== []) {
        $device['gallery'] = buildGalleryFromPaths($deviceImages, $device['name']);
    }

    if (filter_input(INPUT_GET, 'review', FILTER_UNSAFE_RAW) === 'success') {
        $reviewNotice = 'Ďakujeme, recenzia bola uložená.';
    }
    if (filter_input(INPUT_GET, 'edit', FILTER_UNSAFE_RAW) === 'success') {
        $editNotice = 'Zariadenie bolo aktualizovane.';
    }
}

$page->render(function () use ($device, $deviceId, $devices, $filterOptions, $selectedFilters, $reviewNotice, $reviewErrors, $reviewForm, $currentUser, $isAdmin, $editNotice, $editErrors, $openEditModal, $editForm, $deviceTypes, $deviceStatuses): void {
    ?>
      <?php if (!$deviceId): ?>
        <section class="section device-section">
          <div class="device-layout">
            <?php renderDeviceFilter($filterOptions, $selectedFilters); ?>
            <div class="filter-overlay" data-filter-overlay></div>
            <div class="device-list">
              <div class="filter-toggle-row">
                <button type="button" class="ghost-button filter-toggle" data-filter-toggle>
                  Filtrovať zariadenia
                </button>
                <span class="filter-toggle-hint">Upravte výsledky podľa vašich potrieb.</span>
              </div>
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
        <script>
          (() => {
            const toggle = document.querySelector('[data-filter-toggle]');
            const panel = document.querySelector('[data-filter-panel]');
            const overlay = document.querySelector('[data-filter-overlay]');
            const closeButton = document.querySelector('[data-filter-close]');

            if (!toggle || !panel || !overlay) {
              return;
            }

            const open = () => document.body.classList.add('filter-open');
            const close = () => document.body.classList.remove('filter-open');

            toggle.addEventListener('click', open);
            overlay.addEventListener('click', close);
            if (closeButton) {
              closeButton.addEventListener('click', close);
            }

            document.addEventListener('keydown', (event) => {
              if (event.key === 'Escape') {
                close();
              }
            });

            const mediaQuery = window.matchMedia('(max-width: 900px)');
            mediaQuery.addEventListener('change', (event) => {
              if (!event.matches) {
                close();
              }
            });
          })();
        </script>
      <?php elseif (!$device): ?>
        <section class="section">
          <div class="feature-card">
            <h2>Zariadenie sa nepodarilo načítať</h2>
            <p>Vráťte sa do katalógu a vyberte si iné zariadenie.</p>
            <a class="primary-button" href="zariadenia.php">Späť na katalóg</a>
          </div>
        </section>
      <?php else: ?>
        <section class="device-detail-hero">
          <div class="device-gallery" data-device-gallery>
            <?php $firstImage = $device['gallery'][0] ?? null; ?>
            <?php if ($firstImage): ?>
              <figure>
                <img
                  data-device-gallery-image
                  src="<?= htmlspecialchars($firstImage['src'], ENT_QUOTES, 'UTF-8') ?>"
                  alt="<?= htmlspecialchars($firstImage['alt'], ENT_QUOTES, 'UTF-8') ?>"
                />
                <figcaption data-device-gallery-caption>
                  <?= htmlspecialchars($firstImage['caption'], ENT_QUOTES, 'UTF-8') ?>
                </figcaption>
              </figure>
              <div class="device-gallery-controls">
                <button type="button" class="gallery-arrow" data-gallery-direction="prev" aria-label="Predchádzajúca fotografia">
                  ‹
                </button>
                <div class="device-gallery-dots" role="tablist" aria-label="Fotogaléria zariadenia">
                  <?php foreach ($device['gallery'] as $index => $image): ?>
                    <button
                      type="button"
                      class="gallery-dot"
                      data-gallery-index="<?= (int) $index ?>"
                      data-gallery-src="<?= htmlspecialchars($image['src'], ENT_QUOTES, 'UTF-8') ?>"
                      data-gallery-alt="<?= htmlspecialchars($image['alt'], ENT_QUOTES, 'UTF-8') ?>"
                      data-gallery-caption="<?= htmlspecialchars($image['caption'], ENT_QUOTES, 'UTF-8') ?>"
                      aria-label="Zobraziť fotografiu <?= (int) $index + 1 ?>"
                      aria-current="<?= $index === 0 ? 'true' : 'false' ?>"
                    ></button>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="gallery-arrow" data-gallery-direction="next" aria-label="Nasledujúca fotografia">
                  ›
                </button>
              </div>
            <?php endif; ?>
          </div>
          <div>
            <p class="eyebrow"><?= htmlspecialchars(ucfirst($device['type']), ENT_QUOTES, 'UTF-8') ?></p>
            <h2><?= htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars($device['details'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="device-price"><?= htmlspecialchars($device['price'], ENT_QUOTES, 'UTF-8') ?></div>
            <ul class="feature-list">
              <?php foreach ($device['highlights'] as $highlight): ?>
                <li><?= htmlspecialchars($highlight, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
            <div class="device-cta">
              <a class="primary-button" href="kosik.php?device_id=<?= (int) $device['id'] ?>">Prenajať</a>
              <a class="ghost-button" href="zariadenia.php">Späť na katalóg</a>
              <?php if ($isAdmin): ?>
                <button type="button" class="ghost-button admin-edit-trigger" data-admin-edit-open>Upravit zariadenie</button>
              <?php endif; ?>
            </div>
            <?php if ($editNotice): ?>
              <div class="review-feedback review-feedback-success admin-form-feedback">
                <?= htmlspecialchars($editNotice, ENT_QUOTES, 'UTF-8') ?>
              </div>
            <?php endif; ?>
          </div>
        </section>
        <div class="lightbox" data-lightbox aria-hidden="true">
          <div class="lightbox-content" role="dialog" aria-modal="true" aria-label="Zobrazenie fotografie">
            <button type="button" class="lightbox-close" data-lightbox-close>Zavrieť</button>
            <img class="lightbox-image" data-lightbox-image alt="">
            <div class="lightbox-caption" data-lightbox-caption></div>
          </div>
        </div>
        <?php if ($isAdmin): ?>
          <div class="admin-modal-backdrop<?= $openEditModal ? ' is-open' : '' ?>" data-admin-edit-modal aria-hidden="<?= $openEditModal ? 'false' : 'true' ?>">
            <div class="admin-modal" role="dialog" aria-modal="true" aria-label="Uprava zariadenia">
              <div class="admin-modal-header">
                <h3>Upravit zariadenie</h3>
                <button type="button" class="lightbox-close" data-admin-edit-close>Zavriet</button>
              </div>
              <?php if ($editErrors): ?>
                <div class="review-feedback review-feedback-error admin-form-feedback">
                  <ul>
                    <?php foreach ($editErrors as $error): ?>
                      <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
              <form class="order-form admin-edit-form" method="post">
                <input type="hidden" name="action" value="update_device">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-grid">
                  <label>
                    Znacka
                    <input type="text" name="znacka" value="<?= htmlspecialchars($editForm['znacka'], ENT_QUOTES, 'UTF-8') ?>" required>
                  </label>
                  <label>
                    Model
                    <input type="text" name="model" value="<?= htmlspecialchars($editForm['model'], ENT_QUOTES, 'UTF-8') ?>" required>
                  </label>
                  <label>
                    Typ zariadenia
                    <select name="typ_zariadenia" required>
                      <?php foreach ($deviceTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" <?= $editForm['typ_zariadenia'] === $type ? 'selected' : '' ?>>
                          <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label>
                    Velkost displeja
                    <input type="text" name="velkost_displeja" value="<?= htmlspecialchars($editForm['velkost_displeja'], ENT_QUOTES, 'UTF-8') ?>">
                  </label>
                  <label>
                    RAM (GB)
                    <input type="number" name="ram" min="0" step="1" value="<?= htmlspecialchars($editForm['ram'], ENT_QUOTES, 'UTF-8') ?>">
                  </label>
                  <label>
                    Pamat (GB)
                    <input type="number" name="pamat" min="0" step="1" value="<?= htmlspecialchars($editForm['pamat'], ENT_QUOTES, 'UTF-8') ?>">
                  </label>
                  <label>
                    Rok vydania
                    <input type="number" name="rok_vydania" min="2000" step="1" value="<?= htmlspecialchars($editForm['rok_vydania'], ENT_QUOTES, 'UTF-8') ?>">
                  </label>
                  <label>
                    Softver
                    <input type="text" name="softver" value="<?= htmlspecialchars($editForm['softver'], ENT_QUOTES, 'UTF-8') ?>">
                  </label>
                  <label>
                    Cena za den (EUR)
                    <input type="number" name="cena_za_den" min="0" step="0.01" value="<?= htmlspecialchars($editForm['cena_za_den'], ENT_QUOTES, 'UTF-8') ?>" required>
                  </label>
                  <label>
                    Zaloha (EUR)
                    <input type="number" name="zaloha" min="0" step="0.01" value="<?= htmlspecialchars($editForm['zaloha'], ENT_QUOTES, 'UTF-8') ?>">
                  </label>
                  <label>
                    Stav
                    <select name="stav">
                      <?php foreach ($deviceStatuses as $status): ?>
                        <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $editForm['stav'] === $status ? 'selected' : '' ?>>
                          <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label>
                    Popis
                    <input type="text" name="popis" value="<?= htmlspecialchars($editForm['popis'], ENT_QUOTES, 'UTF-8') ?>">
                  </label>
                </div>
                <div class="device-cta">
                  <button class="primary-button" type="submit">Ulozit zmeny</button>
                  <button class="ghost-button" type="button" data-admin-edit-close>Zrusit</button>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>
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
          <?php if ($reviewNotice): ?>
            <div class="review-feedback review-feedback-success">
              <?= htmlspecialchars($reviewNotice, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>
          <?php if ($reviewErrors): ?>
            <div class="review-feedback review-feedback-error">
              <ul>
                <?php foreach ($reviewErrors as $error): ?>
                  <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <div class="review-list">
            <?php if (!empty($device['reviews'])): ?>
              <?php foreach ($device['reviews'] as $review): ?>
                <article class="review-card">
                  <div class="review-rating">
                    <?= str_repeat('★', (int) $review['rating']) ?><?= str_repeat('☆', 5 - (int) $review['rating']) ?>
                  </div>
                  <div class="review-meta">
                    <strong><?= htmlspecialchars($review['author'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if (!empty($review['date'])): ?>
                      <span><?= htmlspecialchars(date('d.m.Y', strtotime($review['date'])), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                  </div>
                  <p><?= htmlspecialchars($review['text'], ENT_QUOTES, 'UTF-8') ?></p>
                </article>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="review-empty">
                Zatiaľ bez recenzií. Buďte prvý, kto sa podelí o skúsenosť.
              </div>
            <?php endif; ?>
          </div>
          <div class="review-form-wrapper">
            <?php if ($currentUser): ?>
              <h3>Pridajte vlastnú recenziu</h3>
              <form class="review-form" method="post">
                <input type="hidden" name="action" value="add_review">
                <label>
                  Hodnotenie
                  <select name="rating" required>
                    <option value="">Vyberte hodnotenie</option>
                    <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                      <option value="<?= $rating ?>" <?= $reviewForm['rating'] === (string) $rating ? 'selected' : '' ?>>
                        <?= $rating ?> / 5
                      </option>
                    <?php endfor; ?>
                  </select>
                </label>
                <label>
                  Vaša recenzia
                  <textarea name="comment" rows="4" required><?= htmlspecialchars($reviewForm['comment'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
                <button class="primary-button" type="submit">Odoslať recenziu</button>
              </form>
            <?php else: ?>
              <div class="review-login-hint">
                Na pridanie recenzie je potrebné prihlásenie.
                <a href="login.php">Prihlásiť sa</a>
              </div>
            <?php endif; ?>
          </div>
        </section>
        <script>
          (() => {
            const gallery = document.querySelector('[data-device-gallery]');
            if (!gallery) {
              return;
            }

            const imageElement = gallery.querySelector('[data-device-gallery-image]');
            const captionElement = gallery.querySelector('[data-device-gallery-caption]');
            const dots = Array.from(gallery.querySelectorAll('[data-gallery-index]'));
            if (!imageElement || dots.length === 0 || !captionElement) {
              return;
            }

            const images = dots.map((dot) => ({
              src: dot.dataset.gallerySrc,
              alt: dot.dataset.galleryAlt,
              caption: dot.dataset.galleryCaption,
            }));

            let currentIndex = Math.max(
              0,
              dots.findIndex((dot) => dot.getAttribute('aria-current') === 'true')
            );

            const setActive = (index) => {
              const data = images[index];
              if (!data) {
                return;
              }
              imageElement.src = data.src;
              imageElement.alt = data.alt;
              captionElement.textContent = data.caption;
              dots.forEach((dot, dotIndex) => {
                dot.setAttribute('aria-current', dotIndex === index ? 'true' : 'false');
              });
              currentIndex = index;
            };

            const step = (direction) => {
              const nextIndex = (currentIndex + direction + images.length) % images.length;
              setActive(nextIndex);
            };

            gallery.addEventListener('click', (event) => {
              const dotButton = event.target.closest('[data-gallery-index]');
              if (dotButton) {
                setActive(Number(dotButton.dataset.galleryIndex));
                return;
              }
              const arrowButton = event.target.closest('[data-gallery-direction]');
              if (arrowButton) {
                step(arrowButton.dataset.galleryDirection === 'next' ? 1 : -1);
                return;
              }
              if (event.target === imageElement) {
                const lightbox = document.querySelector('[data-lightbox]');
                const lightboxImage = document.querySelector('[data-lightbox-image]');
                const lightboxCaption = document.querySelector('[data-lightbox-caption]');
                if (lightbox && lightboxImage && lightboxCaption) {
                  lightboxImage.src = imageElement.src;
                  lightboxImage.alt = imageElement.alt;
                  lightboxCaption.textContent = captionElement.textContent;
                  lightbox.classList.add('is-open');
                  lightbox.setAttribute('aria-hidden', 'false');
                }
              }
            });

            const lightbox = document.querySelector('[data-lightbox]');
            const lightboxClose = document.querySelector('[data-lightbox-close]');
            if (lightbox && lightboxClose) {
              const close = () => {
                lightbox.classList.remove('is-open');
                lightbox.setAttribute('aria-hidden', 'true');
              };
              lightboxClose.addEventListener('click', close);
              lightbox.addEventListener('click', (event) => {
                if (event.target === lightbox) {
                  close();
                }
              });
              document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && lightbox.classList.contains('is-open')) {
                  close();
                }
              });
            }

            const editModal = document.querySelector('[data-admin-edit-modal]');
            const editOpenButton = document.querySelector('[data-admin-edit-open]');
            const editCloseButtons = document.querySelectorAll('[data-admin-edit-close]');
            if (editModal) {
              const openEditModal = () => {
                editModal.classList.add('is-open');
                editModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
              };
              const closeEditModal = () => {
                editModal.classList.remove('is-open');
                editModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
              };

              if (editOpenButton) {
                editOpenButton.addEventListener('click', openEditModal);
              }
              editCloseButtons.forEach((button) => {
                button.addEventListener('click', closeEditModal);
              });
              editModal.addEventListener('click', (event) => {
                if (event.target === editModal) {
                  closeEditModal();
                }
              });
              document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && editModal.classList.contains('is-open')) {
                  closeEditModal();
                }
              });

              if (editModal.classList.contains('is-open')) {
                document.body.classList.add('modal-open');
              }
            }
          })();
        </script>
      <?php endif; ?>
    <?php
});
