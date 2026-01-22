# Notifikacie prenajmu

Tento priecinok obsahuje skript na odosielanie emailovych notifikacii pre prenajmy,
ktore sa blizia ku koncu (napr. o 7, 3 alebo 1 den).

## Spustenie

```bash
php notifkacie/odoslat_notifikacie.php
```

## Premenne prostredia

Skript pouziva nastavenia databazy z `config/database.php` a tieto volitelne
premenne pre odosielanie emailov:

- `PHONETAL_MAIL_FROM` (predvolene `info@phonetal.sk`)
- `PHONETAL_MAIL_FROM_NAME` (predvolene `Phonetal`)

Pre SMTP (odporucane) nastavte:

- `PHONETAL_SMTP_HOST`
- `PHONETAL_SMTP_USER`
- `PHONETAL_SMTP_PASS`
- `PHONETAL_SMTP_PORT` (predvolene 587)
- `PHONETAL_SMTP_SECURITY` (predvolene `tls`)
