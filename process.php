<?php
// Load composer autoload file, so later will be able to load from vendor package
require_once __DIR__ . '/vendor/autoload.php';

// Here i use php package name "League CSV reader" to read from uploaded csv file
use League\Csv\Reader;

// Set All responses to JSON, since we need to send to frontend with JSON result
header('Content-Type: application/json');

/**
 * Function to upload/proses .csv file
 * @param string $path
 * @throws InvalidArgumentException
 * @return array{best_selling_sku: array{sku: string, total_quantity: int, total_revenue: float}|array{best_selling_sku: array{sku: string, total_quantity: mixed}, total_revenue: float}}
 */
function processCsvFile(string $path): array
{
    // Read & loads the uploaeded file into a string
    $csvContent = (string) file_get_contents($path);

    // Error handling 1: Throw error if csv file is empty, no need to process
    if (trim($csvContent) === '') {
        throw new InvalidArgumentException('The CSV file is empty.');
    }

    // Parse CSV from the string using "League csv package"
    $csv = Reader::fromString($csvContent);

    // Make 1st rows always as column headers
    $csv->setHeaderOffset(0);

    // Error handling 2: Check all required columns should be cumpolsory
    $csvHeaders = array_diff(['sku', 'quantity', 'price'], $csv->getHeader());

    if (!empty($csvHeaders)) {
        throw new InvalidArgumentException('Missing required columns: ' . implode(', ', $csvHeaders) . '.');
    }

    // Variable to store total of revenue for all rows
    $totalRevenue = 0.0;
    // Accumulated quantity sold per SKU
    $skuQuantities = [];
    // Row counter for error messages (starts at 1 for the header since 1st row was set as header)
    $rowNum = 1;

    foreach ($csv->getRecords() as $record) {
        // Increment row number for each data row
        $rowNum++;
        // Remove and trim whitespace from SKU
        $sku = trim($record['sku']);
        $quantity = $record['quantity'];
        $price = $record['price'];

        // Error handling 3: Check SKU
        if ($sku === '') {
            throw new InvalidArgumentException("Row {$rowNum}: SKU cannot be empty.");
        }

        // Error handling 4: Check quantity
        if (!is_numeric($quantity) || (int) $quantity <= 0) {
            throw new InvalidArgumentException("Row {$rowNum}: quantity must be positive integer, got '{$quantity}'.");
        }

        // Error handling 5: Check price
        if (!is_numeric($price) || (float) $price < 0) {
            throw new InvalidArgumentException("Row {$rowNum}: price must be not negative number, got '{$price}'.");
        }

        // Get sums of revenue (quantity × price) and store into $totalRevenue variable
        $totalRevenue += (int) $quantity * (float) $price;
        // Increase up the count for the same SKU (to get highest value later)
        $skuQuantities[$sku] = ($skuQuantities[$sku] ?? 0) + (int) $quantity;
    }

    // Errors handling 5: No data rows found after the headers
    if (empty($skuQuantities)) {
        throw new InvalidArgumentException('The CSV file contains no data rows.');
    }

    // Sort SKU by quantity descending to find the highest value
    arsort($skuQuantities);

    // Get the 1st SKU since we sort the value, so 1st index must be the highest one
    $bestSku = (string) array_key_first($skuQuantities);

    return [
        // Round to 2 decimal places for currency
        'total_revenue' => round($totalRevenue, 2),
        'best_selling_sku' => [
            'sku' => $bestSku,
            'total_quantity' => $skuQuantities[$bestSku],
        ],
    ];
}

try {
    // Set the default CSV
    $csvPath = __DIR__ . '/allsome_interview_test_orders.csv';

    // Check if a file was uploaded via the form
    if (!empty($_FILES['csv']['tmp_name'])) {
        $upload = $_FILES['csv'];

        // Errors handling 6: COmmons upload errors from PHP Docs
        // Can get the common list from HERE https://www.php.net/manual/en/filesystem.constants.php#constant.upload-err-cant-write
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE  => 'Exceeds server upload limit.',
                UPLOAD_ERR_FORM_SIZE => 'Exceeds form upload limit.',
                UPLOAD_ERR_NO_FILE   => 'No file was uploaded',
            ];

            // Set 400 (bad request)
            http_response_code(400);
            echo json_encode(['error' => $messages[$upload['error']] ?? 'Unkown error!.']);
            exit;
        }

        // Errors handling 7: Only allowed .csv file extension only.
        if (strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION)) !== 'csv') {
            // Set 400 (bad request)
            http_response_code(400);
            echo json_encode(['error' => 'Only .csv files are accepted.']);
            exit;
        }

        // Use the uploaded temp file path
        $csvPath = $upload['tmp_name'];
    }

    // Errors handling 8: Ensure the file is exist
    if (!file_exists($csvPath)) {
        // Set 404 (Not found)
        http_response_code(404);
        echo json_encode(['error' => 'CSV file not found or unreadable']);
        exit;
    }

    // Process and return the result JSON
    echo json_encode(processCsvFile($csvPath), JSON_PRETTY_PRINT);

} catch (InvalidArgumentException $e) {
    // Set 422 (Unprocessable Entity)
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()]);

} catch (Throwable $e) {
    // Set 500 (Internal Server Error)
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);

}
