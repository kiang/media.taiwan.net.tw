<?php
include_once __DIR__ . '/vendor/autoload.php';
$basePath = dirname(__DIR__);
$rawPath = $basePath . '/raw/trails';
if (!file_exists($rawPath)) {
    mkdir($rawPath, 0777, true);
}
$targetPath = $basePath . '/docs/geojson';
if (!file_exists($targetPath)) {
    mkdir($targetPath, 0777, true);
}
$tmpZipFile = $rawPath . '/Trail-json.zip';
file_put_contents($tmpZipFile, file_get_contents('https://media.taiwan.net.tw/XMLReleaseAll_public/v2.0/Zh_tw/Trail-json.zip'));

$za = new ZipArchive();
$za->open($tmpZipFile);
$za->extractTo($rawPath, ['TrailList.json', 'TrailFacilityList.json']);
$za->close();
unlink($tmpZipFile);

// remove BOM
foreach (glob($rawPath . '/*.json') as $jsonFile) {
    $c = file_get_contents($jsonFile);
    $pos = strpos($c, '{');
    if ($pos !== 0) {
        file_put_contents($jsonFile, substr($c, $pos));
    }
}

$json = json_decode(file_get_contents($rawPath . '/TrailList.json'), true);
$fc = [
    'type' => 'FeatureCollection',
    'features' => [],
];
$keys = ['TrailID', 'TrailName', 'Description', 'TrailClass', 'TrafficInfo', 'ParkingInfo', 'ServiceStatus', 'IsPublicAccess', 'IsAccessibleForFree', 'TrailLength', 'TrailHeight'];
foreach ($json['Trails'] as $trail) {
    $polyline = json_decode(geoPHP::load($trail['Geometry'], 'wkt')->out('json'), true);
    $f = [
        'type' => 'Feature',
        'properties' => [],
        'geometry' => $polyline,
    ];
    foreach ($keys as $key) {
        $f['properties'][$key] = $trail[$key];
    }
    $fc['features'][$trail['TrailID']] = $f;
}
ksort($fc['features']);
$fc['features'] = array_values($fc['features']);
file_put_contents($targetPath . '/trails.json', json_encode($fc, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
