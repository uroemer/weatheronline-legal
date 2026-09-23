# Verzeichnis von Verarbeitungstätigkeiten (Art. 30 DSGVO)

**Verantwortlicher:** WeatherOnline Meteorological Services Ltd, Garforth, Leeds, LS25 1NB, England  
**Stand:** September 2026  
**Hinweis:** Internes Dokument — nicht öffentlich, aber auf Anfrage von Aufsichtsbehörden vorzulegen.

---

## 1. Wetterdaten-API-Abruf

| Feld | Inhalt |
|------|--------|
| **Zweck** | Bereitstellung von Wetterdaten für den in der App gewählten Ort |
| **Rechtsgrundlage** | Art. 6 Abs. 1 lit. b DSGVO — Vertragserfüllung |
| **Kategorien betroffener Personen** | App-Nutzer |
| **Kategorien verarbeiteter Daten** | WMO-Kennung und geografische Koordinaten des gewählten Ortes (kein Gerätesstandort, kein Personenbezug) |
| **Empfänger** | WeatherOnline-Server (Leeds, England, UK) |
| **Drittlandtransfer** | Nein (UK: Angemessenheitsbeschluss) |
| **Speicherdauer** | Keine Speicherung — flüchtige Verarbeitung pro Anfrage |
| **Technische Maßnahmen** | HTTPS/TLS, HMAC-Signatur (vorbereitet) |

---

## 2. Server-Log-Verarbeitung

| Feld | Inhalt |
|------|--------|
| **Zweck** | Qualitätskontrolle, Fehleranalyse, API-Stabilitätsmessung |
| **Rechtsgrundlage** | Kein Personenbezug (anonymisierte Daten) — DSGVO nicht anwendbar |
| **Kategorien betroffener Personen** | Keine (Daten sind nach Anonymisierung nicht personenbezogen) |
| **Kategorien verarbeiteter Daten** | Anonymisiertes IP-Subnetz (/24), Herkunftsland, Zeitstempel, API-Pfad, HTTP-Statuscode, Datenmenge, Antwortzeit, App-Name/Version/OS |
| **Empfänger** | Interne IT von WeatherOnline |
| **Drittlandtransfer** | Nein |
| **Speicherdauer** | Mehrere Jahre |
| **Technische Maßnahmen** | Cloudflare Worker anonymisiert IP vor Erreichen des Servers; vollständige IP wird nie gespeichert |

---

## 3. Apple MapKit (iOS)

| Feld | Inhalt |
|------|--------|
| **Zweck** | Darstellung interaktiver Radarkarten |
| **Rechtsgrundlage** | Art. 6 Abs. 1 lit. f DSGVO — berechtigtes Interesse (Bereitstellung einer funktionalen Wetterkarte) |
| **Kategorien betroffener Personen** | iOS-App-Nutzer |
| **Kategorien verarbeiteter Daten** | Kartenkachel-Anfragen inkl. IP-Adresse (durch Apple verarbeitet, nicht durch WeatherOnline) |
| **Empfänger** | Apple Inc., USA |
| **Drittlandtransfer** | Ja — USA; Grundlage: EU-Standardvertragsklauseln (Art. 46 Abs. 2 lit. c DSGVO) |
| **Speicherdauer** | Wird durch Apple bestimmt; WeatherOnline hat keinen Einfluss |
| **Technische Maßnahmen** | Nur im Kontext der Radaransicht; keine Weitergabe von Nutzerdaten durch WeatherOnline |

---

## 4. OpenStreetMap / OSMF (Android)

| Feld | Inhalt |
|------|--------|
| **Zweck** | Darstellung interaktiver Radarkarten auf Android |
| **Rechtsgrundlage** | Art. 6 Abs. 1 lit. f DSGVO — berechtigtes Interesse |
| **Kategorien betroffener Personen** | Android-App-Nutzer |
| **Kategorien verarbeiteter Daten** | Kartenkachel-Anfragen inkl. IP-Adresse (durch OSMF verarbeitet) |
| **Empfänger** | OpenStreetMap Foundation (OSMF), Großbritannien |
| **Drittlandtransfer** | Nein (UK: Angemessenheitsbeschluss Art. 45 DSGVO) |
| **Speicherdauer** | Wird durch OSMF bestimmt |
| **Technische Maßnahmen** | Standard-HTTPS |

---

## 5. Lokale App-Datenspeicherung

| Feld | Inhalt |
|------|--------|
| **Zweck** | Speicherung von Favoriten-Orten und App-Einstellungen |
| **Rechtsgrundlage** | Art. 6 Abs. 1 lit. b DSGVO — Vertragserfüllung |
| **Kategorien betroffener Personen** | App-Nutzer |
| **Kategorien verarbeiteter Daten** | Vom Nutzer gespeicherte Ortsnamen, WMO-Kennungen, Koordinaten; App-Einstellungen |
| **Empfänger** | Keine — ausschließlich lokale Gerätespeicherung |
| **Drittlandtransfer** | Nein |
| **Speicherdauer** | Bis zur Deinstallation der App oder manuellen Löschung durch den Nutzer |
| **Technische Maßnahmen** | iOS Keychain / Android SharedPreferences (geräteseitige Verschlüsselung) |

---

*Stand: September 2026*
