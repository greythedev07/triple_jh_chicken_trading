<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

function buildTablePdf(array $headers, array $row, $title) {
    $escape = function ($s) {
        $s = (string)$s;
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace('(', '\\(', $s);
        $s = str_replace(')', '\\)', $s);
        $s = str_replace("\r", '', $s);
        $s = str_replace("\n", ' ', $s);
        return $s;
    };

    // Landscape (letter): 792 x 612 points
    $pageW = 792;
    $pageH = 612;

    $marginL = 36;
    $marginR = 36;
    $tableW = $pageW - $marginL - $marginR;
    $tableX = $marginL;

    $titleY = 575;
    $tableTopY = 535;
    $headerH = 24;
    $rowH = 24;

    $colCount = max(1, count($headers));
    $colW = $tableW / $colCount;

    $streamParts = [];

    // Title
    $streamParts[] = "BT\n/F1 14 Tf\n";
    $streamParts[] = sprintf("1 0 0 1 %d %d Tm\n", (int)$tableX, (int)$titleY);
    $streamParts[] = sprintf("(%s) Tj\n", $escape($title));
    $streamParts[] = "ET\n";

    // Grid lines
    $bottomY = $tableTopY - $headerH - $rowH;
    $streamParts[] = "0.5 w\n";

    // Outer border
    $streamParts[] = sprintf("%d %d m %d %d l S\n", (int)$tableX, (int)$tableTopY, (int)($tableX + $tableW), (int)$tableTopY);
    $streamParts[] = sprintf("%d %d m %d %d l S\n", (int)$tableX, (int)$bottomY, (int)($tableX + $tableW), (int)$bottomY);
    $streamParts[] = sprintf("%d %d m %d %d l S\n", (int)$tableX, (int)$tableTopY, (int)$tableX, (int)$bottomY);
    $streamParts[] = sprintf("%d %d m %d %d l S\n", (int)($tableX + $tableW), (int)$tableTopY, (int)($tableX + $tableW), (int)$bottomY);

    // Header separator
    $headerSepY = $tableTopY - $headerH;
    $streamParts[] = sprintf("%d %d m %d %d l S\n", (int)$tableX, (int)$headerSepY, (int)($tableX + $tableW), (int)$headerSepY);

    // Vertical lines
    for ($i = 1; $i < $colCount; $i++) {
        $x = $tableX + ($colW * $i);
        $streamParts[] = sprintf("%d %d m %d %d l S\n", (int)$x, (int)$tableTopY, (int)$x, (int)$bottomY);
    }

    // Header text
    $streamParts[] = "BT\n/F1 9 Tf\n";
    for ($i = 0; $i < $colCount; $i++) {
        $hx = $tableX + ($colW * $i) + 3;
        $hy = $tableTopY - 16;
        $streamParts[] = sprintf("1 0 0 1 %d %d Tm\n", (int)$hx, (int)$hy);
        $streamParts[] = sprintf("(%s) Tj\n", $escape($headers[$i] ?? ''));
    }
    $streamParts[] = "ET\n";

    // Row text
    $streamParts[] = "BT\n/F1 9 Tf\n";
    for ($i = 0; $i < $colCount; $i++) {
        $rx = $tableX + ($colW * $i) + 3;
        $ry = $headerSepY - 16;
        $streamParts[] = sprintf("1 0 0 1 %d %d Tm\n", (int)$rx, (int)$ry);
        $value = $row[$i] ?? '';
        // crude fit: truncate long values
        if (strlen($value) > 22) {
            $value = substr($value, 0, 19) . '...';
        }
        $streamParts[] = sprintf("(%s) Tj\n", $escape($value));
    }
    $streamParts[] = "ET\n";

    $content = implode('', $streamParts);

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 792 612] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objects[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefPos . "\n%%EOF";

    return $pdf;
}

try {
    $tableCheck = $db->query("SHOW TABLES LIKE 'weekly_analytics'");
    if ($tableCheck->rowCount() === 0) {
        throw new Exception("weekly_analytics table does not exist.");
    }

    $date = isset($_GET['date']) ? trim((string)$_GET['date']) : '';
    $match = isset($_GET['match']) ? strtolower(trim((string)$_GET['match'])) : 'start';
    $format = isset($_GET['format']) ? strtolower(trim((string)$_GET['format'])) : 'csv';
    $checkOnly = isset($_GET['check']) && (string)$_GET['check'] === '1';

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date. Use YYYY-MM-DD.');
    }

    if (!in_array($match, ['start', 'end'], true)) {
        throw new Exception('Invalid match type.');
    }

    if (!in_array($format, ['csv', 'pdf'], true)) {
        throw new Exception('Invalid format.');
    }

    $column = $match === 'end' ? 'week_end_date' : 'week_start_date';

    $stmt = $db->prepare("SELECT id, week_start_date, week_end_date, total_sales, total_orders, total_products_sold, created_at, updated_at FROM weekly_analytics WHERE {$column} = ? LIMIT 1");
    $stmt->execute([$date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'No weekly analytics found for the selected date.']);
        exit;
    }

    if ($checkOnly) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $row,
        ]);
        exit;
    }

    $filenameBase = 'weekly_analytics_' . $row['week_start_date'] . '_to_' . $row['week_end_date'];

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filenameBase . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        $columns = ['id', 'week_start_date', 'week_end_date', 'total_sales', 'total_orders', 'total_products_sold', 'created_at', 'updated_at'];
        fputcsv($out, $columns);

        $data = [];
        foreach ($columns as $c) {
            $data[] = $row[$c];
        }
        fputcsv($out, $data);

        fclose($out);
        exit;
    }

    $columns = ['id', 'week_start_date', 'week_end_date', 'total_sales', 'total_orders', 'total_products_sold', 'created_at', 'updated_at'];
    $values = [];
    foreach ($columns as $c) {
        $values[] = (string)$row[$c];
    }

    $pdf = buildTablePdf($columns, $values, 'Weekly Analytics Export');

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $pdf;
    exit;

} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

function buildSimplePdf(array $lines) {
    $escape = function ($s) {
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace('(', '\\(', $s);
        $s = str_replace(')', '\\)', $s);
        $s = str_replace("\r", '', $s);
        return $s;
    };

    $content = "BT\n/F1 12 Tf\n";
    $x = 50;
    $y = 780;
    $leading = 16;

    // Set absolute start position once
    $content .= sprintf("1 0 0 1 %d %d Tm\n", $x, $y);
    $content .= sprintf("%d TL\n", $leading);

    $first = true;
    foreach ($lines as $line) {
        if (!$first) {
            $content .= "T*\n";
        }
        $first = false;

        $content .= sprintf("(%s) Tj\n", $escape($line));
    }
    $content .= "ET";

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

    $stream = $content;
    $objects[] = "5 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefPos . "\n%%EOF";

    return $pdf;
}
