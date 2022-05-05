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
        margin-bottom: 0.25rem;
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
<h1>Raw harvest data</h1>
<hr>
<?php
$file = fopen("../csv files/harvest data - clean.csv", "r");

while (!feof($file)) {
 $line = fgets($file);
 echo "<code>" . $line . "</code><br>";
}
?>
</body>
