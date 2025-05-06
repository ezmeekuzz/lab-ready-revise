<!DOCTYPE html>
<html>
<head>
    <title>Your PO Submission Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 15px; text-align: center; }
        .content { padding: 20px; background-color: #fff; }
        .footer { margin-top: 20px; padding: 10px; text-align: center; font-size: 12px; color: #777; }
        .details { margin: 15px 0; }
        .detail-row { margin-bottom: 10px; }
        .label { font-weight: bold; }
        .thank-you { font-size: 18px; color: #2a6496; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Purchase Order Submission Confirmation</h2>
        </div>
        
        <div class="content">
            <p class="thank-you">Thank you for your purchase order submission!</p>
            
            <p>Dear <?= $user['fullname'] ?>,</p>
            
            <p>We've received your purchase order for the following quotation:</p>
            
            <div class="details">
                <h3>Order Details</h3>
                <div class="detail-row">
                    <span class="label">Reference Number:</span> <?= $quotation['reference_number'] ?>
                </div>
                <div class="detail-row">
                    <span class="label">Product/Service:</span> <?= $quotation['quotation_name'] ?>
                </div>
                <div class="detail-row">
                    <span class="label">Amount:</span> $<?= number_format($quotation['price'], 2) ?>
                </div>
                
                <h3>Shipping Information</h3>
                <div class="detail-row">
                    <span class="label">Address:</span> <?= $shipping['address'] ?>
                </div>
                <div class="detail-row">
                    <span class="label">City:</span> <?= $shipping['city'] ?>
                </div>
                <div class="detail-row">
                    <span class="label">State:</span> <?= $shipping['state'] ?>
                </div>
                <div class="detail-row">
                    <span class="label">Zip Code:</span> <?= $shipping['zipcode'] ?>
                </div>
                <div class="detail-row">
                    <span class="label">Phone:</span> <?= $shipping['phone'] ?>
                </div>
                
                <h3>Submission Details</h3>
                <div class="detail-row">
                    <span class="label">Submitted At:</span> <?= $date ?>
                </div>
            </div>
            
            <p>Our team will review your purchase order and process your request. You'll receive another notification once your order has been processed.</p>
            
            <p>If you have any questions about your order, please contact our support team at <?= env('app.supportEmail') ?> or call us at <?= env('app.supportPhone') ?>.</p>
        </div>
        
        <div class="footer">
            <p>This is an automated confirmation. Please do not reply to this email.</p>
            <p>&copy; <?= date('Y') ?> <?= env('app.name') ?></p>
        </div>
    </div>
</body>
</html>