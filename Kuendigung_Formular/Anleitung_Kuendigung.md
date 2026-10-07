# Anleitung: Kündigungsfunktion einbauen (Verzeichnis `Kuendigung_Formular`)

Stand 06.10.2026, Fassung 1. Rechtsgrundlage: § 312k BGB („Kündigungsbutton“, Pflicht seit 01.07.2022; gilt für Verbraucherverträge mit wiederkehrender Zahlung, die online geschlossen werden können). Hintergrund: `Analyse_Widerruf_Kuendigung.pdf` (WK4, WK5, A.5), `Programmierliste_Widerruf_Kuendigung.pdf` (K1–K8). Aufbau und Schutz vor Missbrauch entsprechen der Widerrufsfunktion (`Widerruf_Formular`).

## 1. Inhalt des Verzeichnisses

| Datei | Zweck |
|---|---|
| `vertraege-kuendigen.html` | Bestätigungsseite „Verträge hier kündigen“ (Deutsch). Enthält nur den Inhaltsbereich; Kopf- und Fußzeile kommen aus der Seitenvorlage. |
| `kuendigung.php` | Auswertung (PHP 8): prüft Eingaben, prüft auf Missbrauch (Anhang A), vergibt Vorgangsnummer mit Zeitstempel, schreibt Protokoll, sendet Kündigungsbestätigung an den Kunden und Meldung an die Bearbeiter der Member-Mails, zeigt eine druck- und speicherbare Bestätigungsseite. |
| `Anleitung_Kuendigung.md` / `.pdf` | diese Anleitung |

## 2. Gesetzliche Anforderungen und Umsetzung

| Anforderung § 312k BGB | Umsetzung |
|---|---|
| Kündigungsschaltfläche gut lesbar mit „Verträge hier kündigen“ o. ä., ständig verfügbar, unmittelbar und leicht zugänglich | Link in der Fußzeile jeder Seite und im Kontomenü (Abschnitt 3) |
| Schaltfläche führt **unmittelbar** zur Bestätigungsseite; keine Anmeldung (LG München I), keine Zwischenseiten, keine Halteangebote (BGH 2025) | `vertraege-kuendigen.html` ist die Bestätigungsseite; ohne Anmeldung; nur sachliche Hinweise |
| Angaben: Art der Kündigung, bei außerordentlicher Kündigung der Grund | Auswahl ordentlich / außerordentlich; Feld „Grund“ (Pflicht bei außerordentlicher) |
| Angabe des Zeitpunkts | „zum nächstmöglichen Zeitpunkt“ (Voreinstellung) oder Datum |
| Eindeutige Identifizierung | Name, Benutzername oder Rechnungsnummer; Vertrag (freiwillig) |
| Elektronischer Kommunikationsweg für die Bestätigung | E-Mail-Adresse |
| Bestätigungsschaltfläche „jetzt kündigen“ o. ä. | Schaltfläche „**Jetzt kündigen**“ |
| Erklärung mit Datum und Uhrzeit speicherbar | Bestätigungsseite mit Datum, Uhrzeit und allen Angaben; Hinweis auf Drucken / PDF |
| Sofortige Bestätigung auf dauerhaftem Datenträger: Inhalt, Datum und Uhrzeit des Eingangs, Zeitpunkt, zu dem der Vertrag beendet werden soll | E-Mail an den Kunden; das genaue Vertragsende teilen die Bearbeiter der Member-Mails nach Prüfung gesondert mit (Abschnitt 6) |
| Kündigung ohne Zeitangabe gilt im Zweifel zum frühestmöglichen Zeitpunkt | Voreinstellung „zum nächstmöglichen Zeitpunkt“ |

## 3. Seite einbauen

1. Seite unter einer festen Adresse veröffentlichen, z. B. `https://www.weatheronline.co.uk/vertraege-kuendigen`. **Ohne Anmeldung** erreichbar.
2. In die Seitenvorlage übernehmen: Inhalt von `<main>…</main>`, `<style>`-Block und das kleine Skript am Seitenende. Den einfachen `<header>` durch den Kopf der Webseite ersetzen; die Fußzeile kommt dynamisch aus der Vorlage.
3. `<meta name="robots" content="noindex">` beibehalten; keine externen Ressourcen, keine Werbung, kein Tracking auf dieser Seite.
4. Platzhalter ersetzen: `[Link zu den AGB]`, `[Link zur Datenschutzerklärung]`.
5. Feldnamen nicht ändern (`art`, `grund`, `zeitpunkt`, `datum`, `name`, `vertrag`, `produkt`, `email`, `mitteilung`, `website`, `dauer`).
6. Beschriftungen „Verträge hier kündigen“ (Überschrift/Link) und „Jetzt kündigen“ (Schaltfläche) nicht ändern.
7. **Keine Halteangebote** (Rabatte, „Wollen Sie wirklich…?“, Umfragen) auf dieser Seite oder zwischen Link und Seite einfügen.
8. Link „Verträge hier kündigen“: in der Fußzeile **jeder Seite** von www.weatheronline.co.uk und member.weatheronline.co.uk (dpds nicht nötig – keine Verträge mit Verlängerung) und im Kontomenü; gut lesbar. Ziel: Adresse aus 3.1.

## 4. `kuendigung.php` einrichten

1. In dasselbe Verzeichnis wie die Seite legen oder `action="kuendigung.php"` anpassen.
2. Einstellungen am Dateianfang:
   - `MEMBER_MAIL` = member@weatheronline.co.uk (Empfänger der internen Meldung), `ABSENDER` = WeatherOnline <member@weatheronline.co.uk>; Absender-Domain mit SPF/DKIM/DMARC.
   - `DATENVERZ` (Protokoll `kuendigungen.csv`, Zähler `zaehler.json`) **außerhalb des Web-Verzeichnisses**, beschreibbar, nicht öffentlich.
   - `IP_SALZ`: eigener geheimer Zufallswert (kann derselbe wie beim Widerruf sein).
   - `HINTER_CLOUDFLARE`: `true` nur, wenn der Server ausschließlich über Cloudflare erreichbar ist.
   - Grenzwerte wie beim Widerruf (3 je IP und Stunde, Alarm ab 20, Mindestzeit 3 Sekunden).
3. E-Mail-Versand: `mail()` oder SMTP-Bibliothek (Funktion `sende()`).
4. HTTPS erzwingen, HSTS setzen, `expose_php = Off`.

## 5. Testen (vor Freischaltung)

Lokaler Test vom 06.10.2026: alle Fälle wie erwartet.

| Test | Erwartung |
|---|---|
| Ordentlich, nächstmöglicher Zeitpunkt, Pflichtfelder ausgefüllt | Bestätigungsseite mit Datum, Uhrzeit, Art, gewünschtem Vertragsende; E-Mail an Kunde und Bearbeiter der Member-Mails; Zeile in der CSV |
| Außerordentlich ohne Grund | Fehlerseite (HTTP 422) |
| Datum in der Vergangenheit oder ungültig | Fehlerseite (HTTP 422) |
| Außerordentlich mit Grund und Datum | Bestätigung mit Grund und „zum TT.MM.JJJJ“ |
| Feld `website` ausgefüllt | Seite „Vielen Dank“, nichts versandt, nichts protokolliert |
| Absenden schneller als 3 Sekunden / 4. Absendung je IP und Stunde | Bestätigungsseite; **keine** Kunden-E-Mail; „[PRÜFEN]“-Meldung; Protokoll „auffaellig“ |
| Link in Mitteilung oder Grund | in der Kunden-E-Mail „[Link entfernt]“ |
| Name beginnt mit `=` | in der CSV mit `'` |
| 20 Absendungen in 60 Minuten | eine Alarm-E-Mail |
| GET auf `kuendigung.php` | Weiterleitung auf die Seite |
| Link „Verträge hier kündigen“ | auf www und member sichtbar, ohne Anmeldung nutzbar, führt direkt zur Seite |

## 6. Bearbeitung eingegangener Kündigungen (Bearbeiter der Member-Mails)

Pro Meldung, **am selben Arbeitstag**:

1. Vertrag in aMember suchen. **Angegebene E-Mail-Adresse mit der im Konto vergleichen** – weicht sie ab, vor der Umsetzung über die Konto-E-Mail-Adresse nachfragen (Schutz gegen Kündigung durch Dritte).
2. Vertragsende bestimmen:
   - ordentlich: Ende der laufenden Laufzeit (Monat bzw. Jahr); bei einem gewünschten früheren Datum: Laufzeitende, sofern keine kürzere Frist gilt;
   - Verbraucher in Deutschland mit Jahresmitgliedschaft nach dem ersten Jahr: Kündigungsfrist höchstens ein Monat (§ 309 Nr. 9 BGB, Analyse WK7);
   - außerordentlich: Grund prüfen; bei wichtigem Grund sofort bzw. zum gewünschten Datum.
3. Kündigung in aMember eintragen und **PayPal-Abonnement zum Vertragsende im PayPal-Händlerkonto beenden** (aMember beendet es nicht automatisch, WK5).
4. Dem Kunden das **genaue Vertragsende** per E-Mail mitteilen (an die Konto-E-Mail-Adresse und die angegebene Adresse).
5. Meldungen mit „[PRÜFEN]“: Kunde hat keine automatische Bestätigung erhalten. Bei echter Kündigung die Bestätigung mit Inhalt, Datum und Uhrzeit des Eingangs und Vertragsende von Hand senden.
6. Erledigung in der CSV-Datei oder im Ticketsystem vermerken.

## 7. Datenschutz und Aufbewahrung

- Protokoll und E-Mails als Nachweis; Aufbewahrung 3 Jahre ab Jahresende (EU) bzw. 6 Jahre (UK), danach löschen.
- Zählerdatei: nur gekürzter, gesalzener Hash der IP-Adresse; Löschung nach 60 Minuten.
- Datenschutzerklärung Abschnitt 3.3.5 „Widerruf und Kündigung“ (Analyse, Anhang A.8) einschließlich des Satzes zur IP-Kurzspeicherung deckt auch die Kündigung ab.

## 8. Später anzupassen

- Englische Fassung für das Vereinigte Königreich; dort ab Januar 2027 einfache Online-Kündigung nach dem DMCCA – diese Seite erfüllt das sinngemäß.
- Optional: Abfrage des Vertragsendes direkt aus aMember, damit die automatische Bestätigung das genaue Datum enthält.

---

## Anhang A: Schutz vor Missbrauch – Analyse und Stand (06.10.2026)

### A.1 Ausgangslage

Die Kündigungsfunktion muss **ohne Anmeldung** und **unmittelbar** zugänglich sein; Halteangebote und zusätzliche Hürden sind unzulässig. Die Kündigung gilt mit dem Absenden als erklärt; die Bestätigung ist sofort per E-Mail zu senden. Schutzmaßnahmen dürfen daher eine echte Kündigung weder verhindern noch verzögern: kein CAPTCHA, keine Anmeldung, keine Bestätigungslinks. Geeignet sind unsichtbare Maßnahmen, die **niemals eine Kündigung verwerfen**, sondern nur die automatische Kunden-E-Mail zurückhalten und den Vorgang an die Bearbeiter der Member-Mails zur Prüfung geben.

### A.2 Gefährdungen

| Nr. | Gefährdung | Folge ohne Schutz |
|---|---|---|
| G1 | Missbrauch als E-Mail-Versender (fremde Adresse, Werbetext in „Mitteilung“ oder „Grund“) | Spam unter dem Namen WeatherOnline; Sperrung der Absender-Domain |
| G2 | **Kündigung durch Dritte** mit bekanntem Benutzernamen | Vertragsende gegen den Willen des Mitglieds; Verlust eines zahlenden Kunden |
| G3 | Formel-Einschleusung in die CSV-Datei | Ausführung beim Öffnen in Excel o. Ä. |
| G4 | Massenhafte automatische Absendungen | Flut von E-Mails und Protokolleinträgen |
| G5 | Einschleusen von E-Mail-Kopfzeilen | Versand an beliebige Empfänger |
| G6 | Einschleusen von HTML/Skripten in die Bestätigungsseite | fremde Skripte im Browser |
| G7 | Absenden von fremden Webseiten aus (CSRF) | Kündigungen im Namen ahnungsloser Besucher |
| G8 | Gefälschte E-Mails im Namen von WeatherOnline | Täuschung von Kunden |

### A.3 Umgesetzte Maßnahmen

| Nr. | Maßnahme | Umsetzung | gegen | Test 06.10.2026 |
|---|---|---|---|---|
| M0 | Fallenfeld, nur POST, Pflichtfelder, gültige E-Mail, Datum geprüft (heute bis in 3 Jahren), Längenbegrenzung | `kuendigung.php`, HTML | G4 | ✔ |
| M0 | Kopfzeilen-Schutz; feste Empfänger und Absender | Zeilenumbrüche entfernt | G5 | ✔ |
| M0 | Ausgabe maskiert; Sicherheits-Header (`Content-Security-Policy` ohne Skripte, `nosniff`, kein Einbetten) | Bestätigungsseite | G6 | ✔ |
| **M1(a)** | Höchstens 3 Absendungen je IP-Adresse in 60 Minuten; danach Speicherung und „[PRÜFEN]“-Meldung, keine Kunden-E-Mail | `zaehle()` | G1, G4 | ✔ |
| **M1(b)** | Links in „Mitteilung“ und „Grund“ in der Kunden-E-Mail durch „[Link entfernt]“ ersetzt | `ohneLinks()` | G1 | ✔ |
| **M1(c)** | Alarm an die Bearbeiter der Member-Mails ab 20 Absendungen in 60 Minuten, höchstens einmal je Stunde | `zaehle()` | G1, G4 | ✔ (Logik wie Widerruf) |
| **M2** | Abgleich der E-Mail-Adresse mit dem Konto vor der Umsetzung; Hinweis in der Kunden-E-Mail „Falls Sie diese Kündigung nicht selbst abgesendet haben …“; Mitteilung des Vertragsendes auch an die Konto-E-Mail-Adresse | Abschnitt 6 | G2 | organisatorisch |
| **M3** | CSV-Werte mit `=`, `+`, `-`, `@` am Anfang mit `'` gespeichert | `csvSicher()` | G3 | ✔ |
| **M4** | Mindestzeit 3 Sekunden zwischen Seitenaufruf und Absenden; ohne JavaScript nicht bewertet | Skript in der Seite, `dauer` | G4 | ✔ |

### A.4 Datenschutz der Schutzmaßnahmen

Wie beim Widerruf: keine IP-Adresse im Klartext, nur ein gekürzter, gesalzener SHA-256-Wert; Löschung nach 60 Minuten; Rechtsgrundlage berechtigtes Interesse (Art. 6 Abs. 1 lit. f DSGVO bzw. UK GDPR).

### A.5 Was erreicht ist

- Eine echte Kündigung wird in jedem Fall **gespeichert, an die Bearbeiter der Member-Mails gemeldet und auf der Seite mit Datum, Uhrzeit und gewünschtem Vertragsende bestätigt** – auch bei Verdacht auf Missbrauch.
- Die Seite erfüllt die Angaben- und Schaltflächenpflichten des § 312k BGB ohne Anmeldung und ohne Halteangebote.
- Missbrauch als E-Mail-Versender ist begrenzt (3 je IP und Stunde, keine anklickbaren Links), Bots werden erkannt, ein Angriff löst einen Alarm aus.
- Kopfzeilen-, HTML- und Formel-Einschleusung sind unterbunden.
- Kündigung durch Dritte wird durch den Abgleich mit der Konto-E-Mail-Adresse und die Benachrichtigung an diese Adresse aufgedeckt, bevor sie umgesetzt wird.

### A.6 Was offen ist

| Nr. | Offener Punkt | Bewertung / Vorschlag | Zuständig |
|---|---|---|---|
| O1 | Verteilte Angriffe über viele IP-Adressen | Restrisiko; Alarm erkennt es; zusätzlich Cloudflare-Begrenzung für `kuendigung.php` | Server / Cloudflare |
| O2 | Gemeinsame IP-Adressen: echte Kunden ohne automatische Bestätigung | „[PRÜFEN]“-Meldungen am selben Tag bearbeiten, Bestätigung von Hand senden | Bearbeiter der Member-Mails |
| O3 | Automatische Bestätigung enthält das **gewünschte**, nicht das **genaue** Vertragsende | gesetzlich ausreichend (Zeitpunkt, zu dem der Vertrag beendet werden „soll“); genaues Datum folgt von Hand (Abschnitt 6.4); optional aMember-Abfrage (Abschnitt 8) | Bearbeiter der Member-Mails / Programmierung |
| O4 | Verschleierte Links werden nicht erkannt | gering, da nicht anklickbar | – |
| O5 | Ohne JavaScript keine Zeitprüfung | gewollt | – |
| O6 | CSRF-Token nicht umgesetzt | geringes Risiko, da jeder Eingang geprüft und an die Konto-Adresse gemeldet wird; optional | Programmierung |
| O7 | SPF, DKIM, DMARC | vor dem Einsatz einrichten | Server / E-Mail |
| O8 | `HINTER_CLOUDFLARE` und `IP_SALZ` | vor dem Einsatz prüfen bzw. ersetzen | Server / Programmierung |
| O9 | PayPal-Abonnement wird nicht automatisch beendet | Beendigung von Hand bis zur Umstellung (WK5) | Bearbeiter der Member-Mails |
