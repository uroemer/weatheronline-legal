<?php
/*
 * Auswertung des Kündigungsformulars (vertraege-kuendigen.html).
 * Stand 07.10.2026, Fassung 2 (Data Minimization: CSV ohne Personendaten,
 * Kunden-E-Mail auf gesetzliches Minimum nach § 312k BGB reduziert).
 *
 * Anforderungen § 312k BGB:
 *   - Bestätigung sofort auf dauerhaftem Datenträger (E-Mail):
 *     Inhalt, Datum und Uhrzeit des Eingangs, Zeitpunkt der Vertragsbeendigung
 *
 * Ablauf: Eingaben prüfen → Missbrauchsprüfung → Zeitstempel → Protokoll
 *         → E-Mail an Kunden und Bearbeiter → Bestätigungsseite.
 * Grundsatz: Eine echte Kündigung wird NIE verworfen.
 */

declare(strict_types=1);
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/London');

// ---- Einstellungen -------------------------------------------------------
const MEMBER_MAIL       = 'member@weatheronline.co.uk';
const ABSENDER          = 'WeatherOnline <member@weatheronline.co.uk>';
const DATENVERZ         = __DIR__ . '/../kuendigung-protokoll';
const PROTOKOLL         = DATENVERZ . '/kuendigungen.csv';
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

// M1(b): Links aus Freitext entfernen (für Kunden-E-Mail)
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
    header('Location: vertraege-kuendigen.html', true, 303);
    exit;
}

// ---- Fallenfeld (einfache Bots) ------------------------------------------
if (feld('website', 200) !== '') {
    seite('Kündigung eingegangen', '<p>Vielen Dank.</p>');
}

$name       = einzeilig(feld('name', 120));
$vertrag    = einzeilig(feld('vertrag', 120));
$produkt    = einzeilig(feld('produkt', 200));
$email      = einzeilig(feld('email', 200));
$mitteilung = feld('mitteilung', 2000);
$dauerRoh   = feld('dauer', 12);
$art        = feld('art', 20) === 'ausserordentlich' ? 'ausserordentlich' : 'ordentlich';
$grund      = feld('grund', 2000);
$zeitpunkt  = feld('zeitpunkt', 20) === 'datum' ? 'datum' : 'naechstmoeglich';
$datumRoh   = feld('datum', 10);

// ---- Pflichtfelder prüfen ------------------------------------------------
$fehler = [];
if ($name === '')    { $fehler[] = 'Bitte geben Sie Ihren Namen an.'; }
if ($vertrag === '') { $fehler[] = 'Bitte geben Sie Ihren Benutzernamen oder die Rechnungsnummer an.'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $fehler[] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.'; }
if ($art === 'ausserordentlich' && trim($grund) === '') {
    $fehler[] = 'Bitte geben Sie den Grund der außerordentlichen Kündigung an.';
}
$wunschDatum = null;
if ($zeitpunkt === 'datum') {
    $d     = DateTimeImmutable::createFromFormat('!Y-m-d', $datumRoh);
    $heute = new DateTimeImmutable('today');
    if (!$d || $d->format('Y-m-d') !== $datumRoh) {
        $fehler[] = 'Bitte geben Sie ein gültiges Datum an oder wählen Sie „zum nächstmöglichen Zeitpunkt".';
    } elseif ($d < $heute || $d > $heute->modify('+3 years')) {
        $fehler[] = 'Das gewünschte Datum muss zwischen heute und in drei Jahren liegen.';
    } else {
        $wunschDatum = $d;
    }
}

if ($fehler) {
    $liste = '<ul><li>' . implode('</li><li>', array_map('h', $fehler)) . '</li></ul>';
    seite('Angaben unvollständig',
        '<p>Die Kündigung konnte noch nicht abgesendet werden:</p>' . $liste
        . '<p><a href="vertraege-kuendigen.html">Zurück zum Formular</a></p>', 422);
}

// ---- Zeitstempel und Vorgangsnummer --------------------------------------
$jetzt   = new DateTimeImmutable('now');
$datum   = $jetzt->format('d.m.Y');
$uhrzeit = $jetzt->format('H:i:s') . ' Uhr (' . $jetzt->format('T') . ')';
$vorgang = 'K-' . $jetzt->format('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

$artText  = $art === 'ausserordentlich'
    ? 'außerordentliche (fristlose) Kündigung aus wichtigem Grund'
    : 'ordentliche Kündigung';
$endeText = $wunschDatum
    ? 'zum ' . $wunschDatum->format('d.m.Y')
    : ($art === 'ausserordentlich' ? 'mit sofortiger Wirkung' : 'zum nächstmöglichen Zeitpunkt');

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

// ---- Protokoll: nur Vorgangsnummer, Timestamp, Typ, Art, Zeitpunkt, Status
// Keine personenbezogenen Daten in der CSV (Art. 5 Abs. 1 lit. c DSGVO).
// Vollständige Daten ausschließlich in der E-Mail an member@weatheronline.co.uk.
if (!is_dir(DATENVERZ)) { @mkdir(DATENVERZ, 0750, true); }
$fp = @fopen(PROTOKOLL, 'ab');
if ($fp) {
    flock($fp, LOCK_EX);
    fputcsv($fp, [
        $vorgang,
        $jetzt->format(DATE_ATOM),
        'kuendigung',
        $art,
        $zeitpunkt === 'datum' ? $datumRoh : 'naechstmoeglich',
        $auffaellig ? 'auffaellig' : 'normal',
    ], ';');
    flock($fp, LOCK_UN);
    fclose($fp);
}

// ---- E-Mail an Kunden: gesetzliches Minimum § 312k BGB -------------------
// Erforderlich: Inhalt (Art, Zeitpunkt, Vertragskennung), Datum und Uhrzeit.
$textKunde = "Kündigung eingegangen am $datum um $uhrzeit.\n"
    . "Vorgangsnummer: $vorgang\n\n"
    . "Art der Kündigung: $artText\n"
    . ($art === 'ausserordentlich' ? "Grund: " . ohneLinks($grund) . "\n" : '')
    . "Gewünschtes Vertragsende: $endeText\n"
    . "Vertragskennung: $vertrag\n"
    . ($produkt !== '' ? "Vertrag: $produkt\n" : '')
    . "\nDas genaue Datum des Vertragsendes teilen wir Ihnen nach Prüfung gesondert mit. "
    . "Bis dahin bleibt Ihr Zugang bestehen; ein PayPal-Abonnement wird zum selben Zeitpunkt beendet.\n\n"
    . "Falls Sie diese Kündigung nicht selbst abgesendet haben, antworten Sie bitte auf diese E-Mail.\n\n"
    . "Mit freundlichen Grüßen\nWeatherOnline\n\n" . FIRMA . "\n";

// ---- Interne Meldung: vollständige Daten für Bearbeitung -----------------
$textIntern = ($auffaellig
        ? "ACHTUNG – AUFFÄLLIGER EINGANG, KEINE AUTOMATISCHE KUNDEN-E-MAIL VERSANDT\n"
        . implode("\n", $merkmale) . "\n"
        . "Bitte prüfen: echte Kündigung? Dann Bestätigung von Hand senden.\n\n"
        : ($merkmale ? "Hinweis: " . implode(' | ', $merkmale) . "\n\n" : ''))
    . "Neue Kündigung – $datum, $uhrzeit\n"
    . "Vorgangsnummer: $vorgang\n\n"
    . "Art:              $artText\n"
    . ($art === 'ausserordentlich' ? "Grund:            $grund\n" : '')
    . "Gewünschtes Ende: $endeText\n"
    . "Name:             $name\n"
    . "Vertragskennung:  $vertrag\n"
    . ($produkt !== '' ? "Vertrag:          $produkt\n" : '')
    . "E-Mail:           $email\n"
    . ($mitteilung !== '' ? "Mitteilung:       $mitteilung\n" : '')
    . "\nZu erledigen:\n"
    . "1. Vertrag suchen; E-Mail-Adresse mit der im Konto vergleichen.\n"
    . "   Weicht sie ab: vor Umsetzung über die Konto-Adresse nachfragen.\n"
    . "2. Vertragsende bestimmen (Laufzeitende; DE nach erstem Jahr: Monatsfrist; außerordentlich: Grund prüfen).\n"
    . "3. Kündigung in aMember eintragen, PayPal-Abonnement zum Vertragsende beenden.\n"
    . "4. Dem Kunden das genaue Vertragsende mitteilen.\n";

// ---- Versand -------------------------------------------------------------
$okKunde = false;
if (!$auffaellig) {
    $okKunde = sende($email, "Bestätigung Ihrer Kündigung ($vorgang)", $textKunde, MEMBER_MAIL);
}
sende(MEMBER_MAIL, ($auffaellig ? '[PRÜFEN] ' : '') . "Kündigung $vorgang", $textIntern, $email);

if ($zaehler['alarm_faellig']) {
    sende(MEMBER_MAIL, 'ALARM Kündigungsformular: ungewöhnlich viele Absendungen',
        "In den letzten 60 Minuten: " . $zaehler['gesamt'] . " Absendungen.\n"
        . "Bitte Protokoll prüfen und ggf. Cloudflare-Regel verschärfen.\n", MEMBER_MAIL);
}

// ---- Bestätigungsseite ---------------------------------------------------
if ($auffaellig) {
    $hinweisMail = '<p>Ihre Kündigung wurde gespeichert und weitergeleitet. '
        . 'Die automatische Bestätigung konnte wegen ungewöhnlich vieler Anfragen nicht versandt werden; '
        . 'Sie erhalten sie nach Prüfung von uns. Bitte bewahren Sie diese Seite auf '
        . 'oder wenden Sie sich an <a href="mailto:' . MEMBER_MAIL . '">' . MEMBER_MAIL . '</a>.</p>';
} elseif ($okKunde) {
    $hinweisMail = '<p>Eine Bestätigung wurde an <strong>' . h($email) . '</strong> gesendet.</p>';
} else {
    $hinweisMail = '<p><strong>Hinweis:</strong> Die Bestätigungs-E-Mail konnte nicht versandt werden. '
        . 'Ihre Kündigung ist trotzdem eingegangen. Bitte bewahren Sie diese Seite auf '
        . 'oder wenden Sie sich an <a href="mailto:' . MEMBER_MAIL . '">' . MEMBER_MAIL . '</a>.</p>';
}

seite('Kündigung eingegangen',
    '<p>Ihre Kündigung ist am <strong>' . h($datum) . '</strong> um <strong>' . h($uhrzeit) . '</strong> eingegangen.</p>'
    . $hinweisMail
    . '<p>Bitte bewahren Sie diese Seite auf: Druckfunktion (Strg+P / Cmd+P) oder als PDF speichern.</p>'
    . '<dl>'
    . '<dt>Vorgangsnummer</dt><dd>' . h($vorgang) . '</dd>'
    . '<dt>Art der Kündigung</dt><dd>' . h($artText) . '</dd>'
    . ($art === 'ausserordentlich' ? '<dt>Grund</dt><dd>' . nl2br(h($grund)) . '</dd>' : '')
    . '<dt>Gewünschtes Vertragsende</dt><dd>' . h($endeText) . '</dd>'
    . '<dt>Vertragskennung</dt><dd>' . h($vertrag) . '</dd>'
    . ($produkt !== '' ? '<dt>Vertrag</dt><dd>' . h($produkt) . '</dd>' : '')
    . '</dl>'
    . '<p><a href="https://www.weatheronline.de/">Zur Startseite</a></p>');
