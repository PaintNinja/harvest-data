<!DOCTYPE html>
<html lang="en-gb">
<head>
    <title>Data processing 1</title>
    <meta name="color-scheme" content="light dark">
    <style>
        body {
            font-family: system-ui;
        }
        h1 {
            margin-top: 3rem;
            margin-bottom: 0.125rem;
        }
        h1:first-of-type {
            margin-top: 0;
        }
        code {
            font-family: ui-monospace, monospace;
        }
    </style>
</head>
<body>
<h1>Formatted harvest data</h1>
<hr>
<?php
require '../vendor/autoload.php'; // import libs from Composer
use Ds\Map;

ini_set('display_errors', 1);

$file = fopen("../csv files/harvest data - clean.csv", "r");

// create a Map of the data
$map = new Map();

while (!feof($file)) {
    $line = fgets($file);
    $splitLine = explode(",", $line);

    // first entry is always County
    $county = $splitLine[0];
    $map->put($county, new Map());
    array_shift($splitLine);

    // loop through the rest of the entries
    $cropCode = "";
    foreach ($splitLine as $index => $value) {
        if (is_numeric($value)) {
            $map->get($county)->put($cropCode, $value);
        } else {
            $cropCode = $value;
        }
        echo "<code>index " . $index . ": " . $value . "</code><br>";
        //$map->get($splitLine[0])->put($key, $value);
    }
    echo "<br>";
}
?>
<pre><?php print_r($map); ?></pre>
</body>
