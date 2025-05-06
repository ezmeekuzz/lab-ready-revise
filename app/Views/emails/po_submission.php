<!DOCTYPE html>
<html>
<head>
    <title>New PO Submission</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 15px; text-align: center; }
        .content { padding: 20px; background-color: #fff; }
        .footer { margin-top: 20px; padding: 10px; text-align: center; font-size: 12px; color: #777; }
        .details { margin: 15px 0; }
        .detail-row { margin-bottom: 10px; }
        .label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Purchase Order Submission</h2>
        </div>
        
        <div class="content">
            <p>Hello Admin,</p>
            <p>A new purchase order has been submitted with the following details:</p>
            
            <div class="details">
                <h3>Quotation Information</h3>
                <div class="detail-row">
                    <span class="label">Reference Number:</span> <?= esc($quotation['reference_number'] ?? 'N/A') ?>
                </div>
                <div class="detail-row">
                    <span class="label">Product/Service:</span> <?= esc($quotation['quotation_name'] ?? 'N/A') ?>
                </div>
                <div class="detail-row">
                    <span class="label">Amount:</span> $<?= isset($quotation['price']) ? number_format($quotation['price'], 2) : '0.00' ?>
                </div>
                
                <h3>Customer Information</h3>
                <div class="detail-row">
                    <span class="label">Name:</span> <?= esc($user['fullname'] ?? '') ?>
                </div>
                <div class="detail-row">
                    <span class="label">Email:</span> <?= esc($user['email'] ?? 'N/A') ?>
                </div>
                <div class="detail-row">
                    <span class="label">Company:</span> <?= esc($user['company_name'] ?? 'N/A') ?>
                </div>
                
                <h3>Shipping Information</h3>
                <div class="detail-row">
                    <span class="label">Address:</span> <?= esc($shipping['address'] ?? 'N/A') ?>
                </div>
                <div class="detail-row">
                    <span class="label">City:</span> <?= esc($shipping['city'] ?? 'N/A') ?>
                </div>
                <div class="detail-row">
                    <span class="label">State:</span> <?= esc($shipping['state'] ?? 'N/A') ?>
                </div>
                <div class="detail-row">
                    <span class="label">Zip Code:</span> <?= esc($shipping['zipcode'] ?? 'N/A') ?>
                </div>
                <div class="detail-row">
                    <span class="label">Phone:</span> <?= esc($shipping['phone'] ?? 'N/A') ?>
                </div>
                
                <h3>Submission Details</h3>
                <div class="detail-row">
                    <span class="label">Submitted At:</span> <?= esc($date ?? date('F j, Y g:i a')) ?>
                </div>
            </div>
            
            <p>The PO document is attached to this email. Please review and process this order accordingly.</p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification. Please do not reply to this email.</p>
            <p>&copy; <?= date('Y') ?> <?= env('app.name') ?></p>
        </div>
    </div>
</body>
</html>