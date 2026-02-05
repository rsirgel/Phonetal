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

$deviceId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

Auth::init();
$currentUser = Auth::user();
$reviewNotice = null;
$reviewErrors = [];
$reviewForm = [
    'rating' => '',
    'comment' => '',
];

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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW);
        if ($action === 'add_review') {
            if (!$currentUser) {
                $reviewErrors[] = 'Na pridanie recenzie sa prosím prihláste.';
            }

            if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
                $reviewErrors[] = 'Neplatný bezpečnostný token. Obnovte stránku a skúste znova.';
            } else {
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
            }
        }
    }

    try {
        $database = new Database();
        $reviewsFromDb = $database->fetchReviewsByDeviceId((int) $deviceId);
    } catch (Throwable $exception) {
        $reviewsFromDb = null;
    }

    $device = array_merge($device, buildDeviceDetail($device, $reviewsFromDb));

    if (filter_input(INPUT_GET, 'review', FILTER_UNSAFE_RAW) === 'success') {
        $reviewNotice = 'Ďakujeme, recenzia bola uložená.';
    }
}

$page->render(function () use ($device, $deviceId, $devices, $filterOptions, $selectedFilters, $reviewNotice, $reviewErrors, $reviewForm, $currentUser): void {
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>" />
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
              }
            });
          })();
        </script>
      <?php endif; ?>
    <?php
});
