<!DOCTYPE html>
<html>
<head>
    <title>Invoice Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Invoice <?= esc($invoiceNumber) ?></h2>
        </div>
        
        <p>Dear <?= esc($customerName) ?>,</p>
        
        <p>Your invoice for order <?= esc($referenceNumber) ?> is now available.</p>
        
        <?php if (!empty($message)): ?>
        <p><strong>Message from our team:</strong><br>
        <?= nl2br(esc($message)) ?></p>
        <?php endif; ?>
        
        <p>You can view your invoice online by clicking the link below:</p>
        
        <p><a href="<?= esc($invoiceLink) ?>" style="display: inline-block; padding: 10px 20px; background-color: #4e73df; color: white; text-decoration: none; border-radius: 4px;">
            View Invoice <?= esc($invoiceNumber) ?>
        </a></p>
        
        <p>The invoice is also attached to this email for your records.</p>
        
        <div class="footer">
            <p>If you have any questions about this invoice, please contact our support team.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>