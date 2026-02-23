<?php
require_once __DIR__ . '/models/page.php';

$page = new Page(
    'Phonetal | Služby',
    'Prenájom, oprava a výkup zariadení. Prehľad služieb Phonetal pre jednotlivcov aj firmy.',
    'sluzby.php'
);

$page->render(function (): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Služby Phonetal</p>
          <h1>Kompletné služby pre prenájom zariadení</h1>
          <p>
            Postaráme sa o prenájom, servis a výkup zariadení. Získate dostupnosť, spoľahlivú
            podporu a bezpečné platby bez zbytočných komplikácií.
          </p>
        </div>
        <div class="page-hero-card">
          <h2>Čo získate</h2>
          <ul>
            <li>Záruka počas celej doby prenájmu</li>
            <li>Rýchle riešenie servisu do 24 hodín</li>
            <li>Možnosť odkúpenia zariadenia</li>
            <li>Firemné balíčky a fakturácia</li>
          </ul>
        </div>
      </section>

      <section class="section services">
        <div class="section-heading">
          <h2>Hlavné služby</h2>
          <p>Vyberte si službu podľa vášho cieľa a scenára použitia.</p>
        </div>
        <div class="services-grid">
          <article>
            <h3>Prenájom zariadení</h3>
            <p>Telefóny, tablety, smart hodinky a príslušenstvo pripravené na okamžité použitie.</p>
          </article>
          <article>
            <h3>Oprava zariadení</h3>
            <p>Diagnostika, servis a výmena v prípade poškodenia počas prenájmu.</p>
          </article>
          <article>
            <h3>Výkup zariadení</h3>
            <p>Férové ohodnotenie zariadení a možnosť odkúpenia po prenájme.</p>
          </article>
        </div>
      </section>
    <?php
});