<?php
include "config.php";
require_once('vendor/autoload.php'); // You'll need to install Stripe via Composer

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$user_id = $_SESSION['user_id'];
$plan = sanitizeInput($_POST['plan']);
$amount = intval($_POST['amount']);

try {
    // Create charge
    $charge = \Stripe\Charge::create([
        'amount' => $amount,
        'currency' => 'usd',
        'source' => $_POST['stripeToken'],
        'description' => 'Webspark ' . ucfirst($plan) . ' Plan - User ID: ' . $user_id
    ]);
    
    if ($charge->status == 'succeeded') {
        // Update user subscription/credits
        if ($plan == 'premium') {
            $stmt = $mysqli->prepare("UPDATE users SET subscription='premium', credits=credits+100 WHERE id=?");
            $stmt->bind_param("i", $user_id);
        } elseif ($plan == 'pro') {
            $stmt = $mysqli->prepare("UPDATE users SET subscription='pro', credits=credits+500 WHERE id=?");
            $stmt->bind_param("i", $user_id);
        } elseif ($plan == 'credits') {
            // Credit package purchase
            $credits_to_add = 0;
            switch ($amount) {
                case 500: $credits_to_add = 50; break;
                case 1500: $credits_to_add = 200; break;
                case 3000: $credits_to_add = 500; break;
                case 5000: $credits_to_add = 1000; break;
            }
            $stmt = $mysqli->prepare("UPDATE users SET credits=credits+? WHERE id=?");
            $stmt->bind_param("ii", $credits_to_add, $user_id);
        }
        
        $stmt->execute();
        
        // Log payment
        $stmt = $mysqli->prepare("INSERT INTO payments (user_id, amount, plan_type, payment_method, stripe_payment_id, status) VALUES (?, ?, ?, 'stripe', ?, 'completed')");
        $amount_decimal = $amount / 100;
        $stmt->bind_param("idss", $user_id, $amount_decimal, $plan, $charge->id);
        $stmt->execute();
        
        // Send confirmation email
        $stmt = $mysqli->prepare("SELECT username, email FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_info = $stmt->get_result()->fetch_assoc();
        
        $email_subject = "Payment Confirmation - Webspark";
        $email_message = "
        <html>
        <body>
            <h2>Payment Successful!</h2>
            <p>Dear {$user_info['username']},</p>
            <p>Your payment has been processed successfully:</p>
            <ul>
                <li>Plan: " . ucfirst($plan) . "</li>
                <li>Amount: $" . ($amount/100) . "</li>
                <li>Transaction ID: {$charge->id}</li>
            </ul>
            <p>Thank you for choosing Webspark!</p>
        </body>
        </html>
        ";
        
        sendEmail($user_info['email'], $email_subject, $email_message);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Payment failed']);
    }
    
} catch(\Stripe\Exception\CardException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch(\Stripe\Exception\RateLimitException $e) {
    echo json_encode(['success' => false, 'error' => 'Too many requests made to the API too quickly']);
} catch(\Stripe\Exception\InvalidRequestException $e) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
} catch(\Stripe\Exception\AuthenticationException $e) {
    echo json_encode(['success' => false, 'error' => 'Authentication failed']);
} catch(\Stripe\Exception\ApiConnectionException $e) {
    echo json_encode(['success' => false, 'error' => 'Network communication failed']);
} catch(\Stripe\Exception\ApiErrorException $e) {
    echo json_encode(['success' => false, 'error' => 'API error occurred']);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
}
?>
