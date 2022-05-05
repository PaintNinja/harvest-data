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
        main {
            width: min(750px, 100%);
            margin-inline: 1rem;
            display: grid;
            grid-template-columns: auto auto;
            gap: 1rem;
            justify-content: stretch;
        }
        article {
            margin-top: 3rem;
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

// make an enum of error types
enum ErrorType {
    case ShortOrMissingCountyName;
    case CountyNameStartsWithNumber;
    case UnknownCropCode;
    case MissingCropCode;
}

function showError(ErrorType $errorType, string $problematicText, string $county): void {
    ?>
    <details>
    <?php
    switch ($errorType) {
        case ErrorType::ShortOrMissingCountyName:
            ?>
            <summary>County name too short or missing: <code>"<?php echo $problematicText; ?>"</code></summary>
            <p>Tips:</p>
            <ul>
                <li>Check that there is a county name at the start of the line. If not, add one</li>
                <li>Make sure the entered county name is the full name of the county, not a postcode</li>
            </ul>
            <?php
            break;
        case ErrorType::CountyNameStartsWithNumber:
            ?>
            <summary>County name should not start with a number: <code>"<?php echo $problematicText; ?>"</code></summary>
            <p>Tips:</p>
            <ul>
                <li>Check that there is a county name at the start of the line. If not, add one</li>
            </ul>
            <?php
            break;
        case ErrorType::UnknownCropCode:
            ?>
            <summary>Unknown crop code: <code>"<?php echo $problematicText; ?>"</code></summary>
            <p>Tips:</p>
            <ul>
                <li>Make sure the entered crop code is a recognised crop code:</li>
                <ul>
                    <li>W for wheat</li>
                    <li>B for barley</li>
                    <li>M for maize</li>
                    <li>BE for beetroot</li>
                    <li>C for carrot</li>
                    <li>PO for potatoes</li>
                    <li>PA for parsnips</li>
                    <li>O for oats</li>
                </ul>
                <li>Check for any typos in your crop code:</li>
                <ul>
                    <li>For example, you may have accidentally wrote "BD" instead of "BE"</li>
                </ul>
            </ul>
            <?php
            break;
        case ErrorType::MissingCropCode:
            ?>
            <summary>Missing crop code in county "<code><?php echo $county; ?>"</code></summary>
            <p>Tips:</p>
            <ul>
                <li>Check that all your numbers have a crop code before them. For example:</li>
                <ul>
                    <li>Good: <code>Cambridgeshire, W, 21, C, 782</code></li>
                    <li>Bad:  <code>Cambridgeshire, W, 21, 782</code> (missing C before 782)</li></li>
                </ul>
            </ul>
            <?php
            break;
    }
    ?>
    </details>
    <?php
}

$file = fopen("../csv files/harvest data - validation needed.csv", "r");

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

    // first entry should always be the county
    $county = ucfirst($splitLine[0]);
    if (strlen($county) < 4) {
        showError(ErrorType::ShortOrMissingCountyName, $county, $county);
    } else if (is_numeric($county[0])) {
        showError(ErrorType::CountyNameStartsWithNumber, $county, $county);
    }
    $data->put($county, new Map());
    array_shift($splitLine);

    // loop through the rest of the entries
    $cropCode = "";
    foreach ($splitLine as $index => $value) {
        // crops are stored as value pairs, first is the crop code, second is the harvest amount
        if (is_numeric($value)) { // the value is the harvest amount associated to the previous value's crop code
            // lookup the crop name from the code, falling back to the crop code if unknown
            $cropName = $cropCode;
            if (array_key_exists($cropCode, $cropCodes)) {
                $cropName = $cropCodes[$cropCode];
            } else if ($cropCode === "") {
                $cropName = "Unknown";
                showError(ErrorType::MissingCropCode, $cropCode, $county);
            } else {
                // log the unknown crop code
                showError(ErrorType::UnknownCropCode, $cropCode, $county);
            }
            $data->get($county)->put($cropName, $value);
        } else { // the value is the crop code for the next value's harvest amount
            $cropCode = strtoupper($value);
        }
    }
}

// apply the override if it exists
if (file_exists("../csv files/override.csv")) {
    $overrideFile = fopen("../csv files/override.csv", "r");

    while (!feof($overrideFile)) {
        $line = fgets($overrideFile);
        $splitLine = explode(", ", $line);

        $county = $splitLine[0];
        try {
            $countyData = $data->get($county);
        } catch (Exception $e) {
            $countyData = new Map();
        }

        $cropCode = "";
        foreach ($splitLine as $index => $value) {
            if (is_numeric($value)) {
                $cropName = $cropCodes[$cropCode];
                $countyData->put($cropName, $value);
            } else {
                $cropCode = $value;
            }
        }
        $data->put($county, $countyData);
    }
}

echo "<main>";

// make the tables
foreach ($data as $county => $countyData) {
//    print_r($county);
//    echo "<pre>";
//    print_r($countyData);
//    echo "</pre>";
    echo "<article>";
    echo "<h2>$county</h2>";
    ?>
    <table border="1">
        <tr>
            <th>Crop</th>
            <th>Harvest (tonnes)</th>
            <th>Percentage</th>
        </tr>
        <?php
        foreach ($countyData as $crop => $harvest) {
//        print_r($crop);
//        print_r($harvest);
            ?>
            <tr>
                <td><?php echo $crop; ?></td>
                <td><?php echo $harvest; ?></td>
                <?php
                // calculate the percentage
                $harvestTotal = $data->get($county)->sum();
                $percentage = ($harvest / $harvestTotal) * 100;
                ?>
                <td><?php echo round($percentage, 2); ?>%</td>
            </tr>
            <?php
        }
        ?>
        <tr>
            <td>Total</td>
            <td><?php echo $data->get($county)->sum(); ?></td>
            <td>100%</td>
        </tr>
    </table>
    </article>
    <?php
}
?>
</main>
</body>
