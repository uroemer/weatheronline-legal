# Technisch-Organisatorische Maßnahmen (Art. 32 DSGVO)

**Verantwortlicher:** WeatherOnline Meteorological Services Ltd  
**Stand:** September 2026  
**Hinweis:** Internes Dokument — nicht öffentlich, aber auf Anfrage von Aufsichtsbehörden vorzulegen.

---

## 1. Vertraulichkeit (Art. 32 Abs. 1 lit. b)

| Maßnahme | Umsetzung |
|----------|-----------|
| **Transportverschlüsselung** | HTTPS / TLS 1.2+ für alle API-Verbindungen zwischen App und Server |
| **IP-Anonymisierung** | Cloudflare Worker anonymisiert IP auf /24 (IPv4) bzw. /48 (IPv6) vor Serverspeicherung — keine vollständige IP wird je gespeichert |
| **Datensparsamkeit (Privacy by Design)** | Kein Nutzerkonto, keine Registrierung, kein Tracking, keine Werbeprofile — minimale Datenerhebung by design |
| **Lokale Datenspeicherung** | Gespeicherte Orte nur auf Gerät; kein Cloud-Sync, keine serverseitige Nutzerdatenbank |
| **Kein GPS-Zugriff** | App fordert keine Gerätelocation an; nur manuell gewählte Orte werden verwendet |

---

## 2. Integrität (Art. 32 Abs. 1 lit. b)

| Maßnahme | Umsetzung |
|----------|-----------|
| **HMAC-Signatur** | App signiert alle API-Requests mit HMAC-SHA256 (X-WO-Timestamp + X-WO-Signature) — serverseitige Prüfung in Vorbereitung |
| **Timestamp-Validierung** | Schutz vor Replay-Attacks durch Timestamp-Prüfung (±5 Minuten) — aktivierbar im Cloudflare Worker |
| **Cloudflare WAF** | Geplant: Blockierung von Requests ohne WeatherOnline User-Agent |
| **Certificate Validation** | Standard iOS/Android TLS-Zertifikat-Validierung |

---

## 3. Verfügbarkeit (Art. 32 Abs. 1 lit. b)

| Maßnahme | Umsetzung |
|----------|-----------|
| **Cloudflare CDN** | Anfragen laufen über Cloudflare-Edge-Netzwerk → DDoS-Schutz, globale Redundanz |
| **Graceful Degradation** | App zeigt bei Netzwerkfehler gecachte Daten oder Fehlermeldung; kein Absturz |
| **Response-Caching** | API-Antworten werden clientseitig gecacht (TTL je nach Endpunkt) |
| **Timeout-Handling** | API-Requests mit 12-Sekunden-Timeout |

---

## 4. Belastbarkeit (Art. 32 Abs. 1 lit. b)

| Maßnahme | Umsetzung |
|----------|-----------|
| **Cloudflare Proxy** | Serveradresse nicht öffentlich — Schutz vor direkten Angriffen |
| **IP-Anonymisierung** | Reduziert Datenschutzrisiko bei Sicherheitsvorfällen erheblich — kein personenbezogenes Log-Material |
| **Kein Single Point of Failure** | Cloudflare-Worker läuft verteilt auf Edge-Knoten weltweit |

---

## 5. Wiederherstellung (Art. 32 Abs. 1 lit. c)

| Maßnahme | Umsetzung |
|----------|-----------|
| **Lokale Daten** | Nutzer kann Favoriten durch Deinstallation vollständig löschen |
| **Server-Logs** | Anonymisierte Logs — keine personenbezogenen Daten, daher kein Wiederherstellungsbedarf für Betroffene |
| **Worker-Code** | Cloudflare Worker in Git versioniert (`iphone/cloudflare-worker-ip-anonymization.js`) |

---

## 6. Überprüfbarkeit (Art. 32 Abs. 1 lit. d)

| Maßnahme | Umsetzung |
|----------|-----------|
| **Dokumentation** | Verarbeitungsverzeichnis (Art. 30), TOMs, Datenschutzerklärung, Cloudflare-Worker-Deployment in Git |
| **Code-Versionierung** | Gesamter App-Code und Worker in GitHub (`uroemer/smartphone`) |
| **Audit Trail** | Cloudflare Dashboard zeigt Worker-Deployment-Historie |

---

## 7. Organisatorische Maßnahmen

| Maßnahme | Umsetzung |
|----------|-----------|
| **Verantwortlicher** | Dr. Ulrich Römer — verantwortlich für Datenschutz und technische Umsetzung |
| **EU-Vertreter** | WeatherOnline, Dr. Ulrich Römer, Bonn (Art. 27 DSGVO) |
| **Datenschutzkontakt** | privacy@weatheronline.co.uk |
| **Drittanbieter-Verträge** | Apple (MapKit): Standardvertragsklauseln; Cloudflare: DPA vorhanden |
| **Privacy by Default** | Alle Datenschutz-freundlichen Einstellungen sind Standard — kein Opt-out erforderlich |

---

*Stand: September 2026*
