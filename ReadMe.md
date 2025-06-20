# Documentație pentru proiectul TW-Project

---

## Descriere generală

**TW-Project** este o platformă web complexă dedicată adopției animalelor de companie, realizată în PHP, CSS și HTML, care oferă utilizatorilor o experiență completă și intuitivă. Site-ul permite navigarea printre animalele disponibile pentru adopție, postarea de anunțuri pentru animale, completarea de formulare de adopție, precum și interacțiuni moderne precum autentificare securizată, încărcare și gestionare imagini, localizare geografică pe hartă și notificări prin flux RSS.

> **Proiect realizat de:**  
> **Tănașa Ionuț Eduard**  
> **Cojocărescu Rebeca Daria**

---

## Workflow detaliat al utilizatorului

### 1. Autentificare și conturi

- Utilizatorul nou poate crea un cont sau se poate autentifica printr-un formular dedicat.
- După autentificare, primește un token JWT care îi validează identitatea pe tot parcursul sesiunii și îi permite accesul la funcționalități avansate.
- Interfața este complet responsive, adaptându-se la orice dispozitiv și păstrând elemente vizuale distinctive (cerc portocaliu, imaginea de pisică).

### 2. Explorarea și vizualizarea animalelor

- Pagina principală listează animalele disponibile pentru adopție, fiecare având un card cu detalii esențiale: nume, specie, imagine reprezentativă etc.
- Fiecare animal are o pagină dedicată (pet-page.php) ce include:
  - Galerie de imagini.
  - Toate informațiile relevante (rasă, vârstă, descriere, stare de sănătate, locație).
  - O hartă Google Maps cu marker pentru fiecare animal; markerul animalului curent este evidențiat cu un cerc roșu.
  - Markeri suplimentari pentru alte animale, care se pot ascunde/afișa în funcție de zoom pentru claritate.
  - Info window la click pe marker, cu detalii și link spre pagina animalului respectiv.

### 3. Adăugare animal spre adopție

- Utilizatorii autentificați pot posta un nou animal completând un formular detaliat (post-pet.php):
  - Poate fi selectată locația pe hartă (manual sau automat, pe baza poziției curente).
  - Se pot încărca mai multe imagini (validare extinsă pentru tip și dimensiune).
  - Se completează toate datele relevante despre animal: specie, vârstă, descriere, personalitate, stare de sănătate, eventuale restricții, program de hrănire etc.
  - Feedback vizual imediat pentru fișierele încărcate și pentru orice eroare.

### 4. Procesul de adopție

- Pe pagina fiecărui animal există un buton pentru inițierea procesului de adopție.
- Utilizatorul completează un formular de adopție, unde oferă date personale, motivația pentru adopție, experiența cu animalele de companie etc.
- Formularul este verificat pe server, iar eventualele erori sunt afișate prietenos, pentru o experiență cât mai clară.
- În funcție de status (pending, aprobat, respins), utilizatorul va primi notificări și va putea urmări stadiul cererii.

### 5. Noutăți și flux RSS

- Există o secțiune dedicată noutăților, unde sunt afișate ultimele animale propuse spre adopție.
- Utilizatorii pot accesa fluxul RSS pentru a primi notificări automate despre noile anunțuri.
- Datele sunt actualizate automat prin AJAX și endpointuri dedicate.

### 6. Administrarea imaginilor

- Fiecare imagine încărcată este validată (tip, dimensiune) și i se generează un nume unic.
- Salvarea se face atât fizic (în uploads/) cât și logic (în baza de date, tabela media).
- La ștergerea unui animal sau a unei imagini, fișierele sunt eliminate de pe server și din baza de date.

### 7. Securitate și bune practici

- Toate operațiile sensibile (postare animal, adopție, upload/ștergere imagini) sunt protejate prin autentificare JWT.
- Cheile secrete și datele sensibile trebuie mutate în variabile de mediu la instalarea pe producție.
- Uploadul de fișiere este validat riguros pentru a preveni upload-uri malițioase.

---

## Structura proiectului

- **backend/**
    - `public/` – Punctul de intrare (ex: index.php), endpointuri publice pentru API/backend.
- **test/**
    - `models/` – Modele pentru animale, imagini, logica de business, formulare.
    - `utils/` – Utilitare (ex: JWTManager – gestionare autentificare și token-uri).
    - `view/` – Pagini PHP pentru UI (vizualizare animale, formular adopție, postare animal, știri etc.).
    - `stiluri/` – CSS pentru layout responsiv și elemente personalizate.

---

## Structura bazei de date

Structura inițială a bazei de date acoperă toate nevoile de bază pentru useri, animale, adopții, program de hrănire, restricții, media (poze/video), istoric medical și flux RSS. Ulterior, s-au adăugat tabele suplimentare pentru mesaje între utilizatori și pentru formulare detaliate de adopție ("pet form"). Vezi diagrama de mai jos pentru relaționare:

![image1](image1)

**Tabele principale:**
- **user:** datele utilizatorului (nume, email, parolă, locație, rol, familie, coordonate).
- **PET:** date complete despre animal (specie, rasă, stare, adresă, disponibilitate, coordonate, owner etc.).
- **Adoption:** legătura între animal și adoptator, cu status și dată.
- **Feeding Schedule:** program de hrănire pe animal (descriere, oră, frecvență).
- **restriction:** restricții alimentare sau comportamentale pentru animal.
- **media:** poze/video asociate cu animalul.
- **Medical history:** istoric medical și proceduri pentru animal.
- **RSS Feed:** știri și noutăți legate de animale.
- **Mesaje:** (adăugat ulterior) - pentru comunicare între utilizatori.
- **Pet Form:** (adăugat ulterior) - pentru formulare detaliate de adopție și feedback.

---

## Tehnologii folosite

- **Backend:** PHP, conectare cu Oracle DB (OCI8), JWT (Firebase PHP JWT)
- **Frontend:** HTML, CSS (responsive), JavaScript (Google Maps, AJAX)
- **Altele:** RSS feed, validare formular, upload imagini.

---

## Tutorial complet pentru instalare și rulare locală

### 🔧 Ce trebuie să instalezi:

#### 1. Server local (cu PHP și Apache)

- Recomandat: **XAMPP** (Windows/Linux/macOS) – conține Apache, PHP și MySQL.
    - Alternativ: Bitnami WAMP/LAMP/MAMP.
    - **Descarcă de aici:** https://www.apachefriends.org
    - *Ignoră MySQL, vei folosi Oracle!*

#### 2. Oracle Database (gratuit)

- **Oracle Database 21c/23c XE (Express Edition)**
    - *Descarcă de aici:* https://www.oracle.com/database/technologies/xe-downloads.html
    - Creează-ți cont Oracle dacă nu ai.

#### 3. Oracle SQL Developer (opțional, dar recomandat)

- Interfață grafică pentru baza de date.
    - *Descarcă de aici:* https://www.oracle.com/tools/downloads/sqldev-downloads.html

#### 4. PHP Oracle extension

- **OCI8** (extensie PHP pentru Oracle)
- **Oracle Instant Client** (biblioteci pentru conectare)
    - *Ghid oficial instalare:* https://www.php.net/manual/en/oci8.installation.php

---

### Instalare pas cu pas (Windows)

#### 1. Descarcă și instalează XAMPP

- Intră pe https://www.apachefriends.org/index.html
- Descarcă varianta potrivită pentru sistemul tău.
- Rulează installer-ul, apasă "Next" la fiecare pas, lasă folderele implicite.
- La final, pornește XAMPP Control Panel și apasă "Start" la Apache (fundalul devine verde).
- Testează accesând http://localhost în browser.

#### Cerințe minime:
- Windows 10/11 64-bit, 2 GB RAM (4 GB recomandat), 10 GB spațiu liber, drepturi administrator.

#### 2. Instalează Oracle Database XE

- Descarcă arhiva de pe site-ul Oracle și extrage-o.
- Rulează setup.exe ca administrator, acceptă licența, alege folderul de instalare.
- Setează parola pentru SYS, SYSTEM, PDBADMIN (noteaz-o!).
- După instalare, deschide „SQL Command Line” sau „SQL Plus” și conectează-te:
  ```
  CONNECT sys as sysdba
  ```
- Folosește parola setată la instalare. Dacă vezi promptul SQL>, totul funcționează.

#### 3. Instalează și configurează Oracle SQL Developer (opțional)

- Descarcă arhiva, extrage și rulează `sqldeveloper.exe`.
- Creează conexiune nouă cu:
    - Username: `system`
    - Password: parola ta
    - Hostname: `localhost`
    - Port: `1521`
    - Service Name: `XEPDB1`
- Testează conexiunea (Status: Success).

#### 4. Instalează și configurează Oracle Instant Client + OCI8

- Descarcă Oracle Instant Client de aici: https://www.oracle.com/database/technologies/instant-client/downloads.html
- Extrage arhiva în `C:\instantclient`
- Adaugă folderul la variabila PATH și creează variabila TNS_ADMIN cu valoarea `C:\instantclient`
- Găsește fișierul `php.ini` (ex: `C:\xampp\php\php.ini`)
    - Caută și activează linia:
      ```
      extension=php_oci8_19
      ```
    - Salvează, repornește Apache din XAMPP.

#### 5. Testează conexiunea PHP ↔ Oracle

- Creează fișierul `test_oracle.php` în `htdocs`:

  ```php
  <?php
  $conn = oci_connect("system", "parola123", "localhost/XEPDB1");
  if (!$conn) { $e = oci_error(); die("Eroare conexiune: " . $e['message']); }
  echo "Conexiune reușită!";
  oci_close($conn);
  ?>
  ```

- Accesează http://localhost/test_oracle.php; dacă vezi "Conexiune reușită!", totul funcționează.

---

## Detalii de implementare

### a) Integrare Google Maps

- Fiecare animal are coordonate, afișate pe hartă cu marker, info window și link spre detalii.
- Zoom-ul hărții controlează vizibilitatea markerilor pentru a evita aglomerarea.
- Animalul curent e evidențiat cu un cerc.

### b) Upload și validare imagini

- Imaginile sunt verificate (tip și dimensiune) și au nume unic generat automat.
- Salvarea se face fizic și logic (în baza de date).
- Ștergerea unui animal sau imagini elimină fișierele și din server și din DB.

### c) Formulare și UI/UX

- Formularele pentru postare animal și adopție sunt validate și prietenoase, cu feedback instant.
- Stilizare CSS adaptivă; UI modern cu elemente grafice distinctive.
- Popup-uri pentru succes/eroare, spinner de loading, etc.

### d) Flux RSS

- Ultimele animale propuse spre adopție sunt afișate printr-un endpoint RSS.
- Utilizatorii pot urmări fluxul pentru a fi notificați de animale noi.

### e) Mesagerie și formulare avansate

- Modul de mesaje între utilizatori pentru comunicarea directă adoptator-proprietar.
- Tabele suplimentare pentru formulare complexe de adopție (pet form), cu feedback și statusuri detaliate.

---

## Securitate & bune practici

- Cheile sensibile (JWT, DB) nu trebuie hardcodate – folosește variabile de mediu.
- Autentificarea și autorizarea sunt obligatorii pentru funcții sensibile.

---

## Recomandări de extindere

- Testare automată pentru modele și API.
- Integrare email pentru notificări la adopție.
- Paginare și filtre avansate pentru animale.
- Îmbunătățire UX (ex: progres bar la upload, autocomplete adresă pe hartă).
- Statistici pentru administratori (număr animale, adopții, feedback etc.).

---

## Concluzie

**TW-Project** este o platformă completă și scalabilă pentru adopția animalelor de companie, cu workflow modern, UI adaptiv, funcționalități mature și o bază de date relațională extensibilă, care acoperă tot fluxul de la postare animal, vizualizare detalii, completare formular și adopție, până la gestionarea noutăților, mesageriei și securitatea datelor.

---
