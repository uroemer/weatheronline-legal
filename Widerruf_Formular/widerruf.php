<?php
/*
 * Auswertung des Widerrufsformulars (vertrag-widerrufen.html) – BEISPIEL, vor Einsatz prüfen.
 * Stand 06.10.2026, Fassung 2 (Schutz vor Missbrauch M1–M4, siehe Anleitung_Widerruf, Anhang A).
 *
 * Anforderungen § 356a BGB:
 *   - Eingangsbestätigung sofort auf dauerhaftem Datenträger (E-Mail)
 *   - mit Inhalt der Erklärung sowie Datum und Uhrzeit des Eingangs
 *
 * Ablauf: Eingaben prüfen -> Missbrauchsprüfung -> Zeitstempel -> Protokoll
 *         -> E-Mail an Kunden und an die Bearbeiter der Member-Mails -> Bestätigungsseite.
 * Grundsatz: Ein echter Widerruf wird NIE verworfen. Auffällige Eingänge werden
 *            protokolliert und an die Bearbeiter der Member-Mails gemeldet; nur die automatische
 *            Kunden-E-Mail entfällt (Schutz vor Missbrauch als E-Mail-Versender).
 */

declare(strict_types=1);
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/London');

// ---- Einstellungen -------------------------------------------------------
const MEMBER_MAIL   = 'member@weatheronline.co.uk';
const ABSENDER      = 'WeatherOnline <member@weatheronline.co.uk>';
// Verzeichnis AUSSERHALB des Web-Verzeichnisses (Pfad anpassen), für den Webserver beschreibbar:
const DATENVERZ     = __DIR__ . '/../widerruf-protokoll';
const PROTOKOLL     = DATENVERZ . '/widerrufe.csv';
const ZAEHLER       = DATENVERZ . '/zaehler.json';
// Zufälliger, geheimer Wert (einmalig erzeugen, z. B. bin2hex(random_bytes(16))) – für die Kürzung der IP-Adressen:
const IP_SALZ       = 'BITTE-ERSETZEN-geheimer-zufallswert';
const MAX_JE_IP     = 3;     // M1/M4: höchstens 3 Absendungen je IP-Adresse ...
const FENSTER_SEK   = 3600;  // ... innerhalb von 60 Minuten
const ALARM_GESAMT  = 20;    // M1(c): Alarm an die Bearbeiter der Member-Mails ab 20 Absendungen insgesamt je Stunde
const MIN_DAUER_MS  = 3000;  // M4: Mindestzeit zwischen Seitenaufruf und Absenden (3 Sekunden)
// Nur auf true setzen, wenn der Server ausschließlich über Cloudflare erreichbar ist:
const HINTER_CLOUDFLARE = true;

const FIRMA         = "WeatherOnline Limited · Brookfield Court, Selby Road, Garforth, Leeds, LS25 1NB, England\n"
                    . "Registernummer 04619915 (England and Wales) · member@weatheronline.co.uk";

// ---- Hilfsfunktionen -----------------------------------------------------
function feld(string $name, int $max): string {
    $v = isset($_POST[$name]) && is_string($_POST[$name]) ? trim($_POST[$name]) : '';
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    return mb_substr($v, 0, $max);
}
function einzeilig(string $v): string {          // verhindert Kopfzeilen-Einschleusung
    return trim(preg_replace('/[\r\n\t]+/', ' ', $v));
}
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// M3: Schutz vor Formel-Einschleusung beim Öffnen der CSV-Datei in Tabellenprogrammen
function csvSicher(string $v): string {
    $v = str_replace(["\n", "\t"], ' ', $v);
    return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
}

// M1(b): Links aus Freitext entfernen (für die E-Mail an die angegebene Adresse)
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
 * M1(a)/M4: Zählt Absendungen je (gekürzter, gesalzener) IP-Adresse und insgesamt.
 * Gespeichert wird nur ein Kurz-Hash der IP-Adresse, keine IP-Adresse im Klartext;
 * Einträge älter als FENSTER_SEK werden bei jedem Aufruf gelöscht.
 * Rückgabe: ['je_ip' => n, 'gesamt' => n, 'alarm_faellig' => bool]
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
    if ($gesamt >= ALARM_GESAMT && ($daten['alarm'] ?? 0) <= $grenze) {   // höchstens ein Alarm je Stunde
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

// ---- Fallenfeld (einfache Bots) -------------------------------------------
if (feld('website', 200) !== '') {
    seite('Widerruf eingegangen', '<p>Vielen Dank.</p>');
}

$name       = einzeilig(feld('name', 120));
$vertrag    = einzeilig(feld('vertrag', 120));
$produkt    = einzeilig(feld('produkt', 200));
$email      = einzeilig(feld('email', 200));
$mitteilung = feld('mitteilung', 2000);
$dauerRoh   = feld('dauer', 12);

// ---- Pflichtfelder prüfen --------------------------------------------------
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

// ---- Zeitstempel und Vorgangsnummer -------------------------------------
$jetzt   = new DateTimeImmutable('now');
$datum   = $jetzt->format('d.m.Y');
$uhrzeit = $jetzt->format('H:i:s') . ' Uhr (' . $jetzt->format('T') . ')';
$vorgang = 'W-' . $jetzt->format('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

// ---- Missbrauchsprüfung (M1, M4) -----------------------------------------
$merkmale = [];
$zaehler  = zaehle($jetzt->getTimestamp());
if ($zaehler['je_ip'] > MAX_JE_IP) {
    $merkmale[] = 'Begrenzung: ' . $zaehler['je_ip'] . ' Absendungen von derselben IP-Adresse in 60 Minuten';
}
if ($dauerRoh !== '' && ctype_digit($dauerRoh) && (int)$dauerRoh < MIN_DAUER_MS) {
    $merkmale[] = 'Formular in ' . (int)$dauerRoh . ' ms ausgefüllt (unter ' . MIN_DAUER_MS . ' ms)';
}
$auffaellig = (bool)$merkmale;
$mitLinks   = ohneLinks($mitteilung) !== $mitteilung;
if ($mitLinks) { $merkmale[] = 'Mitteilung enthielt Links (in der Kunden-E-Mail entfernt)'; }

// ---- Protokoll (Nachweis) ------------------------------------------------
if (!is_dir(DATENVERZ)) { @mkdir(DATENVERZ, 0750, true); }
$fp = @fopen(PROTOKOLL, 'ab');
if ($fp) {
    flock($fp, LOCK_EX);
    fputcsv($fp, array_map('csvSicher', [
        $vorgang, $jetzt->format(DATE_ATOM), $name, $vertrag, $produkt, $email, $mitteilung,
        $auffaellig ? 'auffaellig' : 'normal', implode(' | ', $merkmale),
    ]), ';');
    flock($fp, LOCK_UN);
    fclose($fp);
}

// ---- E-Mail-Texte --------------------------------------------------------
$inhaltIntern = "Name: $name\n"
    . "Benutzername / Rechnungsnummer: $vertrag\n"
    . ($produkt !== '' ? "Vertrag: $produkt\n" : '')
    . "E-Mail: $email\n"
    . ($mitteilung !== '' ? "Mitteilung (Originaltext): $mitteilung\n" : '');

$mitteilungKunde = ohneLinks($mitteilung);
$inhaltKunde = "Name: $name\n"
    . "Benutzername / Rechnungsnummer: $vertrag\n"
    . ($produkt !== '' ? "Vertrag: $produkt\n" : '')
    . "E-Mail: $email\n"
    . ($mitteilungKunde !== '' ? "Mitteilung: $mitteilungKunde\n" : '');

$textKunde = "Guten Tag $name,\n\n"
    . "wir bestätigen den Eingang Ihres Widerrufs am $datum um $uhrzeit.\n"
    . "Vorgangsnummer: $vorgang\n\n"
    . "Inhalt Ihrer Erklärung: Widerruf des Vertrags.\n$inhaltKunde\n"
    . "Soweit das Widerrufsrecht besteht, erstatten wir Ihre Zahlung innerhalb von 14 Tagen über das ursprünglich "
    . "verwendete Zahlungsmittel. Ein laufendes PayPal-Abonnement wird beendet und der Zugang gesperrt.\n\n"
    . "Falls Sie diesen Widerruf nicht selbst abgesendet haben, antworten Sie bitte auf diese E-Mail.\n\n"
    . "Mit freundlichen Grüßen\nWeatherOnline\n\n" . FIRMA . "\n";

$textIntern = ($auffaellig ? "ACHTUNG – AUFFÄLLIGER EINGANG, KEINE AUTOMATISCHE KUNDEN-E-MAIL VERSANDT\n"
                           . implode("\n", $merkmale) . "\n"
                           . "Bitte prüfen: echter Widerruf? Dann Eingangsbestätigung von Hand an die Konto-E-Mail-Adresse senden.\n\n"
                           : ($merkmale ? "Hinweis: " . implode(' | ', $merkmale) . "\n\n" : ''))
    . "Neuer Widerruf über das Formular\n\n"
    . "Vorgangsnummer: $vorgang\nEingang: $datum, $uhrzeit\n\n$inhaltIntern\n"
    . "Zu erledigen:\n"
    . "1. Vertrag suchen; angegebene E-Mail-Adresse mit der im Konto vergleichen (M2). Weicht sie ab:\n"
    . "   vor Sperre und Erstattung über die Konto-E-Mail-Adresse nachfragen.\n"
    . "2. Widerrufsrecht prüfen (Frist, Checkbox, Vertragsbestätigung).\n"
    . "3. Zugang sperren, PayPal-Abonnement beenden, Erstattung veranlassen.\n";

// ---- Versand -------------------------------------------------------------
$okKunde = false;
if (!$auffaellig) {
    $okKunde = sende($email, "Eingangsbestätigung Ihres Widerrufs ($vorgang)", $textKunde, MEMBER_MAIL);
}
sende(MEMBER_MAIL, ($auffaellig ? '[PRÜFEN] ' : '') . "Widerruf $vorgang – $name", $textIntern, $email);

if ($zaehler['alarm_faellig']) {
    sende(MEMBER_MAIL, 'ALARM Widerrufsformular: ungewöhnlich viele Absendungen',
        "In den letzten 60 Minuten wurden " . $zaehler['gesamt'] . " Absendungen des Widerrufsformulars gezählt.\n"
        . "Möglicher Missbrauch. Bitte Protokoll prüfen und ggf. Cloudflare-Regel verschärfen.\n", MEMBER_MAIL);
}

// ---- Bestätigungsseite ---------------------------------------------------
if ($auffaellig) {
    $hinweisMail = '<p>Ihr Widerruf wurde gespeichert und an die zuständigen Bearbeiter weitergeleitet. '
        . 'Wegen ungewöhnlich vieler Anfragen wurde die Bestätigung nicht automatisch per E-Mail versandt; '
        . 'Sie erhalten sie nach Prüfung von uns. Bitte bewahren Sie diese Seite auf (Drucken oder Speichern) '
        . 'oder wenden Sie sich an <a href="mailto:' . MEMBER_MAIL . '">' . MEMBER_MAIL . '</a>.</p>';
} elseif ($okKunde) {
    $hinweisMail = '<p>Eine Bestätigung wurde an <strong>' . h($email) . '</strong> gesendet.</p>';
} else {
    $hinweisMail = '<p><strong>Hinweis:</strong> Die Bestätigungs-E-Mail konnte nicht versandt werden. Ihr Widerruf ist trotzdem eingegangen. '
        . 'Bitte bewahren Sie diese Seite auf (Drucken oder Speichern) oder wenden Sie sich an '
        . '<a href="mailto:' . MEMBER_MAIL . '">' . MEMBER_MAIL . '</a>.</p>';
}

seite('Widerruf eingegangen',
    '<p>Ihr Widerruf ist am <strong>' . h($datum) . '</strong> um <strong>' . h($uhrzeit) . '</strong> bei uns eingegangen.</p>'
    . $hinweisMail
    . '<dl><dt>Vorgangsnummer</dt><dd>' . h($vorgang) . '</dd>'
    . '<dt>Name</dt><dd>' . h($name) . '</dd>'
    . '<dt>Benutzername / Rechnungsnummer</dt><dd>' . h($vertrag) . '</dd>'
    . ($produkt !== '' ? '<dt>Vertrag</dt><dd>' . h($produkt) . '</dd>' : '')
    . ($mitteilung !== '' ? '<dt>Mitteilung</dt><dd>' . nl2br(h($mitteilung)) . '</dd>' : '')
    . '</dl><p><a href="https://www.weatheronline.co.uk/">Zur Startseite</a></p>');
