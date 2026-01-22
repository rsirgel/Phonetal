<?php

function renderDeviceCard(array $device): void
{
    $deviceId = isset($device['id']) ? (int) $device['id'] : 0;
    $detailUrl = $deviceId > 0 ? "zariadenie.php?id={$deviceId}" : 'zariadenie.php';
    $rentUrl = $deviceId > 0 ? "kosik.php?device_id={$deviceId}" : 'kosik.php';
    ?>
    <article class="product-card" data-detail-url="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">
      <div class="product-image"><?= htmlspecialchars($device['label'], ENT_QUOTES, 'UTF-8') ?></div>
      <h3><?= htmlspecialchars($device['name'], ENT_QUOTES, 'UTF-8') ?></h3>
      <p><?= htmlspecialchars($device['details'], ENT_QUOTES, 'UTF-8') ?></p>
      <strong><?= htmlspecialchars($device['price'], ENT_QUOTES, 'UTF-8') ?></strong>
      <div class="product-actions">
        <a class="ghost-button" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">Detail</a>
        <a class="primary-button" href="<?= htmlspecialchars($rentUrl, ENT_QUOTES, 'UTF-8') ?>">Prenajať</a>
      </div>
    </article>
    <?php
}