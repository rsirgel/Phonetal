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
    ?>
    <aside class="filter-panel" data-filter-panel>
      <div class="filter-panel-header">
        <h2>Filter zariadení</h2>
        <button type="button" class="filter-close" data-filter-close aria-label="Zavrieť filter">
          ×
        </button>
      </div>
      <p>Najprv si vyberte kategóriu, potom sa zobrazia iba relevantné parametre.</p>
      <form method="get" action="zariadenia.php" class="filter-form" data-category-filter-form>
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

        <input type="hidden" name="typy[]" value="<?= htmlspecialchars($activeCategory, ENT_QUOTES, 'UTF-8') ?>" data-active-category />

        <div class="filter-group filter-category-group">
          <h3>Kategórie</h3>
          <div class="filter-category-list">
            <?php foreach ($options['typy'] as $type): ?>
              <?php
                $isActive = $activeCategory === $type;
                $parameters = $categoryParameters[$type] ?? [];
                $categoryOptions = $filtersByCategory[$type] ?? [];
              ?>
              <section class="filter-category-card<?= $isActive ? ' is-open' : '' ?>" data-category-card>
                <button
                  type="button"
                  class="filter-category-trigger"
                  data-category-trigger
                  data-category-value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
                  aria-expanded="<?= $isActive ? 'true' : 'false' ?>"
                >
                  <span><?= htmlspecialchars($categoryLabels[$type] ?? ucfirst($type), ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="filter-category-icon" aria-hidden="true">⌄</span>
                </button>

                <div class="filter-category-content" <?= $isActive ? '' : 'hidden' ?> data-category-content>
                  <?php foreach ($parameters as $parameter): ?>
                    <?php $values = $categoryOptions[$parameter] ?? []; ?>
                    <?php if ($values === []) continue; ?>
                    <div class="filter-subgroup">
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
              </section>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="filter-actions">
          <a href="zariadenia.php" class="ghost-button">Resetovať filtre</a>
        </div>
      </form>
    </aside>
    <?php
}
