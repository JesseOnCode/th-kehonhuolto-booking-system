# Hierontavarausjärjestelmä (Booking System)

Tämä on kevyt ja käyttäjäystävällinen ajanvarausjärjestelmä, joka on suunniteltu erityisesti hierontapalveluille. Järjestelmä on helposti upotettavissa olemassa oleville verkkosivuille Iframe-ratkaisulla.

##  Ominaisuudet

### Asiakkaalle
* **Ei kirjautumispakkoa:** Nopea varaus ilman käyttäjätunnusten luomista.
* **Valittavat palvelut:** 60 min tai 90 min hieronta-ajat.
* **Kalenterinäkymä:** Selkeä kuukausi/viikkonäkymä ja tarkka kellonajan valinta vuorokausitasolla.
* **Lahjaksi ostaminen:** Mahdollisuus ilmoittaa lahjaksi ostetusta ajasta "Lisätietoa varauksesta" -kentän kautta.
* **Vahvistukset:** Automaattinen sähköpostivahvistus varauksen jälkeen.

### Ylläpitäjälle (Admin)
* **Hallintapaneeli:** Mahdollisuus muokata aukioloaikoja ja avoinna olevia päiviä.
* **Ilmoitukset:** Sähköposti-ilmoitus jokaisesta uudesta varauksesta ja peruutuksesta.

---

##  Kerättävät asiakastiedot
Varauksen yhteydessä kerätään seuraavat tiedot:
1.  Etunimi
2.  Sukunimi
3.  Sähköpostiosoite
4.  Puhelinnumero
5.  Lisätietoa varauksesta (vapaaehtoinen tekstikenttä, esim. lahjakortit tai huomiot vaivoista)

---

##  Tekninen toteutus

### Varausvirta (User Flow)
1.  Asiakas valitsee keston (60/90 min).
2.  Asiakas valitsee vapaan päivän ja kellonajan kalenterista.
3.  Asiakas täyttää yhteystiedot.
4.  Järjestelmä lähettää vahvistuksen molemmille osapuolille.

### Peruutusehdot ja hallinta
Asiakas voi peruuttaa varauksen ilman kirjautumista sähköpostivahvistuksessa olevan **yksilöllisen peruutuslinkin** kautta. 

**Peruutussäännöt:**
* **Aikaraja:** Peruutus on tehtävä viimeistään **24 tuntia** ennen varattua aikaa.
* **Maksut:** Alle 24h peruutuksista veloitetaan 50 % hoidon hinnasta.
* **Sairastapaukset:** Suosittelemme perumaan ajan välittömästi flunssaoireiden ilmetessä.
* **Vaihtoehtoiset peruutustavat:** Jos online-peruutus ei onnistu, peruutus tulee tehdä puhelimitse tai tekstiviestillä.

---

##  Tulevaisuuden kehitysideat (Bonus)
* **Käyttäjätunnukset:** Mahdollisuus luoda tili omien varausten hallintaa ja historiatietoja varten.
* **Maksuintegraatio:** Varausten maksaminen suoraan varauksen yhteydessä.
* **SMS-muistutukset:** Automaattinen tekstiviestimuistutus 24h ennen hoidon alkua.

---

##  Lisenssi
Tämä projekti on tarkoitettu hierontayrittäjien käyttöön. Kaikki oikeudet muutoksiin pidätetään.
