# WeatherOnline — Legal Repository

Arbeitsverzeichnis: `/Users/ulrich.roemermac.com/legal`

## Was ist dieses Repo?

Zentrale Rechtsdokumente für alle WeatherOnline-Plattformen.  
**Eine Quelle der Wahrheit** — iOS, Android und Website beziehen sich alle hierauf.

GitHub: `github.com/uroemer/weatheronline-legal`

## Dateien

| Datei | Zweck | Öffentlich |
|---|---|---|
| `Datenschutzerklaerung.md` | Datenschutzerklärung (iOS + Android + Web) | Ja |
| `Impressum.md` | Impressum (iOS + Android + Web) | Ja |
| `Datenschutz-Rechtsgrundlagen.md` | Rechtliche Grundlagen Datenschutz | Intern |
| `Impressum-Rechtsgrundlagen.md` | Rechtliche Grundlagen Impressum | Intern |
| `Datenschutzbehoerden_Europa.md` | Behörden weltweit | Intern |
| `Verarbeitungsverzeichnis-Art30.md` | Art. 30 DSGVO — Verarbeitungsverzeichnis | Intern |
| `TOMs-Art32.md` | Art. 32 DSGVO — Technisch-org. Maßnahmen | Intern |

## Verantwortlicher

WeatherOnline Meteorological Services Ltd  
Brookfield Court Selby Road, Garforth, Leeds, LS25 1NB, England  
E-Mail: contact@weatheronline.co.uk / privacy@weatheronline.co.uk  
EU-Vertreter: WeatherOnline, Dr. Ulrich Römer, Bonn, Deutschland

## Nach Änderungen

Wenn Texte hier geändert werden, müssen folgende Plattformen aktualisiert werden:

### iOS
- Neue Session in `/Users/ulrich.roemermac.com/smartphone/iphone`
- `PrivacyView.swift` mit aktuellem Text aus `Datenschutzerklaerung.md` aktualisieren
- `AboutView.swift` bei Impressum-Änderungen
- Neues Build erstellen und auf TestFlight hochladen

### Android
- Neue Session in `/Users/ulrich.roemermac.com/smartphone/android`
- Entsprechende Views mit aktuellem Text aktualisieren

### Website
- Datenschutzerklärung unter `weatheronline.co.uk/datenschutz` veröffentlichen
- Impressum unter `weatheronline.co.uk/impressum`

## Rechtlicher Rahmen

Die Dokumente decken ab: DSGVO, UK GDPR, LGPD (BR), DPDP Act (IN),
KVKK (TR), PDPA (TH), CCPA (US), COPPA (US), APPI (JP), PIPA (KR),
PIPL (CN), PIPEDA (CA), POPIA (ZA), DSG (CH).

Besonderheit: IP-Anonymisierung via Cloudflare Worker (/24 IPv4, /48 IPv6)
— daher keine personenbezogenen Daten in Server-Logs → vereinfachter Datenschutz.

## Git / Commits

```bash
git add <datei>
git commit -m "Legal: <was geändert und warum>"
git push
```
