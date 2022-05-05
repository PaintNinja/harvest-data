<!DOCTYPE html>
<html lang="en-gb">
<head>
    <title>Data processing 1</title>
    <meta name="color-scheme" content="light dark">
    <style>
        body {
            font-family: system-ui;
        }
        h1, h2 {
            margin-top: 3rem;
            margin-bottom: 0.25rem;
        }
        h1:first-of-type, h2:first-of-type {
            margin-top: 0;
        }
        code, pre {
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

$cropCodes = [
    "W" => "Wheat",
    "B" => "Barley",
    "M" => "Maize",
    "BE" => "Beetroot",
    "C" => "Carrot",
    "PO" => "Potatoes",
    "PA" => "Parsnips",
    "O" => "Oats"
];
//echo "<pre>";
//print_r($cropCodes);
//echo "</pre>";

// create a Map of the data
$data = new Map();

while (!feof($file)) {
    $line = fgets($file);
    $splitLine = explode(", ", $line);

    // first entry is always the county
    $county = $splitLine[0];
    $data->put($county, new Map());
    array_shift($splitLine);

    // loop through the rest of the entries
    $cropCode = "";
    foreach ($splitLine as $index => $value) {
        if (is_numeric($value)) {
            // lookup the crop name from the code
            $cropName = $cropCodes[$cropCode];
            $data->get($county)->put($cropName, $value);
        } else {
            $cropCode = $value;
        }
    }
}
?>
<pre><?php print_r($data); ?></pre>
</body>
