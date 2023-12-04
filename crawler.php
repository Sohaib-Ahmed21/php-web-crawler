<?php
// Specify the URL and depth
$startUrl = 'https://github.com';
$depth = 1;

file_put_contents('output.json', '');
// Load robots.txt content
$baseUrl = parse_url($startUrl, PHP_URL_SCHEME) . '://' . parse_url($startUrl, PHP_URL_HOST);

// Construct the robots.txt URL
$robotsUrl = $baseUrl . '/robots.txt';
$robotsContent = file_get_contents($robotsUrl);
echo $robotsUrl . "<hr>";

// Parse robots.txt and extract disallowed paths
$disallowedPaths = parseRobotsTxt($robotsContent);

// Recursive function to crawl links with a specified depth
function crawlLinks($url, $depth, $disallowedPaths) {
    global $baseUrl;

    $headers = get_headers($url);

    if (strpos($headers[0], '404') !== false) {
        echo "Page not found: $url" . "<hr>";
        return;
    }

    try {
        // Load HTML content from URL into DOMDocument
        $dom = new DOMDocument;
        libxml_use_internal_errors(true); // Enable error handling for loadHTMLFile

        $loaded = $dom->loadHTMLFile($url);


        libxml_clear_errors(); // Clear any warnings or errors

        // Access links from href attribute
        $anchors = $dom->getElementsByTagName('a');
        $linkList = [];

        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');

            // Check if the href is a valid URL
            $absoluteUrl = makeAbsoluteUrl($href, $baseUrl);
            if (filter_var($absoluteUrl, FILTER_VALIDATE_URL) && !isDisallowed($absoluteUrl, $disallowedPaths)) {
                $linkList[] = $absoluteUrl;
            }
        }

        // Retrieve text from HTML tags
        $titleElement = $dom->getElementsByTagName('title')->item(0);
        $headings = $dom->getElementsByTagName('h1');
        $paragraphs = $dom->getElementsByTagName('p');
        $spans = $dom->getElementsByTagName('span');
        $anchors = $dom->getElementsByTagName('a');
        $listItems = $dom->getElementsByTagName('li');
        $tableCells = $dom->getElementsByTagName('td');
        $labels = $dom->getElementsByTagName('label');
        $buttons = $dom->getElementsByTagName('button');

        $title = $titleElement ? $titleElement->textContent : 'No title found';
        $headingTexts = getTagTexts($headings);
        $paragraphTexts = getTagTexts($paragraphs);
        $spanTexts = getTagTexts($spans);
        $anchorTexts = getTagTexts($anchors);
        $listItemTexts = getTagTexts($listItems);
        $cellTexts = getTagTexts($tableCells);
        $labelTexts = getTagTexts($labels);
        $buttonTexts = getTagTexts($buttons);

        // Create an array to store the data
        $data = [
            'url' => $url,
            'title' => $title,
            'links' => $linkList,
            'headings' => $headingTexts,
            'paragraphs' => $paragraphTexts,
            'spans' => $spanTexts,
            'anchors' => $anchorTexts,
            'listItems' => $listItemTexts,
            'tableCells' => $cellTexts,
            'labels' => $labelTexts,
            'buttons' => $buttonTexts,
        ];

        // Retrieve existing data from output.json
        $existingData = [];
        $jsonFilePath = 'output.json';
        if (file_exists($jsonFilePath)) {
            $existingData = json_decode(file_get_contents($jsonFilePath), true);
        }

        // Append the data to the existing data
        $existingData[] = $data;

        // Convert the combined data to JSON format
        $jsonData = json_encode($existingData, JSON_PRETTY_PRINT);

        // Store the JSON data in a file
        file_put_contents($jsonFilePath, $jsonData);

        // Display information about the traversed URL
        echo "Traversed: $url" . "<hr>";

        // Crawl links recursively with decreased depth
        if ($depth > 0) {
            foreach ($linkList as $link) {
                crawlLinks($link, $depth - 1, $disallowedPaths);
            }
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}

// Start crawling
crawlLinks($startUrl, $depth, $disallowedPaths);
echo "json updated";

/**
 * Helper function to make an absolute URL from a relative URL and base URL.
 *
 * @param string $relativeUrl
 * @param string $baseUrl
 * @return string
 */
function makeAbsoluteUrl($relativeUrl, $baseUrl) {
    $relativeUrl = ltrim($relativeUrl, '/');
    return rtrim($baseUrl, '/') . '/' . $relativeUrl;
}

/**
 * Helper function to retrieve disallowed paths from robots.txt.
 *
 * @param string $robotsContent
 * @return array
 */
function parseRobotsTxt($robotsContent) {
    $disallowedPaths = [];

    // Extract Disallow directives
    preg_match_all('/Disallow:\s*(.*?)\s*$/im', $robotsContent, $matches);

    if (!empty($matches[1])) {
        $disallowedPaths = $matches[1];
    }

    return $disallowedPaths;
}

/**
 * Check if a URL is disallowed based on robots.txt rules.
 *
 * @param string $url
 * @param array $disallowedPaths
 * @return bool
 */
function isDisallowed($url, $disallowedPaths) {
    foreach ($disallowedPaths as $path) {
        // Use simple string comparison for simplicity 
        if (strpos($url, $path) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Helper function to retrieve text from DOMNodeList of elements.
 *
 * @param DOMNodeList $elements
 * @return array
 */
function getTagTexts($elements) {
    $texts = [];
    foreach ($elements as $element) {
        $texts[] = $element->textContent;
    }
    return $texts;
}

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
            if (is_array($value) && in_array($searchString, $value)) {
                $matches[] = [
                    'url' => $entry['url'],
                    'element' => $key,
                ];
            }
        }
    }

    if (!empty($matches)) {
        echo "Elements where the string '$searchString' is found:<br>";
        foreach ($matches as $match) {
            echo "Element: {$match['element']}, URL: {$match['url']}<hr>";
        }
    } else {
        echo "No matches found for the string '$searchString'.<hr>";
    }
}

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
        if (isset($entry[$jsonObject]) && is_array($entry[$jsonObject]) && in_array($searchString, $entry[$jsonObject])) {
            $matches[] = $entry['url'];
        }
    }

    if (!empty($matches)) {
        echo "URLs where the string '$searchString' is found in the '$jsonObject' object:<hr>";
        foreach ($matches as $url) {
            echo "URL: $url<br>";
        }
    } else {
        echo "No matches found for the string '$searchString' in the '$jsonObject' object.<hr>";
    }
}

// ... (your existing code)

// Call the search functions
$searchString = 'blame';
searchStringInJson($searchString);
echo "<h1>Results of searching through element's name:</h1>";
$jsonObject = 'spans'; // Change this to the desired JSON object (e.g., 'paragraphs', 'buttons')
searchStringInJsonObject('Toggle', $jsonObject);
?>
