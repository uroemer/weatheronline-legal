# Datenschutzerklärung — Rechtsgrundlagen und weltweite Gültigkeit

Dieses Dokument erläutert, welche Datenschutzgesetze weltweit auf WeatherOnline anwendbar sind, welche Pflichtinhalte jeweils gefordert werden und wie die Datenschutzerklärung diese erfüllt.

---

## Besonderheit: IP-Anonymisierung

Alle App-Anfragen durchlaufen einen Cloudflare Worker, der die IP-Adresse vor Erreichen des Servers anonymisiert (/24 IPv4, /48 IPv6). Da keine vollständigen IP-Adressen gespeichert werden und keine Nutzerdaten erhoben werden, entfallen viele sonst verpflichtende Angaben:

- Kein Nutzer-Tracking → keine Einwilligung erforderlich
- Keine personenbezogenen Daten gespeichert → keine Löschfristen für Nutzerdaten
- Keine Weitergabe an Dritte → keine Drittland-Transfers für Nutzerdaten
- PIPL (China): anonymisierte Subnetze = keine personenbezogenen Daten → PIPL nicht anwendbar
- KVKK (Türkei): IP-Anonymisierung reduziert Registrierungspflichten erheblich
- COPPA (USA): App für alle Altersgruppen geeignet (Wetterinfo) — keine Datenerhebung → COPPA automatisch erfüllt

**Rechtliche Einordnung der Anonymisierung selbst:**

IP-Adressen sind nach CJEU Breyer (C-582/14) personenbezogene Daten. Art. 4(2) DSGVO qualifiziert auch das transiente Empfangen und Löschen als „Verarbeitung" — Erhebung und Vernichtung sind dort ausdrücklich aufgeführt. Die Aussage „WeatherOnline verarbeitet keine personenbezogenen Daten" wäre daher rechtlich ungenau: Cloudflare verarbeitet als Auftragsverarbeiter (Art. 28 DSGVO) die vollständige IP für den Bruchteil einer Sekunde, der zur Anonymisierung technisch notwendig ist.

Korrekte Formulierung: WeatherOnline *speichert und nutzt* keine personenbezogenen Daten. Die transiente Verarbeitung durch Cloudflare dient ausschließlich der Anonymisierung und erfolgt auf Grundlage des berechtigten Interesses (Art. 6 Abs. 1 lit. f DSGVO).

---

## Anwendbare Datenschutzgesetze

| Gesetz | Land / Region | Gilt weil |
|--------|---------------|-----------|
| **DSGVO** (EU 2016/679) | EU / EWR | Nutzer in EU-Mitgliedstaaten |
| **UK GDPR** | Vereinigtes Königreich | Firmensitz UK; UK-Nutzer |
| **LGPD** (Lei 13.709/2018) | Brasilien | App im brasilianischen App Store verfügbar |
| **DPDP Act 2023** | Indien | App im indischen App Store verfügbar |
| **APPI** (改正個人情報保護法) | Japan | App im japanischen App Store verfügbar |
| **PIPA** (개인정보 보호법) | Südkorea | App im koreanischen App Store verfügbar |
| **PIPL** (个人信息保护法) | China | App in China verfügbar (eingeschränkt) |
| **CCPA / CPPA** | USA (Kalifornien) | Nutzer in Kalifornien |
| **PIPEDA** | Kanada | Kanadische Nutzer |
| **Privacy Act 1988** | Australien | Australische Nutzer |
| **POPIA** | Südafrika | Südafrikanische Nutzer |
| **DSG 2020** | Schweiz | Schweizer Nutzer |
| **KVKK** (6698 sayılı Kanun) | Türkei | Türkisch ist unterstützte App-Sprache; türkische Nutzer |
| **PDPA** (B.E. 2562) | Thailand | Thailändische Nutzer |
| **COPPA** | USA (federal) | App im US-amerikanischen App Store; Kinder-Schutzpflicht |

---

## Pflichtinhalte — Erfüllung je Rechtsordnung

### Informationspflichten (Art. 13 DSGVO-Äquivalent)

| Pflichtinhalt | DSGVO | UK GDPR | LGPD | DPDP | APPI | PIPA | Erfüllt |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Identität des Verantwortlichen | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ Abschn. 1 |
| Kontaktdaten | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ Abschn. 1+11 |
| EU-Vertreter (Art. 27) | ✓ | — | — | — | — | — | ✓ Abschn. 2 |
| UK-Vertreter (UK GDPR Art. 27) | — | ✓ | — | — | — | — | ✓ (Firmensitz = UK) |
| Zweck der Verarbeitung | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ Abschn. 4+5 |
| Rechtsgrundlage | ✓ | ✓ | ✓ | — | — | — | ✓ Abschn. 4+6 |
| Kategorien verarbeiteter Daten | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ Abschn. 3+5 |
| Empfänger / Weitergabe | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ Abschn. 5+6 |
| Drittlandtransfer | ✓ | ✓ | ✓ | — | ✓ | ✓ | ✓ Abschn. 6 |
| Speicherdauer | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ Abschn. 5+8 |
| Betroffenenrechte | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ Abschn. 9 |
| Beschwerderecht bei Behörde | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ Abschn. 10 |
| Keine Profilerstellung / Tracking | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ Abschn. 3 |

---

## Länderspezifische Besonderheiten

### Europa

| Gesetz | Besonderheit | Lösung |
|--------|-------------|--------|
| **DSGVO** Art. 27 | EU-Vertreter Pflicht (Sitz außerhalb EU) | WeatherOnline, Dr. Ulrich Römer, Bonn — Abschn. 2 |
| **DSGVO** Art. 46 | Drittlandtransfer Apple MapKit (USA) | EU-Standardvertragsklauseln — Abschn. 6 |
| **UK GDPR** | Entspricht DSGVO; Firmensitz UK = kein Vertreter nötig | Firmenadresse Leeds = UK-Anschrift |
| **DSG 2020** (CH) | Entspricht DSGVO weitgehend; EDÖB als Behörde | ✓ Abschn. 10 + Anhang |

### Amerika

| Gesetz | Besonderheit | Lösung |
|--------|-------------|--------|
| **COPPA** (USA) | Verbot der Datenerhebung von Kindern unter 13 ohne elterliche Einwilligung; FTC-Enforcement | App für alle Altersgruppen geeignet (Wetterinfo); da keine Daten erhoben werden, ist COPPA automatisch erfüllt — Abschn. 3 |
| **LGPD** (BR) | 9 Betroffenenrechte, ANPD als Behörde, Einwilligung oder Vertragserfüllung | ✓ Abschn. 9+10; keine Einwilligung nötig (Vertragserfüllung) |
| **CCPA** (CA/USA) | Recht auf Auskunft, Löschung, Opt-out aus Verkauf | Kein Verkauf von Daten; Auskunft + Löschung in Abschn. 9; CPPA in Abschn. 10 |
| **PIPEDA** (CA) | 10 Fair Information Principles | Erfüllt durch DSGVO-konformes Vorgehen |

### Asien-Pazifik

| Gesetz | Besonderheit | Lösung |
|--------|-------------|--------|
| **DPDP Act 2023** (IN) | Data Fiduciary-Pflichten; Data Protection Board | Keine personenbezogenen Daten → minimale Pflichten; Board in Abschn. 10 |
| **APPI** (JP) | Drittlandsübermittlung nur mit Einwilligung oder Angemessenheitsbeschluss | Apple (USA): Standardvertragsklauseln; keine Nutzerdaten übermittelt |
| **PIPA** (KR) | Strenge Anforderungen; Privacy Officer; koreanischsprachige Erklärung empfohlen | IP-Anonymisierung reduziert Anforderungen erheblich; PIPC in Abschn. 10 |
| **PIPL** (CN) | Nur auf personenbezogene Daten anwendbar | Anonymisierte /24-Subnetze = keine personenbezogenen Daten → PIPL nicht anwendbar |
| **PDPA** (TH) | 8 Datenschutzprinzipien; PDPC als Behörde; Einwilligung oder Vertragserfüllung | PDPC in Abschn. 10; IP-Anonymisierung → minimale Pflichten |
| **Privacy Act** (AU) | Australian Privacy Principles (APPs) | Erfüllt durch DSGVO-konformes Vorgehen; OAIC in Abschn. 10 |

### Türkei / Naher Osten / Afrika

| Gesetz | Besonderheit | Lösung |
|--------|-------------|--------|
| **KVKK** (TR) | DSGVO-ähnlich; Registrierungspflicht beim KVKK wenn personenbezogene Daten verarbeitet; Türkisch als unterstützte App-Sprache | IP-Anonymisierung → minimale Pflichten; KVKK in Abschn. 9+10+Anhang |
| **POPIA** (ZA) | 8 Verarbeitungsbedingungen; Information Regulator | Keine personenbezogenen Daten → minimale Pflichten; Regulator in Abschn. 10 |
| **PDPL** (VAE) | UAE Data Office; Verarbeitungsregister | UAE Data Office in Abschn. 10 |
| **Privacy Protection Law** (IL) | Privacy Protection Authority | PPA in Abschn. 10 |

---

## Rechtsgrundlagen der Verarbeitung (Art. 6 DSGVO)

| Verarbeitung | Rechtsgrundlage | Abschnitt |
|---|---|---|
| Wetterdaten-API (Koordinaten, Ort-ID) | Art. 6 Abs. 1 lit. b — Vertragserfüllung | 4 |
| Apple MapKit (Kartendarstellung) | Art. 6 Abs. 1 lit. f — berechtigtes Interesse | 6 |
| IP-Anonymisierung (Cloudflare als AVV, transient) | Art. 6 Abs. 1 lit. f — berechtigtes Interesse (datenschutzfreundliche Infrastruktur) | 5 |
| Server-Logs (nach Anonymisierung gespeichert) | Kein Personenbezug → DSGVO nicht anwendbar | 5 |

---

## Drittland-Transfers

| Empfänger | Land | Übertragungsgrundlage |
|---|---|---|
| Apple MapKit | USA | EU-Standardvertragsklauseln (Art. 46 Abs. 2 lit. c DSGVO) |
| OpenStreetMap / OSMF (nur Android) | UK | Angemessenheitsbeschluss (Art. 45 DSGVO) |
| Cloudflare (IP-Anonymisierung, AVV) | USA (Edge) | Art. 28 DSGVO (AVV); Standardvertragsklauseln (Art. 46 Abs. 2 lit. c); vollständige IP nur transient für Anonymisierungsvorgang |

---

## Betroffenenrechte — Überblick

| Recht | DSGVO | UK GDPR | LGPD | DPDP | CCPA | KVKK | PDPA | Abschn. |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Auskunft | Art. 15 | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | 9 |
| Berichtigung | Art. 16 | ✓ | ✓ | ✓ | — | ✓ | ✓ | 9 |
| Löschung | Art. 17 | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | 9 |
| Einschränkung | Art. 18 | ✓ | — | — | — | ✓ | ✓ | 9 |
| Datenübertragbarkeit | Art. 20 | ✓ | ✓ | — | — | — | ✓ | 9 |
| Widerspruch | Art. 21 | ✓ | — | — | — | ✓ | ✓ | 9 |
| Opt-out Datenverkauf | — | — | — | — | ✓ | — | — | 9 (n/a) |

*Hinweis: Da WeatherOnline keine personenbezogenen Daten erhebt und speichert, sind die meisten Betroffenenrechte faktisch gegenstandslos. Sie werden dennoch vollständig aufgeführt, da dies gesetzlich gefordert wird.*

---

## Lokalisierung

Die Datenschutzerklärung muss nach folgenden Gesetzen in der Landessprache verfügbar sein:

| Land | Gesetz | Sprache | Status |
|---|---|---|---|
| Deutschland / AT / CH | DSGVO / DSG | Deutsch | ✓ vorhanden |
| Südkorea | PIPA | Koreanisch | geplant |
| Türkei | KVKK | Türkisch | ✓ vorhanden (App-Sprache tr) |
| China | PIPL | Chinesisch (Mandarin) | PIPL n/a (IP-Anonymisierung) |
| Japan | APPI | Japanisch | empfohlen |
| Brasilien | LGPD | Portugiesisch | empfohlen |
| Thailand | PDPA | Thailändisch | empfohlen |
| Alle anderen | — | Englisch ausreichend | ✓ geplant |

---

*Stand: September 2026 — WeatherOnline iOS/Android*
