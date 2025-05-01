<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 15px; text-align: center; }
        .content { padding: 20px; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .label { font-weight: bold; color: #555; }
        .value { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Purchase Order Approval Request</h2>
            <p>Request Date: <?= esc($date) ?></p>
        </div>
        
        <div class="content">
            <h3>Customer Information</h3>
            <div class="value">
                <span class="label">Name:</span> <?= esc($user['fullname'] ?? '') ?>
            </div>
            <div class="value">
                <span class="label">Company:</span> <?= esc($user['companyname'] ?? 'N/A') ?>
            </div>
            <div class="value">
                <span class="label">Email:</span> <?= esc($user['email'] ?? '') ?>
            </div>
            <div class="value">
                <span class="label">Phone:</span> <?= esc($user['phonenumber'] ?? 'N/A') ?>
            </div>
            
            <h3>Quotation Details</h3>
            <div class="value">
                <span class="label">Reference #:</span> <?= esc($quotation['reference_number']) ?>
            </div>
            <div class="value">
                <span class="label">Description:</span> <?= esc($quotation['quotation_name']) ?>
            </div>
            <div class="value">
                <span class="label">Amount:</span> $<?= number_format($quotation['price'], 2) ?>
            </div>
            
            <?php if (!empty($notes)): ?>
            <h3>Additional Notes</h3>
            <div class="value">
                <?= nl2br(esc($notes)) ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>Please review this request at your earliest convenience.</p>
        </div>
    </div>
</body>
</html>