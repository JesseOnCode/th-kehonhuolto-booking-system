# Hieronta-varausjärjestelmän tiedostojen kuvaus.

## Tämä dokumentti selittää järjestelmän PHP-tiedostojen käyttötarkoitukset ja niiden väliset suhteet.

## Järjestelmän ydin
- db_config.php: Järjestelmän keskeisin tiedosto, joka muodostaa PDO-yhteyden tietokantaan. 
- Se sisältää myös tärkeitä apufunktioita, kuten safe_output()-funktion  XSS-suojausta varten, tällä estetään haitallisen JavaScript-koodin syöttäminen järjestelmään.


## Asiakaspuolen varaustoiminnot
# Asiakkaan varausprosessi etenee seuraavassa järjestyksessä:

## booking.php: 
- Varauskalenterin käyttöliittymä, jossa asiakas valitsee hoidon ja päivämäärän. Se käyttää JavaScriptiä dynaamiseen ajan hakuun.

get_available_times.php: AJAX-taustatiedosto, joka hakee tietokannasta valitun päivän vapaat ajat huomioiden hoidon keston ja tauot.

confirm_booking.php: Kerää asiakkaan yhteystiedot ja vahvistaa valitun ajan ennen tallennusta. Tiedosto huolehtii myös CSRF-suojauksesta.

save_appointment.php: Lopullinen tallennustiedosto. Se tarkistaa syötteet, luo varauksen appointments-tauluun ja poistaa vastaavan ajan available_times-taulusta.

Hallintapaneeli (Admin)
Yrittäjän työkalu ajanvarausten hallintaan:

admin_login.php: Kirjautumissivu ylläpitoon.

admin_dashboard.php: Hallinnan pääsivu, josta näkee varauskirjan ja vapaat ajat. Sisältää suojaukset istunnon aikakatkaisulle (30min) ja CSRF-hyökkäyksille.

add_time_range.php: Generoi automaattisesti useita vapaita slotteja valitulle aikavälille (30 minuutin välein).

add_time.php: Lisää yksittäisen varauksen suoraan varauskirjaan. Tämä lisätty asiakkaiden takia, jotka eivät varaa aikaa varauskalenterista, vaan puhelimitse tai muiden kanavien kautta.

delete_appointment.php: Poistaa tai peruuttaa asiakkaan tekemän varauksen.

delete_time.php: Poistaa yksittäisen vapaana olevan ajan varattavien listalta.

logout.php: Kirjaa käyttäjän ulos ja tuhoaa istunnon.

password_hash.php: Työkalu uusien salasanojen turvalliseen luontiin tietyn ajan välein.