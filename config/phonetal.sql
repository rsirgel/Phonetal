CREATE TABLE MA_pouzivatelia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meno VARCHAR(50) NOT NULL,
  priezvisko VARCHAR(50) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  telefon VARCHAR(20),
  rodne_cislo VARCHAR(20),
  mesto VARCHAR(50),
  ulica VARCHAR(100),
  rola ENUM('pouzivatel', 'admin') DEFAULT 'pouzivatel'
);

CREATE TABLE MA_zariadenia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  znacka VARCHAR(50) NOT NULL,
  model VARCHAR(50) NOT NULL,
  typ_zariadenia ENUM('telefon', 'tablet','hodinky','sluchadla', 'prislusenstvo'),
  velkost_displeja VARCHAR(20),
  ram INT,
  pamat INT,
  rok_vydania YEAR,
  softver VARCHAR(50),
  cena_za_den DECIMAL(8, 2) NOT NULL,
  zaloha DECIMAL(8, 2) DEFAULT 0,
  popis TEXT,
  stav ENUM('dostupne', 'nedostupne') DEFAULT 'dostupne'
);

CREATE TABLE MA_prenajmy (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pouzivatel_id INT NOT NULL,
  zaciatok DATE NOT NULL,
  koniec DATE NOT NULL,
  celkova_cena DECIMAL(10, 2) NOT NULL,
  stav ENUM('aktivny', 'ukonceny', 'zruseny') DEFAULT 'aktivny',
  FOREIGN KEY (pouzivatel_id) REFERENCES MA_pouzivatelia(id)
);

CREATE TABLE MA_polozky_prenajmu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prenajom_id INT NOT NULL,
  zariadenie_id INT NOT NULL,
  cena_za_den DECIMAL(8, 2),
  FOREIGN KEY (prenajom_id) REFERENCES MA_prenajmy(id),
  FOREIGN KEY (zariadenie_id) REFERENCES MA_zariadenia(id)
);

CREATE TABLE MA_platby (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prenajom_id INT NOT NULL,
  metoda ENUM('bankovy_prevod', 'paypal', 'google_pay', 'apple_pay'),
  suma DECIMAL(10, 2) NOT NULL,
  stav ENUM('cakajuca', 'uhradena', 'zlyhala') DEFAULT 'cakajuca',
  datum TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (prenajom_id) REFERENCES MA_prenajmy(id)
);

CREATE TABLE MA_opravy (
  id INT AUTO_INCREMENT PRIMARY KEY,
  zariadenie_id INT NOT NULL,
  popis TEXT,
  cena DECIMAL(10, 2),
  stav ENUM('nahlasena', 'prebieha', 'dokoncena') DEFAULT 'nahlasena',
  datum TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (zariadenie_id) REFERENCES MA_zariadenia(id)
);

CREATE TABLE MA_recenzie (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pouzivatel_id INT NOT NULL,
  zariadenie_id INT NOT NULL,
  hodnotenie INT CHECK (hodnotenie BETWEEN 1 AND 5),
  komentar TEXT,
  datum TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pouzivatel_id) REFERENCES MA_pouzivatelia(id),
  FOREIGN KEY (zariadenie_id) REFERENCES MA_zariadenia(id)
);

CREATE TABLE MA_obrazky (
  id INT AUTO_INCREMENT PRIMARY KEY,
  zariadenie_id INT NOT NULL,
  cesta_k_suboru VARCHAR(255),
  FOREIGN KEY (zariadenie_id) REFERENCES MA_zariadenia(id)
);