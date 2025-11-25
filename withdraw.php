<?php
require_once 'config.php';
requireCustomer();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user information and balance
$stmt = $conn->prepare("SELECT username, email, balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get total pending withdrawals
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as pending_amount 
    FROM withdrawals 
    WHERE user_id = ? AND status = 'pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_withdrawals = $stmt->get_result()->fetch_assoc()['pending_amount'];
$stmt->close();

// Get total approved/completed withdrawals
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_withdrawn 
    FROM withdrawals 
    WHERE user_id = ? AND status IN ('approved', 'completed')
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_withdrawn = $stmt->get_result()->fetch_assoc()['total_withdrawn'];
$stmt->close();

// Get recent withdrawal history (last 5)
$stmt = $conn->prepare("
    SELECT * FROM withdrawals 
    WHERE user_id = ? 
    ORDER BY id DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_withdrawals = $stmt->get_result();
$stmt->close();

$conn->close();

// Calculate available balance (current balance - pending withdrawals)
$available_balance = $user['balance'] - $pending_withdrawals;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Withdrawal - EarnCash</title>
    <link rel="stylesheet" href="assets/css/withdraw.css">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-container">
            <h1>EarnCash</h1>
            <div class="nav-links">
                <a href="customer_dashboard.php">DASHBOARD</a>
                
                <a href="withdrawal_history.php">WITHDRAWAL HISTORY</a>
                <a href="index.html">HOME</a>
               
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="container">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <h2>💰 Request Withdrawal</h2>
                <p>Withdraw your earned money securely</p>
            </div>

            <!-- BALANCE OVERVIEW -->
            <div class="balance-overview">
                <div class="balance-card available">
                    <div class="balance-icon">💵</div>
                    <div class="balance-info">
                        <h3>Available Balance</h3>
                        <p class="balance-amount" id="availableBalance">$<?php echo number_format($available_balance, 2); ?></p>
                        <span class="balance-note">Ready to withdraw</span>
                    </div>
                </div>
                <div class="balance-card pending">
                    <div class="balance-icon">⏳</div>
                    <div class="balance-info">
                        <h3>Pending Withdrawals</h3>
                        <p class="balance-amount">$<?php echo number_format($pending_withdrawals, 2); ?></p>
                        <span class="balance-note">Being processed</span>
                    </div>
                </div>
                <div class="balance-card total">
                    <div class="balance-icon">✅</div>
                    <div class="balance-info">
                        <h3>Total Withdrawn</h3>
                        <p class="balance-amount">$<?php echo number_format($total_withdrawn, 2); ?></p>
                        <span class="balance-note">All time</span>
                    </div>
                </div>
            </div>

            <!-- CONTENT GRID -->
            <div class="content-grid">
                <!-- WITHDRAWAL FORM -->
                <div class="card withdrawal-form-card">
                    <div class="card-header">
                        <h3>New Withdrawal Request</h3>
                    </div>
                    <div class="card-body">
                        <form id="withdrawalForm">
                            <!-- Amount Input -->
                            <div class="form-group">
                                <label for="amount">Withdrawal Amount *</label>
                                <div class="input-with-icon">
                                    <span class="input-icon">$</span>
                                    <input 
                                        type="number" 
                                        id="amount" 
                                        name="amount" 
                                        step="0.01" 
                                        min="1.00" 
                                        max="<?php echo $available_balance; ?>" 
                                        placeholder="Enter amount"
                                        required
                                    >
                                </div>
                                <div class="form-note">
                                    <span>Minimum: $1.00</span>
                                    <span>Maximum: $<?php echo number_format($available_balance, 2); ?></span>
                                </div>
                                <div id="amountError" class="error-message"></div>
                            </div>

                            <!-- Payment Method -->
                            <div class="form-group">
                                <label for="paymentMethod">Payment Method *</label>
                                <select id="paymentMethod" name="payment_method" required>
                                    <option value="">Select payment method</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    
                                </select>
                            </div>

                            <!-- Payment Details (Dynamic) -->
                            <div id="paymentDetailsContainer"></div>

                            <!-- Terms and Conditions -->
                            <div class="form-group checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="agreeTerms" required>
                                    <span>I agree to the <a href="#">withdrawal terms and conditions</a></span>
                                </label>
                            </div>

                            <!-- Message Display -->
                            <div id="messageBox" class="message" style="display: none;"></div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-submit" id="submitBtn">
                                <span class="btn-icon">💸</span>
                                Submit Withdrawal Request
                            </button>
                        </form>
                    </div>
                </div>

                <!-- WITHDRAWAL INFORMATION -->
                <div class="card info-card">
                    <div class="card-header">
                        <h3>Withdrawal Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-section">
                            <div class="info-icon">ℹ️</div>
                            <div class="info-content">
                                <h4>Processing Time</h4>
                                <p>Withdrawals are typically processed within 2-5 business days.</p>
                            </div>
                        </div>
                        <div class="info-section">
                            <div class="info-icon">💳</div>
                            <div class="info-content">
                                <h4>Payment Methods</h4>
                                <ul>
                                    <li><strong>PayPal:</strong> Instant to 24 hours</li>
                                    <li><strong>Bank Transfer:</strong> 3-5 business days</li>
                                    <li><strong>Mobile Money:</strong> 1-2 business days</li>
                                    <li><strong>Cryptocurrency:</strong> 24-48 hours</li>
                                </ul>
                            </div>
                        </div>
                        <div class="info-section">
                            <div class="info-icon">📋</div>
                            <div class="info-content">
                                <h4>Requirements</h4>
                                <ul>
                                    <li>Minimum withdrawal: $1.00</li>
                                    <li>Maximum per request: Your available balance</li>
                                    <li>Valid payment details required</li>
                                    <li>Account must be verified</li>
                                </ul>
                            </div>
                        </div>
                        <div class="info-section">
                            <div class="info-icon">⚠️</div>
                            <div class="info-content">
                                <h4>Important Notes</h4>
                                <ul>
                                    <li>Double-check your payment details</li>
                                    <li>Fees may apply for certain methods</li>
                                    <li>One pending withdrawal at a time</li>
                                    <li>Contact support for any issues</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RECENT WITHDRAWALS -->
                <div class="card history-card">
                    <div class="card-header">
                        <h3>Recent Withdrawal Requests</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_withdrawals->num_rows > 0): ?>
                            <div class="withdrawal-history">
                                <?php while ($withdrawal = $recent_withdrawals->fetch_assoc()): ?>
                                <div class="history-item">
                                    <div class="history-info">
                                        <div class="history-amount">$<?php echo number_format($withdrawal['amount'], 2); ?></div>
                                        <div class="history-method"><?php echo ucwords(str_replace('_', ' ', $withdrawal['payment_method'])); ?></div>
                                        <div class="history-date"><?php
                                            // Use request_date if available; fall back to created_at or other date fields if present
                                            $dateField = null;
                                            if (!empty($withdrawal['request_date'])) {
                                                $dateField = $withdrawal['request_date'];
                                            } elseif (!empty($withdrawal['created_at'])) {
                                                $dateField = $withdrawal['created_at'];
                                            } elseif (!empty($withdrawal['request_time'])) {
                                                $dateField = $withdrawal['request_time'];
                                            }
                                            echo $dateField ? date('M d, Y', strtotime($dateField)) : '—';
                                        ?></div>
                                    </div>
                                    <div class="history-status">
                                        <span class="status-badge <?php echo $withdrawal['status']; ?>">
                                            <?php echo ucfirst($withdrawal['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-history">
                                <div class="no-history-icon">📭</div>
                                <p>No withdrawal requests yet</p>
                                <span>Make your first withdrawal request above</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>💰 EarnCash</h3>
                    <p>Earn money by watching advertisements. Simple, fast, and reliable.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="contact.html">Help Center</a></li>
                        <li><a href="about.html">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="contact.html">FAQ</a></li>
                    </ul>
                </div>
            </div>
            
        </div>
    </footer>

    <script>
        const availableBalance = <?php echo $available_balance; ?>;
        const userId = <?php echo $user_id; ?>;
    </script>
    <script src="assets/js/withdraw.js"></script>
</body>
</html>