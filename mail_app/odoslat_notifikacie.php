<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../mail_app/vendor/autoload.php';
require __DIR__ . '/../config/database.php';

// koľko dní pred koncom prenájmu posielať notifikácie
$daysIntervals = [7, 3, 1];

$database = new Database();
$notifications = $database->fetchRentEndNotifications($daysIntervals);

if ($notifications === []) {
    echo "Ziadne notifikacie na odoslanie.\n";
    exit(0);
}

// --- Nastavenie odosielateľa (Laravel .env štýl) ---
$mailFrom = getenv('MAIL_FROM_ADDRESS') ?: (getenv('PHONETAL_MAIL_FROM') ?: 'info@phonetal.sk');
$mailFromName = getenv('MAIL_FROM_NAME') ?: (getenv('PHONETAL_MAIL_FROM_NAME') ?: 'Phonetal');

// --- Nastavenie mailera / SMTP (Laravel .env štýl) ---
$mailMailer = strtolower((string) (getenv('MAIL_MAILER') ?: 'smtp'));
$smtpHost = (string) (getenv('MAIL_HOST') ?: '');
$smtpUser = (string) (getenv('MAIL_USERNAME') ?: '');
$smtpPass = (string) (getenv('MAIL_PASSWORD') ?: '');
$smtpPort = (int) (getenv('MAIL_PORT') ?: 587);

// MAIL_ENCRYPTION: tls | ssl | (prázdne/none/null)
$enc = strtolower(trim((string) (getenv('MAIL_ENCRYPTION') ?: 'tls')));
if ($enc === 'tls' || $enc === 'starttls') {
    $smtpSecure = PHPMailer::ENCRYPTION_STARTTLS;
} elseif ($enc === 'ssl' || $enc === 'smtps') {
    $smtpSecure = PHPMailer::ENCRYPTION_SMTPS;
} else {
    $smtpSecure = '';
}

$sentCount = 0;

foreach ($notifications as $notification) {
    $email = $notification['email'] ?? '';
    $name = $notification['meno'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $prenajomId = $notification['prenajom_id'] ?? 'neznamy';
        echo "Preskakujem neplatny email pre prenajom #{$prenajomId}.\n";
        continue;
    }

    // bezpečné typovanie / fallbacky
    $prenajomId = (string) ($notification['prenajom_id'] ?? '');
    $dni = (int) ($notification['dni'] ?? 0);
    $koniecRaw = (string) ($notification['koniec'] ?? '');

    $daysLabel = ($dni === 1) ? 'den' : 'dni';

    // formát dátumu
    try {
        $koniec = (new DateTimeImmutable($koniecRaw))->format('d.m.Y');
    } catch (Throwable $e) {
        $koniec = $koniecRaw; // ak príde niečo neočakávané, aspoň to vypíšeme
    }

    // predmet
    $subject = 'Prenajom vam konci o ' . $dni . ' ' . $daysLabel;

    // HTML telo (procedurálne ako v send.php)
    $body =
        '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2a37;">'
        . '<h2 style="margin: 0 0 12px;">Prenajom sa blizi ku koncu</h2>'
        . '<p>Dobry den ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>vas prenajom (#' . htmlspecialchars($prenajomId, ENT_QUOTES, 'UTF-8') . ') konci o ' . $dni . ' ' . $daysLabel . '.</p>'
        . '<p>Datum ukoncenia: <strong>' . htmlspecialchars($koniec, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
        . '<p>Ak potrebujete predlzit prenajom alebo mate otazky, ozvite sa nam na ' . htmlspecialchars($mailFrom, ENT_QUOTES, 'UTF-8') . '.</p>'
        . '<p style="margin-top: 24px;">Dakujeme,<br>Tim ' . htmlspecialchars($mailFromName, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</div>';

    // text verzia
    $altBody =
        "Prenajom sa blizi ku koncu\n\n"
        . "Dobrý deň {$name},\n"
        . "Váš prenájom (#{$prenajomId}) končí o {$dni} {$daysLabel}.\n"
        . "Dátum ukončenia: {$koniec}.\n\n"
        . "Ak potrebujete predĺžiť prenájom alebo máte otázky, ozvite sa nám na {$mailFrom}.\n\n"
        . "Ďakujeme,\nTím {$mailFromName}\n";

    // vytvorenie nového mailera (konfigurácia ako v send.php)
    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';

        // SMTP – keď MAIL_MAILER=smtp a je nastavený host, použijeme SMTP, inak mail()
        if ($mailMailer === 'smtp' && $smtpHost !== '') {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;

            // šifrovanie (tls/ssl) – ak je prázdne, necháme bez
            if ($smtpSecure !== '') {
                $mail->SMTPSecure = $smtpSecure;
            }

            $mail->Port = $smtpPort;
        } else {
            $mail->isMail();
        }

        // odosielateľ a príjemca
        $mail->setFrom($mailFrom, $mailFromName);
        $mail->addAddress($email, $name);

        // predmet a obsah
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body;
        $mail->AltBody = $altBody;

        // odoslanie
        $mail->send();

        $sentCount++;
        echo "Odoslane: {$email} (prenajom #{$prenajomId}).\n";
    } catch (Exception $e) {
        echo "Neuspech pre {$email}: {$mail->ErrorInfo}\n";
    }
}

echo "Hotovo, odoslaných notifikacii: {$sentCount}.\n";