<?php
require 'config.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoiceIds = json_decode($_POST['invoice_ids'], true);
    
    if (empty($invoiceIds)) {
        die('No invoices selected');
    }
    
    if (count($invoiceIds) === 1) {
        // Download single invoice
        $invoiceId = $invoiceIds[0];
        generateInvoicePDF($invoiceId);
    } else {
        // Create ZIP of multiple invoices
        $zip = new ZipArchive();
        $zipName = 'invoices_' . date('Ymd_His') . '.zip';
        
        if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
            foreach ($invoiceIds as $id) {
                $pdfContent = generateInvoicePDF($id, false);
                $zip->addFromString("invoice_{$id}.pdf", $pdfContent);
            }
            $zip->close();
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipName));
            readfile($zipName);
            unlink($zipName);
            exit;
        } else {
            die('Could not create ZIP file');
        }
    }
}
// Handle single invoice download via GET
if (isset($_GET['id'])) {
    $invoiceId = intval($_GET['id']);
    generateInvoicePDF($invoiceId);
    exit;
}

// Handle batch downloads via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... existing batch download code ...
}

function generateInvoicePDF($invoiceId, $output = true) {
    global $pdo;
    
    $invoice = getInvoiceDetails($pdo, $invoiceId);
    if (!$invoice) {
        return "Invoice not found";
    }
    
    // Calculate total paid
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ?");
    $stmt->execute([$invoiceId]);
    $total_paid = $stmt->fetchColumn();
    $balance = $invoice['total_amount'] - $total_paid;
    
    // Render template
    $standalone = true;
    ob_start();
    include 'invoice_template.php';
    $html = ob_get_clean();
    
    // Generate PDF
    require_once 'vendor/autoload.php';
    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    
    if ($output) {
        $dompdf->stream("invoice-$invoiceId.pdf");
        exit;
    } else {
        return $dompdf->output();
    }
}