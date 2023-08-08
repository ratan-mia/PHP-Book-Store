<?php

class VersionTimezoneAdjuster {
    public static function adjustTimestamp($timestamp, $version) {
        if (version_compare($version, '1.0.17+60', '<')) {
            $datetime = new DateTime($timestamp, new DateTimeZone('UTC'));
            $datetime->setTimezone(new DateTimeZone('Europe/Berlin'));
            return $datetime->format('Y-m-d H:i:s');
        }
        return $timestamp;
    }
}
