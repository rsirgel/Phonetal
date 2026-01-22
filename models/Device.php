<?php
require_once __DIR__ . '/models/Page.php';
require_once __DIR__ . '/models/device-filter.php';
require_once __DIR__ . '/models/device-card.php';

$page = new Page(
    'Phonetal | Zariadenia na prenájom',
    'Prehľad zariadení na prenájom: mobilné telefóny, tablety, slúchadlá a smart hodinky.',
    'zariadenia.php'
);

$filters = [
    'typy' => ['Telefóny', 'Tablety', 'Slúchadlá', 'Smart hodinky'],
    'znacky' => ['Apple', 'Samsung', 'Google', 'Sony', 'Garmin'],
];

$devices = [
    'telefony' => [
        [
            'label' => 'iPhone 15 Pro',
            'name' => 'iPhone 15 Pro',
            'details' => '256 GB • Titanium • 5G',
            'price' => 'od 19 €/deň',
        ],
        [
            'label' => 'Samsung S24',
            'name' => 'Samsung Galaxy S24',
            'details' => '128 GB • AMOLED • 5G',
            'price' => 'od 16 €/deň',
        ],
        [
            'label' => 'Google Pixel',
            'name' => 'Google Pixel 8',
            'details' => '128 GB • Tensor • 5G',
            'price' => 'od 15 €/deň',
        ],
    ],
    'tablety' => [
        [
            'label' => 'iPad Pro',
            'name' => 'iPad Pro 12.9"',
            'details' => '256 GB • Wi-Fi + Cellular',
            'price' => 'od 18 €/deň',
        ],
        [
            'label' => 'Galaxy Tab',
            'name' => 'Galaxy Tab S9',
            'details' => '128 GB • AMOLED • S-Pen',
            'price' => 'od 14 €/deň',
        ],
    ],
    'sluchadla' => [
        [
            'label' => 'AirPods Max',
            'name' => 'Apple AirPods Max',
            'details' => 'Noise cancelling • Bluetooth',
            'price' => 'od 8 €/deň',
        ],
        [
            'label' => 'Sony WH-1000XM5',
            'name' => 'Sony WH-1000XM5',
            'details' => 'Hi-Res audio • ANC',
            'price' => 'od 7 €/deň',
        ],
    ],
    'hodinky' => [
        [
            'label' => 'Apple Watch',
            'name' => 'Apple Watch Series 9',
            'details' => 'GPS + Cellular • 45 mm',
            'price' => 'od 9 €/deň',
        ],
        [
            'label' => 'Garmin Venu',
            'name' => 'Garmin Venu 3',
            'details' => 'Fitness • AMOLED',
            'price' => 'od 6 €/deň',
        ],
    ],
];

$page->render(function () use ($filters, $devices): void {
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
          <?php renderDeviceFilter($filters); ?>
          <div class="device-list">
            <section id="telefony" class="device-group">
              <div class="section-heading">
                <h2>Mobilné telefóny</h2>
                <p>Najnovšie modely pripravené na testovanie alebo krátkodobý prenájom.</p>
              </div>
              <div class="product-grid">
                <?php foreach ($devices['telefony'] as $device): ?>
                  <?php renderDeviceCard($device); ?>
                <?php endforeach; ?>
              </div>
            </section>

            <section id="tablety" class="device-group">
              <div class="section-heading">
                <h2>Tablety</h2>
                <p>Ideálne na štúdium, prácu aj prezentácie.</p>
              </div>
              <div class="product-grid">
                <?php foreach ($devices['tablety'] as $device): ?>
                  <?php renderDeviceCard($device); ?>
                <?php endforeach; ?>
              </div>
            </section>

            <section id="sluchadla" class="device-group">
              <div class="section-heading">
                <h2>Slúchadlá</h2>
                <p>Prémiový zvuk s aktívnym potlačením hluku.</p>
              </div>
              <div class="product-grid">
                <?php foreach ($devices['sluchadla'] as $device): ?>
                  <?php renderDeviceCard($device); ?>
                <?php endforeach; ?>
              </div>
            </section>

            <section id="hodinky" class="device-group">
              <div class="section-heading">
                <h2>Smart hodinky</h2>
                <p>Šport, zdravie a notifikácie v elegantnom dizajne.</p>
              </div>
              <div class="product-grid">
                <?php foreach ($devices['hodinky'] as $device): ?>
                  <?php renderDeviceCard($device); ?>
                <?php endforeach; ?>
              </div>
            </section>
          </div>
        </div>
      </section>
    <?php
});
