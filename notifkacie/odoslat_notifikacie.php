<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/../mail_app/vendor/autoload.php';
require __DIR__ . '/../config/database.php';

$daysIntervals = [7, 3, 1];

$database = new Database();

$notifications = $database->fetchRentEndNotifications($daysIntervals);

if ($notifications === []) {
    echo "Ziadne notifikacie na odoslanie.\n";
    exit(0);
}

$mailFrom = getenv('PHONETAL_MAIL_FROM') ?: 'info@phonetal.sk';
$mailFromName = getenv('PHONETAL_MAIL_FROM_NAME') ?: 'Phonetal';

$sentCount = 0;

foreach ($notifications as $notification) {
    if (!filter_var($notification['email'], FILTER_VALIDATE_EMAIL)) {
        echo "Preskakujem neplatny email pre prenajom #{$notification['prenajom_id']}.\n";
        continue;
    }

    $mail = buildMailer();
    $mail->setFrom($mailFrom, $mailFromName);
    $mail->addAddress($notification['email'], $notification['meno']);

    $subject = sprintf('Prenajom vam konci o %d %s', $notification['dni'], $notification['dni'] === 1 ? 'den' : 'dni');
    $mail->Subject = $subject;

    $body = buildHtmlBody($notification);
    $mail->isHTML(true);
    $mail->Body = $body;
    $mail->AltBody = buildTextBody($notification);

    if ($mail->send()) {
        $sentCount++;
        echo "Odoslane: {$notification['email']} (prenajom #{$notification['prenajom_id']}).\n";
    } else {
        echo "Neuspech pre {$notification['email']}: {$mail->ErrorInfo}\n";
    }
}

echo "Hotovo, odoslanych notifikacii: {$sentCount}.\n";

function buildMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->setLanguage('sk', __DIR__ . '/../mail_app/vendor/phpmailer/phpmailer/language/');

    $smtpHost = getenv('PHONETAL_SMTP_HOST');
    if ($smtpHost) {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = getenv('PHONETAL_SMTP_USER') ?: '';
        $mail->Password = getenv('PHONETAL_SMTP_PASS') ?: '';
        $mail->Port = (int) (getenv('PHONETAL_SMTP_PORT') ?: 587);
        $security = getenv('PHONETAL_SMTP_SECURITY') ?: 'tls';
        if ($security) {
            $mail->SMTPSecure = $security;
        }
    } else {
        $mail->isMail();
    }

    return $mail;
}

function buildHtmlBody(array $notification): string
{
    $koniec = (new DateTimeImmutable($notification['koniec']))->format('d.m.Y');
    $days = (int) $notification['dni'];

    return <<<HTML
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2a37;">
        <h2 style="margin: 0 0 12px;">Prenajom sa blizi ku koncu</h2>
        <p>Ahoj {$notification['meno']},</p>
        <p>vas prenajom (#{$notification['prenajom_id']}) konci o {$days} {$days === 1 ? 'den' : 'dni'}.</p>
        <p>Datum ukoncenia: <strong>{$koniec}</strong>.</p>
        <p>Ak potrebujete predlzit prenajom alebo mate otazky, ozvite sa nam na info@phonetal.sk.</p>
        <p style="margin-top: 24px;">Dakujeme,<br>Tim Phonetal</p>
    </div>
    HTML;
}

function buildTextBody(array $notification): string
{
    $koniec = (new DateTimeImmutable($notification['koniec']))->format('d.m.Y');
    $days = (int) $notification['dni'];

    return "Prenajom sa blizi ku koncu\n\n" .
        "Ahoj {$notification['meno']},\n" .
        "vas prenajom (#{$notification['prenajom_id']}) konci o {$days} " . ($days === 1 ? 'den' : 'dni') . ".\n" .
        "Datum ukoncenia: {$koniec}.\n\n" .
        "Ak potrebujete predlzit prenajom alebo mate otazky, ozvite sa nam na info@phonetal.sk.\n\n" .
        "Dakujeme,\nTim Phonetal\n";
}
