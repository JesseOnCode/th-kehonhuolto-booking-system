# Järjestelmän tietoturvadokumentaatio
## Tämä dokumentti kuvaa ajanvarausjärjestelmän tietoturvaratkaisut, toteutetut suojaukset ja käytetyt parhaat käytännöt (OWASP Best Practices).

## 1. SQL-injektioiden esto (SQLi)
SQL-injektio on yksi yleisimmistä verkkohyökkäyksistä. Järjestelmä suojautuu tältä seuraavasti:

- PDO Prepared Statements: Kaikki tietokantakyselyt suoritetaan valmisteltuina lauseina. Tiedot lähetetään erillään SQL-komennosta, jolloin hyökkääjä ei voi muuttaa kyselyn rakennetta.

- Emulate Prepares pois päältä: Asetus PDO::ATTR_EMULATE_PREPARES => false varmistaa, että tietokantapalvelin hoitaa kyselyiden valmistelun aidosti ja turvallisesti.

- Tietotyyppien validointi: Esimerkiksi treatment_id ja muut ID-kentät tarkistetaan aina kokonaisluvuiksi (FILTER_VALIDATE_INT) ennen käyttöä.

## 2. XSS-suojaus (Cross-Site Scripting)
XSS-hyökkäyksessä sivustolle yritetään syöttää haitallista skripti koodia. Suojaus toteutetaan kahdella tasolla:

- safe_output() -funktio: Kaikki tietokannasta tuleva tieto ajetaan htmlspecialchars()-funktion läpi ennen tulostusta. Tämä muuttaa haitalliset merkit, kuten < ja >, vaarattomaksi tekstiksi.

- Content Security Policy (CSP): Sivuilla on käytössä CSP-otsikko, joka rajoittaa sallitut skriptien ja tyylien lähteet vain omaan palvelimeen ('self'). Tämä estää ulkopuolisten haitallisten koodien lataamisen.

## 3. CSRF-suojaus (Cross-Site Request Forgery)
CSRF-hyökkäyksellä yritetään huijata ylläpitäjä tai asiakas suorittamaan toimintoja (esim. ajan poisto) ilman hänen suostumustaan.

- CSRF-tokenit: Jokainen järjestelmän lomake sisältää uniikin, istuntokohtaisen turvakoodin (token).

- Validointi: Tallennus- ja poistoskriptit (save_appointment.php, delete_time.php) tarkistavat, että lomakkeen lähettämä koodi täsmää käyttäjän istunnossa olevaan koodiin.

## 4. Brute Force ja Rate Limiting
Järjestelmä rajoittaa automaattisten robottien ja hyökkäysyritysten nopeutta:

- Varausten rajoitus: Asiakas voi tehdä uuden varauksen vasta 10 sekunnin odotuksen jälkeen (last_booking timestamp).

- Kirjautumisen rajoitus: Järjestelmä seuraa kirjautumisyrityksiä ja estää pääsyn väliaikaisesti liian monen virheellisen yrityksen jälkeen (esim. 5 yritystä / 15 min).

## 5. Tietojen eheys ja transaktiot
- Tietokannan eheys varmistetaan käyttämällä transaktioita (beginTransaction, commit, rollBack).

- Kaikki tai ei mitään: Esimerkiksi varauksen tallentaminen ja vapaan ajan poistaminen tapahtuvat yhtenä atomisena toimintona.

- Virhehallinta: Jos jokin osa tallennuksesta epäonnistuu, rollBack() palauttaa tietokannan alkutilaan, estäen puolittaisten tai korruptoituneiden tietojen tallentumisen.

## 6. Syötteiden käsittely ja sanitointi
- Kaikki käyttäjältä tuleva tieto käsitellään epäluotettavana:

- strip_tags() ja trim(): Syötteistä poistetaan HTML-koodi ja ylimääräiset välilyönnit.

- Unicode-tuki: Sähköpostiosoitteiden validointi sallii ääkköset (ä, ö), mutta varmistaa silti sähköpostin oikean muodon (preg_match /u).