# Ajanvarausjärjestelmä hierontayritykselle – Projektisuunnitelma

## Tekijät
- Jesse Haapaniemi  
- Tomi Husso  
- Joonas Eskelinen  

---

## 1. Projektin yleiskuvaus
Tämän projektin tavoitteena on toteuttaa selainpohjainen ajanvarausjärjestelmä yhden henkilön hierontayritykselle. Järjestelmän avulla asiakkaat voivat varata hieronta-aikoja verkossa ennalta määriteltyihin vapaisiin aikoihin.

Järjestelmä tallentaa varaukset tietokantaan ja estää päällekkäiset varaukset. Yrityksen omistaja voi hallinnoida varauksia ja tarkastella tulevia asiakasaikoja.

Projekti toteutetaan tiimityönä ja sen kehityksessä hyödynnetään GitHub-versionhallintaa.

---

## 2. Projektin tavoitteet
- Toteuttaa toimiva ja helppokäyttöinen ajanvarausjärjestelmä hierontayritykselle
- Harjoitella relaatiotietokannan suunnittelua ja SQL-kyselyitä
- Kehittää backend-toiminnallisuuksia PHP:llä
- Harjoitella tiimityöskentelyä ja versionhallintaa
- Dokumentoida projekti selkeästi ja ymmärrettävästi

---

## 3. Käytettävät teknologiat
- **Ohjelmointikieli (backend):** PHP + JavaScript  
- **Tietokanta:** MySQL / MariaDB  
- **Frontend:** HTML, CSS, JavaScript  
- **Versionhallinta:** Git ja GitHub  
- **Palvelinympäristö:** Apache (esim. XAMPP / MAMP) kehitysvaiheessa. Valmis projekti webhotellissa.

---

## 4. Järjestelmän toiminnallisuudet

### 4.1 Asiakkaat
- Vapaiden hieronta-aikojen tarkastelu
- Ajan varaaminen
- Varausvahvistuksen vastaanottaminen

### 4.2 Ajanvaraukset
- Varausten luominen
- Varausten tarkastelu
- Varausten peruminen
- Päällekkäisten varausten estäminen

### 4.3 Yrittäjän hallintanäkymä
- Kirjautuminen hallintapaneeliin
- Varausten tarkastelu ja hallinta
- Työaikojen ja vapaiden aikojen määrittely

---

## 5. Tietokantarakenne
Tietokanta koostuu seuraavista tauluista (esimerkki):

- `asiakkaat`
- `vapaat ajat`
- `palvelu`
- `hieronnan kesto`
- `admin_users`

Taulujen väliset relaatiot:
- Yhdellä asiakkaalla voi olla useita varauksia
- Yksi varaus liittyy yhteen palveluun ja yhteen ajankohtaan
- Yrittäjä hallinnoi kaikkia varauksia

(Tarkempi tietokantakuvaus ja SQL-rakenne lisätään myöhemmin.)

---

## 6. Työnjako tiimissä

| Tiimin jäsen | Vastuualue |
|-------------|------------|
| Jesse Haapaniemi | Tietokanta ja SQL |
| Tomi Husso | Backend (PHP) |
| Joonas Eskelinen | Frontend ja dokumentaatio |

---

## 7. Aikataulu

| Viikko | Tehtävät |
|------|---------|
| Viikko 1 | Suunnittelu ja tietokantamalli |
| Viikko 2 | Backend-kehitys |
| Viikko 3 | Frontend-kehitys |
| Viikko 4 | Testaus ja viimeistely |

---

## 8. Versionhallinta
Projektissa käytetään GitHubia versionhallintaan. Kaikki tiimin jäsenet työskentelevät saman repositorion parissa ja muutokset dokumentoidaan selkeillä ja kuvaavilla commit-viesteillä.

---

## 9. Riskit ja haasteet
- Aikataulun hallinta
- Ajanvarausten päällekkäisyyksien estäminen
- Tietokantavirheet
- Versionhallintaan liittyvät ristiriidat

Riskejä pyritään minimoimaan huolellisella suunnittelulla, testaamisella ja säännöllisellä tiimityöskentelyllä.

---

## 10. Yhteenveto
Projektin tavoitteena on kehittää käytännöllinen ja selkeä ajanvarausjärjestelmä yhden henkilön hierontayritykselle. Projektin aikana opitaan ohjelmistokehitystä, tietokantasuunnittelua sekä tiimityöskentelyä. Projekti dokumentoidaan ja hallinnoidaan GitHubin avulla.
