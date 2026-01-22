<?php

function renderDeviceCard(array $device): void
{
    ?>
    <article class="product-card">
      <div class="product-image"><?= htmlspecialchars($device['label'], ENT_QUOTES, 'UTF-8') ?></div>
      <h3><?= htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8') ?></h3>
      <p><?= htmlspecialchars($device['details'], ENT_QUOTES, 'UTF-8') ?></p>
      <strong><?= htmlspecialchars($device['price'], ENT_QUOTES, 'UTF-8') ?></strong>
      <a class="primary-button" href="kosik.php">Prenajať</a>
    </article>
    <?php
}
