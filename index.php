<?php
require_once __DIR__ . '/models/Page.php';

$page = new Page(
    'Phonetal | Prenájom telefónov a zariadení',
    'Phonetal ponúka flexibilný prenájom moderných telefónov, tabletov a príslušenstva. Vyberte si zariadenie, nastavte dobu prenájmu a prenajmite rýchlo online.',
    'index.php'
);

$page->render(function (): void {
    ?>
      <section class="hero">
        <div class="hero-content">
          <p class="eyebrow">Flexibilný prenájom moderných zariadení</p>
          <h1>Prenajmite si telefón už od 7 dní, bez zbytočných záväzkov.</h1>
          <p class="hero-description">
            Phonetal zabezpečí zariadenie v perfektnom stave, rýchle doručenie a jednoduchú
            administráciu. Vyberte značku, nastavte dobu prenájmu a my sa postaráme o zvyšok.
          </p>
          <div class="hero-actions">
            <a class="primary-button" href="zariadenia.php">Vybrať zariadenie</a>
            <a class="ghost-button" href="sluzby.php">Pozrieť služby</a>
          </div>
          <div class="hero-stats">
            <div>
              <strong>7–60</strong>
              <span>dní prenájmu</span>
            </div>
            <div>
              <strong>24h</strong>
              <span>priemerné doručenie</span>
            </div>
            <div>
              <strong>500+</strong>
              <span>spokojných prenájmov</span>
            </div>
          </div>
        </div>
        <div class="hero-card">
          <h2>Všetko na jednom mieste</h2>
          <ul>
            <li>Výber zariadenia podľa značky a typu.</li>
            <li>Jednoduchý košík s výpočtom ceny.</li>
            <li>Záruka počas celej doby prenájmu.</li>
            <li>Možnosť opravy alebo výkupu zariadenia.</li>
          </ul>
          <a class="primary-button" href="kosik.php">Začať prenájom</a>
        </div>
      </section>

      <section id="kategorie" class="section">
        <div class="section-heading">
          <h2>Vyberte si zariadenie</h2>
          <p>Telefóny, tablety, smart hodinky a príslušenstvo pre každý scenár.</p>
        </div>
        <div class="category-grid">
          <article class="category-card">
            <h3>Mobilné telefóny</h3>
            <p>Prémiové modely na testovanie alebo krátkodobý prenájom.</p>
            <a class="ghost-button" href="zariadenia.php#telefony">Zobraziť ponuku</a>
          </article>
          <article class="category-card">
            <h3>Tablety</h3>
            <p>Pre štúdium, prezentácie aj prácu na cestách.</p>
            <a class="ghost-button" href="zariadenia.php#tablety">Zobraziť ponuku</a>
          </article>
          <article class="category-card">
            <h3>Slúchadlá</h3>
            <p>Noise-cancelling sety na sústredenie a cestovanie.</p>
            <a class="ghost-button" href="zariadenia.php#sluchadla">Zobraziť ponuku</a>
          </article>
          <article class="category-card">
            <h3>Smart hodinky</h3>
            <p>Zdravie, notifikácie a štýl na vašom zápästí.</p>
            <a class="ghost-button" href="zariadenia.php#hodinky">Zobraziť ponuku</a>
          </article>
        </div>
      </section>

      <section id="ako-to-funguje" class="section steps">
        <div class="section-heading">
          <h2>Ako to funguje</h2>
          <p>Minimalizujeme počet krokov. Prenájom zvládnete do pár minút.</p>
        </div>
        <div class="steps-grid">
          <article>
            <span>1</span>
            <h3>Vyberte zariadenie</h3>
            <p>Filter podľa značky, typu a parametrov, ktoré potrebujete.</p>
          </article>
          <article>
            <span>2</span>
            <h3>Nastavte dobu</h3>
            <p>Zadajte počet dní (7 až 60) a dôvod prenájmu.</p>
          </article>
          <article>
            <span>3</span>
            <h3>Potvrďte objednávku</h3>
            <p>Skontrolujte cenu, zvoľte platbu a potvrďte dodanie.</p>
          </article>
          <article>
            <span>4</span>
            <h3>My sa postaráme</h3>
            <p>Doručenie, záruka aj podpora počas celého prenájmu.</p>
          </article>
        </div>
      </section>

      <section id="sluzby" class="section services">
        <div class="section-heading">
          <h2>Kľúčové služby</h2>
          <p>Všetko, čo zákazník očakáva od moderného prenájmu.</p>
        </div>
        <div class="services-grid">
          <article>
            <h3>Prenájom zariadení</h3>
            <p>Zariadenia v perfektnom stave s garantovanou dostupnosťou.</p>
          </article>
          <article>
            <h3>Oprava a výkup</h3>
            <p>Rýchle servisné zásahy a možnosť odkúpenia zariadenia.</p>
          </article>
          <article>
            <h3>Bezpečné platby</h3>
            <p>Podpora Google Pay, Apple Pay, PayPal aj bankového prevodu.</p>
          </article>
        </div>
      </section>

      <section class="section features">
        <div class="section-heading">
          <h2>Prečo Phonetal</h2>
          <p>Zamerané na spoľahlivosť, rýchlosť a jednoduchú správu.</p>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h3>Perfektný stav</h3>
            <p>Každé zariadenie prechádza kontrolou kvality a čistením.</p>
          </div>
          <div class="feature-card">
            <h3>Záruka počas prenájmu</h3>
            <p>Pokrytie servisných zásahov a rýchla výmena zariadenia.</p>
          </div>
          <div class="feature-card">
            <h3>Rýchla dostupnosť</h3>
            <p>Dodanie do 24 hodín a transparentné sledovanie objednávky.</p>
          </div>
        </div>
      </section>

      <section id="kontakt" class="section contact">
        <div class="section-heading">
          <h2>Kontaktujte nás</h2>
          <p>Máte otázky alebo potrebujete ponuku pre firmu? Ozvite sa nám.</p>
        </div>
        <form class="contact-form">
          <label>
            Dôvod prenájmu
            <input type="text" name="reason" placeholder="Napr. testovanie nového modelu" />
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
            Rodné číslo
            <input type="number" name="personal_id" placeholder="123456/7890" />
          </label>
          <label class="checkbox">
            <input type="checkbox" required />
            Súhlasím so všeobecnými podmienkami.
          </label>
          <button class="primary-button" type="submit">Odoslať dopyt</button>
        </form>
      </section>
    <?php
});
