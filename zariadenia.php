<?php
require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/device-filter.php';
require_once __DIR__ . '/models/device-card.php';
require_once __DIR__ . '/config/database.php';

$page = new Page(
    'Phonetal | Zariadenia na prenájom',
    'Prehľad zariadení na prenájom: mobilné telefóny, tablety, slúchadlá a smart hodinky.',
    'zariadenia.php'
);

$selectedFilters = [
    'typy' => array_values(array_filter((array) ($_GET['typy'] ?? []))),
    'znacky' => array_values(array_filter((array) ($_GET['znacky'] ?? []))),
    'ram' => array_values(array_filter((array) ($_GET['ram'] ?? []))),
    'uhlopriecky' => array_values(array_filter((array) ($_GET['uhlopriecky'] ?? []))),
];

$filterOptions = [
    'typy' => ['telefon', 'tablet', 'prislusenstvo'],
    'znacky' => ['Apple', 'Samsung', 'Google', 'Sony', 'Garmin'],
    'ram' => ['4', '8', '12', '16'],
    'uhlopriecky' => ['6.1', '6.7', '11', '12.9'],
];

$deviceCatalog = [
    [
        'label' => 'iPhone 15 Pro',
        'name' => 'iPhone 15 Pro',
        'details' => '8 GB RAM • 6.1"',
        'price' => 'od 19 €/deň',
        'typ' => 'telefon',
        'znacka' => 'Apple',
        'ram' => '8',
        'uhlopriecka' => '6.1',
    ],
    [
        'label' => 'Samsung Galaxy S24',
        'name' => 'Samsung Galaxy S24',
        'details' => '8 GB RAM • 6.7"',
        'price' => 'od 16 €/deň',
        'typ' => 'telefon',
        'znacka' => 'Samsung',
        'ram' => '8',
        'uhlopriecka' => '6.7',
    ],
    [
        'label' => 'iPad Pro 12.9"',
        'name' => 'iPad Pro 12.9"',
        'details' => '16 GB RAM • 12.9"',
        'price' => 'od 18 €/deň',
        'typ' => 'tablet',
        'znacka' => 'Apple',
        'ram' => '16',
        'uhlopriecka' => '12.9',
    ],
    [
        'label' => 'Galaxy Tab S9',
        'name' => 'Galaxy Tab S9',
        'details' => '12 GB RAM • 11"',
        'price' => 'od 14 €/deň',
        'typ' => 'tablet',
        'znacka' => 'Samsung',
        'ram' => '12',
        'uhlopriecka' => '11',
    ],
    [
        'label' => 'Apple AirPods Max',
        'name' => 'Apple AirPods Max',
        'details' => 'Prémiové ANC slúchadlá',
        'price' => 'od 8 €/deň',
        'typ' => 'prislusenstvo',
        'znacka' => 'Apple',
        'ram' => '',
        'uhlopriecka' => '',
    ],
];

try {
    $database = new Database();
    $filterOptions = $database->fetchFilterOptions();
    $deviceCatalog = $database->fetchDevicesByFilters($selectedFilters);
} catch (Throwable $exception) {
    $deviceCatalog = array_values(array_filter($deviceCatalog, function (array $device) use ($selectedFilters): bool {
        if ($selectedFilters['typy'] && !in_array($device['typ'], $selectedFilters['typy'], true)) {
            return false;
        }
        if ($selectedFilters['znacky'] && !in_array($device['znacka'], $selectedFilters['znacky'], true)) {
            return false;
        }
        if ($selectedFilters['ram'] && !in_array($device['ram'], $selectedFilters['ram'], true)) {
            return false;
        }
        if ($selectedFilters['uhlopriecky'] && !in_array($device['uhlopriecka'], $selectedFilters['uhlopriecky'], true)) {
            return false;
        }
        return true;
    }));
}

$page->render(function () use ($filterOptions, $selectedFilters, $deviceCatalog): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Katalóg zariadení</p>
          <h1>Zariadenia na prenájom</h1>
          <p>
            Vyberte si z dostupných zariadení podľa typu, značky alebo využitia. Všetky sú v
            perfektnom stave a pripravené na rýchle doručenie.
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
            <section class="device-group">
              <div class="section-heading">
                <h2>Výsledky filtrovania</h2>
                <p>Počet nájdených zariadení: <?= count($deviceCatalog) ?></p>
              </div>
              <div class="product-grid">
                <?php if (!$deviceCatalog): ?>
                  <div class="feature-card">
                    <h3>Nenašli sa žiadne zariadenia</h3>
                    <p>Upravte filtre a skúste to znovu.</p>
                  </div>
                <?php else: ?>
                  <?php foreach ($deviceCatalog as $device): ?>
                    <?php renderDeviceCard($device); ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </section>
          </div>
        </div>
      </section>
      <script>
        document.querySelectorAll('[data-filter-input]').forEach((input) => {
          input.addEventListener('change', () => {
            input.closest('form')?.requestSubmit();
          });
        });
      </script>
    <?php
});