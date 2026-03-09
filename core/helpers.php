<?php
/**
 * Helpers globaux (temps relatif, etc.)
 */

if (!function_exists('relativeTime')) {
    /**
     * Retourne une date en temps relatif : 5m, 1h, 2j, 1 sem, 2 mois, 1 an
     * Les dates MySQL (sans timezone) sont interprétées en heure locale (Europe/Paris) pour correspondre au JS.
     * @param string|int $datetime Date ISO ou timestamp
     * @return string
     */
    function relativeTime($datetime) {
        $tz = new DateTimeZone('Europe/Paris');
        if (is_numeric($datetime)) {
            $ts = (int) $datetime;
        } else {
            $dt = new DateTime($datetime, $tz);
            $ts = $dt->getTimestamp();
        }
        $now = (new DateTime('now', $tz))->getTimestamp();
        $diff = $now - $ts;
        if ($diff < 60) return "à l'instant";
        if ($diff < 3600) return floor($diff / 60) . 'm';
        if ($diff < 86400) return floor($diff / 3600) . 'h';
        if ($diff < 604800) return floor($diff / 86400) . 'j';
        if ($diff < 2592000) return floor($diff / 604800) . ' sem';
        if ($diff < 31536000) return floor($diff / 2592000) . ' mois';
        $years = floor($diff / 31536000);
        return $years . ' an' . ($years > 1 ? 's' : '');
    }
}
