<?php
define(
    'HMS_SOCIETY_ADDRESS',
    'AS College Road, Satsang Nagar Near Atal Smriti Park Deoghar, Jharkhand 814112'
);
define(
    'HMS_PG_ADDRESS',
    'Hirna Near Tv relay Center Near AS College Road Dewan baba Deoghar, Jharkhand 814112'
);

function hms_property_map_url($address, $embed = false) {
    $base = $embed
        ? 'https://maps.google.com/maps?q='
        : 'https://www.google.com/maps/search/?api=1&query=';

    return $base . rawurlencode($address) . ($embed ? '&z=15&output=embed' : '');
}
