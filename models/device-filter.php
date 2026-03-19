<?php

function renderDeviceFilter(array $options, array $selected)
{
    $categoryLabels = [
        'telefon' => 'Telefón',
        'tablet' => 'Tablet',
        'hodinky' => 'Hodinky',
        'sluchadla' => 'Slúchadlá',
        'prislusenstvo' => 'Príslušenstvo',
    ];
    $categoryDescriptions = [
        'telefon' => 'Rýchly výber smartfónov na prenájom.',
        'tablet' => 'Tablety na prácu, školu aj prezentácie.',
        'hodinky' => 'Smart hodinky na notifikácie a zdravie.',
        'sluchadla' => 'Audio technika na cestovanie a sústredenie.',
        'prislusenstvo' => 'Doplnky a príslušenstvo k zariadeniam.',
    ];
    $categoryParameters = [
        'telefon' => ['znacky', 'ram', 'uhlopriecky'],
        'tablet' => ['znacky', 'ram', 'uhlopriecky'],
        'hodinky' => ['znacky', 'uhlopriecky'],
        'sluchadla' => ['znacky'],
        'prislusenstvo' => ['znacky'],
    ];
    $parameterLabels = [
        'znacky' => 'Značka',
        'ram' => 'RAM',
        'uhlopriecky' => 'Uhlopriečka',
    ];
    $activeCategory = $selected['typy'][0] ?? '';
    $filtersByCategory = $options['filters_by_category'] ?? [];
    $activeParameters = $categoryParameters[$activeCategory] ?? [];
    $activeOptions = $filtersByCategory[$activeCategory] ?? [];
    ?>
    <aside class="filter-panel" data-filter-panel>
      <div class="filter-panel-header">
        <h2>Filter zariadení</h2>
        <button type="button" class="filter-close" data-filter-close aria-label="Zavrieť filter">
          ×
        </button>
      </div>
      <p>Vyberte kategóriu cez tlačidlo a následne sa prispôsobia dostupné filtre.</p>

      <div class="filter-group">
        <h3>Kategórie</h3>
        <div class="filter-category-links">
          <?php foreach ($options['typy'] as $type): ?>
            <a
              class="ghost-button filter-category-link<?= $activeCategory === $type ? ' is-active' : '' ?>"
              href="zariadenia.php?typy[]=<?= urlencode($type) ?>"
            >
              <strong><?= htmlspecialchars($categoryLabels[$type] ?? ucfirst($type), ENT_QUOTES, 'UTF-8') ?></strong>
              <span><?= htmlspecialchars($categoryDescriptions[$type] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <form method="get" action="zariadenia.php" class="filter-form">
        <?php if ($activeCategory !== ''): ?>
          <input type="hidden" name="typy[]" value="<?= htmlspecialchars($activeCategory, ENT_QUOTES, 'UTF-8') ?>" />
        <?php endif; ?>

        <div class="filter-group">
          <h3>Dostupnosť</h3>
          <?php foreach ($options['stavy'] as $status): ?>
            <label>
              <input
                type="radio"
                name="stav"
                value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                <?= (($selected['stav'] ?? 'dostupne') === $status) ? 'checked' : '' ?>
                data-filter-input
              />
              <?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?>
            </label>
          <?php endforeach; ?>
        </div>

        <?php if ($activeCategory !== ''): ?>
          <div class="filter-group">
            <h3>Filtre pre kategóriu: <?= htmlspecialchars($categoryLabels[$activeCategory] ?? ucfirst($activeCategory), ENT_QUOTES, 'UTF-8') ?></h3>
            <?php foreach ($activeParameters as $parameter): ?>
              <?php $values = $activeOptions[$parameter] ?? []; ?>
              <?php if ($values === []) continue; ?>
              <div class="filter-subgroup filter-inline-subgroup">
                <h4><?= htmlspecialchars($parameterLabels[$parameter] ?? ucfirst($parameter), ENT_QUOTES, 'UTF-8') ?></h4>
                <?php foreach ($values as $value): ?>
                  <label>
                    <input
                      type="checkbox"
                      name="<?= htmlspecialchars($parameter, ENT_QUOTES, 'UTF-8') ?>[]"
                      value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>"
                      <?= in_array((string) $value, $selected[$parameter] ?? [], true) ? 'checked' : '' ?>
                      data-filter-input
                    />
                    <?php if ($parameter === 'ram'): ?>
                      <?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?> GB
                    <?php else: ?>
                      <?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="filter-hint-box">
            <strong>Najprv vyberte kategóriu.</strong>
            <span>Po kliknutí na jedno z tlačidiel vyššie sa zobrazia len filtre pre daný typ zariadenia.</span>
          </div>
        <?php endif; ?>

        <div class="filter-actions">
          <a href="zariadenia.php" class="ghost-button">Resetovať filtre</a>
        </div>
      </form>
    </aside>
    <?php
}
