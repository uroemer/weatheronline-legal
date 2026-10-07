<?php
/*
 * Auswertung des Widerrufsformulars (vertrag-widerrufen.html).
 * Stand 07.10.2026, Fassung 3 (Data Minimization: CSV ohne Personendaten,
 * Kunden-E-Mail auf gesetzliches Minimum nach § 356a BGB reduziert).
 *
 * Anforderungen § 356a BGB:
 *   - Eingangsbestätigung sofort auf dauerhaftem Datenträger (E-Mail)
 *   - mit Inhalt der Erklärung sowie Datum und Uhrzeit des Eingangs
 *
 * Ablauf: Eingaben prüfen → Missbrauchsprüfung → Zeitstempel → Protokoll
 *         → E-Mail an Kunden und Bearbeiter → Bestätigungsseite.
 * Grundsatz: Ein echter Widerruf wird NIE verworfen.
 */

declare(strict_types=1);
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/London');

// ---- Einstellungen -------------------------------------------------------
const MEMBER_MAIL       = 'member@weatheronline.co.uk';
const ABSENDER          = 'WeatherOnline <member@weatheronline.co.uk>';
const DATENVERZ         = __DIR__ . '/../widerruf-protokoll';
const PROTOKOLL         = DATENVERZ . '/widerrufe.csv';
const ZAEHLER           = DATENVERZ . '/zaehler.json';
const IP_SALZ           = 'BITTE-ERSETZEN-geheimer-zufallswert';
const MAX_JE_IP         = 3;
const FENSTER_SEK       = 3600;
const ALARM_GESAMT      = 20;
const MIN_DAUER_MS      = 3000;
const HINTER_CLOUDFLARE = true;

const FIRMA = "WeatherOnline Limited · Brookfield Court, Selby Road, Garforth, Leeds, LS25 1NB, England\n"
            . "Registernummer 04619915 (England and Wales) · member@weatheronline.co.uk";

// ---- Hilfsfunktionen -----------------------------------------------------
function feld(string $name, int $max): string {
    $v = isset($_POST[$name]) && is_string($_POST[$name]) ? trim($_POST[$name]) : '';
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    return mb_substr($v, 0, $max);
}
function einzeilig(string $v): string {
    return trim(preg_replace('/[\r\n\t]+/', ' ', $v));
}
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// M1(b): Links aus Freitext entfernen (nur für Kunden-E-Mail relevant)
function ohneLinks(string $v): string {
    return preg_replace('~\b(?:https?://|ftp://|www\.)\S+|\b[\w.-]+\.(?:com|net|org|info|biz|ru|cn|xyz|top|de|co\.uk|uk|io)\b(?:/\S*)?~iu',
                        '[Link entfernt]', $v);
}

function clientIp(): string {
    if (HINTER_CLOUDFLARE && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return (string)$_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    return (string)($_SERVER['REMOTE_ADDR'] ?? '');
}

/*
 * M1(a)/M4: Zählt Absendungen je IP-Hash und insgesamt.
 * Kein Klartext der IP-Adresse gespeichert; Einträge nach FENSTER_SEK gelöscht.
 */
function zaehle(int $jetzt): array {
    if (!is_dir(DATENVERZ)) { @mkdir(DATENVERZ, 0750, true); }
    $schluessel = substr(hash('sha256', IP_SALZ . '|' . clientIp()), 0, 16);
    $fp = @fopen(ZAEHLER, 'c+');
    if (!$fp) { return ['je_ip' => 0, 'gesamt' => 0, 'alarm_faellig' => false]; }
    flock($fp, LOCK_EX);
    $daten = json_decode((string)stream_get_contents($fp), true);
    if (!is_array($daten)) { $daten = ['ip' => [], 'alle' => [], 'alarm' => 0]; }
    $grenze = $jetzt - FENSTER_SEK;
    foreach ($daten['ip'] as $k => $zeiten) {
        $daten['ip'][$k] = array_values(array_filter($zeiten, fn($t) => $t > $grenze));
        if (!$daten['ip'][$k]) { unset($daten['ip'][$k]); }
    }
    $daten['alle'] = array_values(array_filter($daten['alle'], fn($t) => $t > $grenze));
    $daten['ip'][$schluessel][] = $jetzt;
    $daten['alle'][] = $jetzt;
    $jeIp   = count($daten['ip'][$schluessel]);
    $gesamt = count($daten['alle']);
    $alarm  = false;
    if ($gesamt >= ALARM_GESAMT && ($daten['alarm'] ?? 0) <= $grenze) {
        $alarm = true;
        $daten['alarm'] = $jetzt;
    }
    ftruncate($fp, 0); rewind($fp);
    fwrite($fp, json_encode($daten));
    flock($fp, LOCK_UN); fclose($fp);
    return ['je_ip' => $jeIp, 'gesamt' => $gesamt, 'alarm_faellig' => $alarm];
}

function sende(string $an, string $betreff, string $text, string $antwortAn): bool {
    $kopf = "From: " . ABSENDER . "\r\n"
          . "Reply-To: $antwortAn\r\n"
          . "MIME-Version: 1.0\r\n"
          . "Content-Type: text/plain; charset=UTF-8\r\n"
          . "Content-Transfer-Encoding: 8bit";
    return mail($an, mb_encode_mimeheader($betreff, 'UTF-8'), $text, $kopf);
}

function seite(string $titel, string $inhalt, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . h($titel) . ' – WeatherOnline</title>'
       . '<style>body{margin:0;font-family:Arial,Helvetica,sans-serif;line-height:1.5;background:#f3f6f9;color:#1a1a1a}'
       . 'main{max-width:640px;margin:24px auto;padding:24px 20px;background:#fff;border:1px solid #c9d3de;border-radius:6px}'
       . 'h1{color:#174a7c;font-size:24px;margin-top:0}a{color:#1f5f9e}dl{margin:0}dt{font-weight:bold;margin-top:8px}dd{margin:0}</style>'
       . '</head><body><main><h1>' . h($titel) . '</h1>' . $inhalt . '</main></body></html>';
    exit;
}

// ---- Nur POST ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vertrag-widerrufen.html', true, 303);
    exit;
}

// ---- Fallenfeld (einfache Bots) ------------------------------------------
if (feld('website', 200) !== '') {
    seite('Widerruf eingegangen', '<p>Vielen Dank.</p>');
}

$name       = einzeilig(feld('name', 120));
$vertrag    = einzeilig(feld('vertrag', 120));
$produkt    = einzeilig(feld('produkt', 200));
$email      = einzeilig(feld('email', 200));
$mitteilung = feld('mitteilung', 2000);
$dauerRoh   = feld('dauer', 12);

// ---- Pflichtfelder prüfen ------------------------------------------------
$fehler = [];
if ($name === '')    { $fehler[] = 'Bitte geben Sie Ihren Namen an.'; }
if ($vertrag === '') { $fehler[] = 'Bitte geben Sie Ihren Benutzernamen oder die Rechnungsnummer an.'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $fehler[] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.'; }

if ($fehler) {
    $liste = '<ul><li>' . implode('</li><li>', array_map('h', $fehler)) . '</li></ul>';
    seite('Angaben unvollständig',
        '<p>Der Widerruf konnte noch nicht abgesendet werden:</p>' . $liste
        . '<p><a href="vertrag-widerrufen.html">Zurück zum Formular</a></p>', 422);
}

// ---- Zeitstempel und Vorgangsnummer --------------------------------------
$jetzt   = new DateTimeImmutable('now');
$datum   = $jetzt->format('d.m.Y');
$uhrzeit = $jetzt->format('H:i:s') . ' Uhr (' . $jetzt->format('T') . ')';
$vorgang = 'W-' . $jetzt->format('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

// ---- Missbrauchsprüfung (M1, M4) -----------------------------------------
$merkmale = [];
$zaehler  = zaehle($jetzt->getTimestamp());
if ($zaehler['je_ip'] > MAX_JE_IP) {
    $merkmale[] = 'Begrenzung: ' . $zaehler['je_ip'] . ' Absendungen von derselben IP in 60 Minuten';
}
if ($dauerRoh !== '' && ctype_digit($dauerRoh) && (int)$dauerRoh < MIN_DAUER_MS) {
    $merkmale[] = 'Formular in ' . (int)$dauerRoh . ' ms ausgefüllt (unter ' . MIN_DAUER_MS . ' ms)';
}
$auffaellig = (bool)$merkmale;
if (ohneLinks($mitteilung) !== $mitteilung) { $merkmale[] = 'Mitteilung enthielt Links'; }

// ---- Protokoll: nur Vorgangsnummer, Timestamp, Typ, Status ---------------
// Keine personenbezogenen Daten in der CSV (Art. 5 Abs. 1 lit. c DSGVO).
// Vollständige Daten ausschließlich in der E-Mail an member@weatheronline.co.uk.
if (!is_dir(DATENVERZ)) { @mkdir(DATENVERZ, 0750, true); }
$fp = @fopen(PROTOKOLL, 'ab');
if ($fp) {
    flock($fp, LOCK_EX);
    fputcsv($fp, [
        $vorgang,
        $jetzt->format(DATE_ATOM),
        'widerruf',
        $auffaellig ? 'auffaellig' : 'normal',
    ], ';');
    flock($fp, LOCK_UN);
    fclose($fp);
}

// ---- E-Mail an Kunden: gesetzliches Minimum § 356a BGB -------------------
// Erforderlich: Inhalt der Erklärung, Datum und Uhrzeit des Eingangs.
$textKunde = "Widerruf eingegangen am $datum um $uhrzeit.\n"
    . "Vorgangsnummer: $vorgang\n\n"
    . "Vertragskennung: $vertrag\n"
    . ($produkt !== '' ? "Vertrag: $produkt\n" : '')
    . "\nSoweit das Widerrufsrecht besteht, erstatten wir Ihre Zahlung innerhalb von 14 Tagen "
    . "über das ursprünglich verwendete Zahlungsmittel. "
    . "Ein laufendes PayPal-Abonnement wird beendet und der Zugang gesperrt.\n\n"
    . "Falls Sie diesen Widerruf nicht selbst abgesendet haben, antworten Sie bitte auf diese E-Mail.\n\n"
    . "Mit freundlichen Grüßen\nWeatherOnline\n\n" . FIRMA . "\n";

// ---- Interne Meldung: vollständige Daten für Bearbeitung -----------------
$textIntern = ($auffaellig
        ? "ACHTUNG – AUFFÄLLIGER EINGANG, KEINE AUTOMATISCHE KUNDEN-E-MAIL VERSANDT\n"
        . implode("\n", $merkmale) . "\n"
        . "Bitte prüfen: echter Widerruf? Dann Eingangsbestätigung von Hand an die Konto-E-Mail-Adresse senden.\n\n"
        : ($merkmale ? "Hinweis: " . implode(' | ', $merkmale) . "\n\n" : ''))
    . "Neuer Widerruf – $datum, $uhrzeit\n"
    . "Vorgangsnummer: $vorgang\n\n"
    . "Name:             $name\n"
    . "Vertragskennung:  $vertrag\n"
    . ($produkt !== '' ? "Vertrag:          $produkt\n" : '')
    . "E-Mail:           $email\n"
    . ($mitteilung !== '' ? "Mitteilung:       $mitteilung\n" : '')
    . "\nZu erledigen:\n"
    . "1. Vertrag suchen; E-Mail-Adresse mit der im Konto vergleichen.\n"
    . "   Weicht sie ab: vor Sperre und Erstattung über die Konto-Adresse nachfragen.\n"
    . "2. Widerrufsrecht prüfen (Frist, Checkbox, Vertragsbestätigung).\n"
    . "3. Zugang sperren, PayPal-Abonnement beenden, Erstattung veranlassen.\n";

// ---- Versand -------------------------------------------------------------
$okKunde = false;
if (!$auffaellig) {
    $okKunde = sende($email, "Eingangsbestätigung Ihres Widerrufs ($vorgang)", $textKunde, MEMBER_MAIL);
}
sende(MEMBER_MAIL, ($auffaellig ? '[PRÜFEN] ' : '') . "Widerruf $vorgang", $textIntern, $email);

if ($zaehler['alarm_faellig']) {
    sende(MEMBER_MAIL, 'ALARM Widerrufsformular: ungewöhnlich viele Absendungen',
        "In den letzten 60 Minuten: " . $zaehler['gesamt'] . " Absendungen.\n"
        . "Bitte Protokoll prüfen und ggf. Cloudflare-Regel verschärfen.\n", MEMBER_MAIL);
}

// ---- Bestätigungsseite ---------------------------------------------------
if ($auffaellig) {
    $hinweisMail = '<p>Ihr Widerruf wurde gespeichert und weitergeleitet. '
        . 'Die automatische Bestätigung konnte wegen ungewöhnlich vieler Anfragen nicht versandt werden; '
        . 'Sie erhalten sie nach Prüfung von uns. Bitte bewahren Sie diese Seite auf '
        . 'oder wenden Sie sich an <a href="mailto:' . MEMBER_MAIL . '">' . MEMBER_MAIL . '</a>.</p>';
} elseif ($okKunde) {
    $hinweisMail = '<p>Eine Bestätigung wurde an <strong>' . h($email) . '</strong> gesendet.</p>';
} else {
    $hinweisMail = '<p><strong>Hinweis:</strong> Die Bestätigungs-E-Mail konnte nicht versandt werden. '
        . 'Ihr Widerruf ist trotzdem eingegangen. Bitte bewahren Sie diese Seite auf '
        . 'oder wenden Sie sich an <a href="mailto:' . MEMBER_MAIL . '">' . MEMBER_MAIL . '</a>.</p>';
}

seite('Widerruf eingegangen',
    '<p>Ihr Widerruf ist am <strong>' . h($datum) . '</strong> um <strong>' . h($uhrzeit) . '</strong> eingegangen.</p>'
    . $hinweisMail
    . '<dl>'
    . '<dt>Vorgangsnummer</dt><dd>' . h($vorgang) . '</dd>'
    . '<dt>Vertragskennung</dt><dd>' . h($vertrag) . '</dd>'
    . ($produkt !== '' ? '<dt>Vertrag</dt><dd>' . h($produkt) . '</dd>' : '')
    . '</dl>'
    . '<p><a href="https://www.weatheronline.de/">Zur Startseite</a></p>');
