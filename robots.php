<?php
require_once __DIR__ . '/models/Page.php';

$page = new Page(
    'Phonetal | Roadmap projektu',
    'Roadmap a plán nasadenia webovej aplikácie Phonetal.',
    'robots.php'
);

$page->render(function (): void {
    ?>
      <section class="page-hero">
        <div>
          <p class="eyebrow">Roadmap</p>
          <h1>Plán nasadenia a míľniky</h1>
          <p>
            Roadmap sumarizuje hlavné fázy projektu, plánované dátumy a kľúčové výstupy pre Phonetal.
          </p>
        </div>
        <div class="page-hero-card">
          <h2>Nasledujúci krok</h2>
          <ul>
            <li>UX/UI prototypy v Adobe XD</li>
            <li>Validácia s cieľovou skupinou</li>
            <li>Výber technológií pre backend</li>
          </ul>
        </div>
      </section>

      <section class="section">
        <div class="section-heading">
          <h2>Míľniky</h2>
          <p>Termíny vychádzajú z projektového plánu a môžu sa upraviť podľa potrieb.</p>
        </div>
        <div class="timeline">
          <article>
            <h3>Plánovanie</h3>
            <p>18.09.2025 – 01.10.2025</p>
          </article>
          <article>
            <h3>Dizajn</h3>
            <p>16.10.2025 – 30.11.2025</p>
          </article>
          <article>
            <h3>Vývoj</h3>
            <p>30.11.2025 – 25.01.2026</p>
          </article>
          <article>
            <h3>Testovanie</h3>
            <p>26.01.2026 – 29.01.2026</p>
          </article>
          <article>
            <h3>Nasadenie</h3>
            <p>30.01.2026</p>
          </article>
        </div>
      </section>

      <section class="section feature-list">
        <div class="section-heading">
          <h2>Roadmap po nasadení</h2>
          <p>Plánované rozšírenia a nové funkcie po spustení.</p>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h3>Správa inventára</h3>
            <p>Automatizované nahrávanie zariadení cez XML feedy.</p>
          </div>
          <div class="feature-card">
            <h3>Firemné balíčky</h3>
            <p>Predplatné modely a správa viac zariadení na jeden účet.</p>
          </div>
          <div class="feature-card">
            <h3>3D prezeranie</h3>
            <p>Interaktívne modely zariadení pre lepší výber.</p>
          </div>
          <div class="feature-card">
            <h3>tba</h3>
            <p>Interaktívne modely zariadení pre lepší výber.</p>
          </div>
        </div>
      </section>
    <?php
});