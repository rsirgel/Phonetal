<?php

function renderDeviceFilter(array $options, array $selected)
{
    ?>
    <aside class="filter-panel">
      <h2>Filter zariadení</h2>
      <p>Výsledky sa zobrazia okamžite po výbere parametrov.</p>
      <form method="get" action="zariadenia.php" class="filter-form">
        <div class="filter-group">
          <h3>Typ zariadenia</h3>
          <?php foreach ($options['typy'] as $type): ?>
            <label>
              <input
                type="checkbox"
                name="typy[]"
                value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
                <?= in_array($type, $selected['typy'], true) ? 'checked' : '' ?>
                data-filter-input
              />
              <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="filter-group">
          <h3>Značka</h3>
          <?php foreach ($options['znacky'] as $brand): ?>
            <label>
              <input
                type="checkbox"
                name="znacky[]"
                value="<?= htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') ?>"
                <?= in_array($brand, $selected['znacky'], true) ? 'checked' : '' ?>
                data-filter-input
              />
              <?= htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="filter-group">
          <h3>RAM</h3>
          <?php foreach ($options['ram'] as $ram): ?>
            <label>
              <input
                type="checkbox"
                name="ram[]"
                value="<?= htmlspecialchars($ram, ENT_QUOTES, 'UTF-8') ?>"
                <?= in_array($ram, $selected['ram'], true) ? 'checked' : '' ?>
                data-filter-input
              />
              <?= htmlspecialchars($ram, ENT_QUOTES, 'UTF-8') ?> GB
            </label>
          <?php endforeach; ?>
        </div>
        <div class="filter-group">
          <h3>Uhlopriečka</h3>
          <?php foreach ($options['uhlopriecky'] as $size): ?>
            <label>
              <input
                type="checkbox"
                name="uhlopriecky[]"
                value="<?= htmlspecialchars($size, ENT_QUOTES, 'UTF-8') ?>"
                <?= in_array($size, $selected['uhlopriecky'], true) ? 'checked' : '' ?>
                data-filter-input
              />
              <?= htmlspecialchars($size, ENT_QUOTES, 'UTF-8') ?>
            </label>
          <?php endforeach; ?>
        </div>
      </form>
    </aside>
    <?php
}