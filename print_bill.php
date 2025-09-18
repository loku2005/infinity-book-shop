<?php
require_once 'includes/functions.php';

// Get bill ID from URL
$bill_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$bill_id) {
    echo "Invalid bill ID";
    exit;
}

// Get bill details
$bill = getBillById($bill_id);

if (!$bill) {
    echo "Bill not found";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo $bill['bill_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            color: #333;
        }
        .invoice-header { 
            text-align: center; 
            border-bottom: 3px solid #007bff; 
            padding-bottom: 20px; 
            margin-bottom: 30px; 
        }
        .company-name { 
            font-size: 32px; 
            font-weight: bold; 
            color: #007bff; 
            margin-bottom: 5px;
        }
        .company-tagline {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .company-contact {
            color: #666;
            font-size: 14px;
        }
        .invoice-details, .customer-details { 
            margin: 25px 0; 
        }
        .invoice-details h4, .customer-details h4 {
            color: #007bff;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 30px 0; 
            font-size: 14px;
        }
        th { 
            background-color: #007bff;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
        }
        td { 
            border-bottom: 1px solid #dee2e6;
            padding: 10px 8px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { 
            font-weight: bold; 
            font-size: 16px;
            background-color: #f8f9fa;
        }
        .total-row td {
            border-top: 2px solid #007bff;
            padding: 15px 8px;
        }
        .invoice-footer { 
            margin-top: 40px; 
            text-align: center; 
            border-top: 2px solid #e9ecef; 
            padding-top: 20px; 
            color: #666;
        }
        .thank-you {
            font-size: 18px;
            color: #007bff;
            font-weight: 600;
            margin-bottom: 10px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <button class="btn btn-primary no-print print-btn" onclick="window.print()">
        <i class="fas fa-print me-2"></i>Print Invoice
    </button>

    <div class="invoice-header">
        <div class="company-name">INFINITY BOOKSHOP</div>
        <div class="company-tagline">Educational Books & Stationery</div>
        <div class="company-contact">
            Contact: +94 11 234 5678 | Email: info@infinitybookshop.lk<br>
            Address: 123 Education Lane, Colombo 07, Sri Lanka
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="invoice-details">
                <h4>INVOICE DETAILS</h4>
                <div class="info-row">
                    <span class="info-label">Invoice Number:</span>
                    <strong><?php echo $bill['bill_number']; ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <?php echo date('F d, Y', strtotime($bill['created_at'])); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Time:</span>
                    <?php echo date('h:i A', strtotime($bill['created_at'])); ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="customer-details">
                <h4>BILL TO</h4>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <strong><?php echo htmlspecialchars($bill['customer_name']); ?></strong>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact:</span>
                    <?php echo htmlspecialchars($bill['customer_contact']); ?>
                </div>
                <?php if ($bill['customer_email']): ?>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <?php echo htmlspecialchars($bill['customer_email']); ?>
                </div>
                <?php endif; ?>
                <?php if ($bill['customer_address']): ?>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <?php echo htmlspecialchars($bill['customer_address']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Item Description</th>
                <th style="width: 15%;" class="text-center">Quantity</th>
                <th style="width: 17.5%;" class="text-right">Unit Price</th>
                <th style="width: 17.5%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $item_number = 1;
            foreach ($bill['items'] as $item): 
            ?>
            <tr>
                <td class="text-center"><?php echo $item_number++; ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td class="text-center"><?php echo $item['quantity']; ?></td>
                <td class="text-right"><?php echo formatCurrency($item['price']); ?></td>
                <td class="text-right"><?php echo formatCurrency($item['subtotal']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>TOTAL AMOUNT:</strong></td>
                <td class="text-right"><strong><?php echo formatCurrency($bill['total']); ?></strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="invoice-footer">
        <div class="thank-you">Thank you for your business!</div>
        <p><em>This is a computer generated invoice and does not require a signature.</em></p>
        <p><small>For any queries regarding this invoice, please contact us at +94 11 234 5678</small></p>
    </div>

    <script>
        // Auto print when page loads (optional)
        // window.onload = function() { window.print(); }
        
        // Close window after printing (optional)
        window.onafterprint = function() {
            // window.close();
        }
    </script>
</body>
</html>