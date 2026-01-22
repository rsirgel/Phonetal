<?php
require_once __DIR__ . '/models/page.php';

$page = new Page(
    'Phonetal | Kontakt',
    'Kontaktujte Phonetal pre prenájom zariadení, firemné balíčky alebo podporu.',
    'kontakt.php'
);

$page->render(function (): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Kontakt</p>
          <h1>Ozvite sa nám</h1>
          <p>
            Podpora je dostupná 7 dní v týždni. Radi vám pripravíme firemnú ponuku alebo odpovieme
            na otázky k prenájmu.
          </p>
        </div>
        <div class="page-hero-card">
          <h2>Rýchle kontakty</h2>
          <ul>
            <li>Email: info@phonetal.sk</li>
            <li>Telefón: +421 902 123 456</li>
            <li>Live chat: 08:00 – 20:00</li>
            <li>Odpoveď do 2 hodín</li>
          </ul>
        </div>
      </section>

      <section class="section contact">
        <div class="section-heading">
          <h2>Napíšte nám</h2>
          <p>Vyplňte formulár a my sa vám ozveme čo najskôr.</p>
        </div>
        <form class="contact-form">
          <label>
            Meno a priezvisko
            <input type="text" name="name" placeholder="Ján Novák" required />
          </label>
          <label>
            Email
            <input type="email" name="email" placeholder="vas@email.sk" required />
          </label>
          <label>
            Telefón
            <input type="tel" name="phone" placeholder="+421 900 000 000" required />
          </label>
          <label>
            Správa
            <input type="text" name="message" placeholder="Ako vám môžeme pomôcť?" />
          </label>
          <label class="checkbox">
            <input type="checkbox" required />
            Súhlasím so spracovaním osobných údajov.
          </label>
          <button class="primary-button" type="submit">Odoslať</button>
        </form>
      </section>
    <?php
});