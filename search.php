<?php
// Add two search functions

/**
 * Search for a string in the JSON data and return matching elements and URLs.
 *
 * @param string $searchString
 */
function searchStringInJson($searchString) {
    $jsonFilePath = 'output.json';
    $jsonData = file_get_contents($jsonFilePath);
    $data = json_decode($jsonData, true);

    $matches = [];

    foreach ($data as $entry) {
        foreach ($entry as $key => $value) {
            if(is_array($value)){
                foreach($value as $actualval){
                    if (is_string($actualval) && strpos($actualval, $searchString) !== false) {
                        $matches[] = [
                            'url' => $entry['url'],
                            'element' => $key,
                        ];
                    }
                }
            } elseif (is_string($value) && strpos($value, $searchString) !== false) {
                $matches[] = [
                    'url' => $entry['url'],
                    'element' => $key,
                ];
            }
        }
    }

    if (!empty($matches)) {
        echo "Elements and urls where the string '$searchString' object is found:<hr>";
        foreach ($matches as $match) {

            echo "Element: {$match['element']}, URL: {$match['url']}<hr>";
        }
    } else {
        echo "No matches found for the string '$searchString' in the  object.<hr>";
    }
}

/**
 * Search for a string in a specific JSON object and return matching URLs.
 *
 * @param string $searchString
 * @param string $jsonObject
 */
/**
 * Search for a string in a specific JSON object and return matching URLs.
 *
 * @param string $searchString
 * @param string $jsonObject
 */
function searchStringInJsonObject($searchString, $jsonObject) {
    $jsonFilePath = 'output.json';
    $jsonData = file_get_contents($jsonFilePath);
    $data = json_decode($jsonData, true);

    $matches = [];

    foreach ($data as $entry) {
        if (isset($entry[$jsonObject])) {
            $value = $entry[$jsonObject];

            if (is_array($value)) {
                foreach ($value as $actualval) {
                    if (is_string($actualval) && strpos($actualval, $searchString) !== false) {
                        $matches[] = [
                            'url' => $entry['url'],
                            'element' => $jsonObject,
                        ];
                    } elseif (is_array($actualval)) {
                        foreach ($actualval as $nestedVal) {
                            if (is_string($nestedVal) && strpos($nestedVal, $searchString) !== false) {
                                $matches[] = [
                                    'url' => $entry['url'],
                                    'element' => $jsonObject,
                                ];
                            }
                        }
                    }
                }
            } elseif (is_string($value) && strpos($value, $searchString) !== false) {
                echo $entry['url'];
                $matches[] = [
                    'url' => $entry['url'],
                    'element' => $jsonObject,
                ];
            }
        }
    }

    if (!empty($matches)) {
        echo "URLs where the string '$searchString' is found in the '$jsonObject' object:<hr>";
        foreach ($matches as $url) {
            echo "URL: {$url['url']}<hr>";
        }
    } else {
        echo "No matches found for the string '$searchString' in the '$jsonObject' object.<hr>";
    }
}


// ... (your existing code)

// Call the search functions
echo "<h1>Results of searching generally:</h1>";
$searchString = 'tips';
searchStringInJson($searchString);
echo "<h1>Results of searching through element's name:</h1>";
$jsonObject = 'spans'; // Change this to the desired JSON object (e.g., 'paragraphs', 'buttons')
searchStringInJsonObject('By', $jsonObject);

?>