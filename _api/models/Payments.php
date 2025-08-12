<?php 
class Payments
{
    private $conn;
    private $table = "tbl_invoice_payments";

    public $transaction_code;
    public $invoice_id;
    public $payment_mode;
    public $amount;
    public $payment_date;
    public $notes;
    public $client_id;
    public $received_by;
    public $status=1;
    public $debit_account_id;
    public $credit_account_id;

    public function __construct($db)
    {
        
        $this->conn = $db;
    }
    
 public function registerInPayment()
{
    try {
        $this->transaction_code = uniqid(date('YmdHis') . '-') . rand(1000, 9999);

        // 1. Check required fields
        if (
            empty($this->invoice_id) || empty($this->payment_mode) ||
            empty($this->amount) || empty($this->payment_date) ||
            empty($this->notes) || empty($this->received_by)
        ) {
            throw new Exception("All fields are required");
        }

        // 2. Fetch invoice total & client
        $invoiceQuery = "SELECT total_amount, client_id FROM tbl_invoices WHERE invoice_id = :invoice_id LIMIT 1";
        $stmtInvoice = $this->conn->prepare($invoiceQuery);
        $stmtInvoice->bindParam(':invoice_id', $this->invoice_id, PDO::PARAM_INT);
        $stmtInvoice->execute();

        if ($stmtInvoice->rowCount() === 0) {
            throw new Exception("Invoice not found.");
        }

        $invoiceData = $stmtInvoice->fetch(PDO::FETCH_ASSOC);
        $invoiceTotal = (float) $invoiceData['total_amount'];
        $this->client_id = $invoiceData['client_id'];

        // 3. Sum all previous payments
        $paidQuery = "SELECT SUM(amount) AS total_paid FROM {$this->table} WHERE invoice_id = :invoice_id";
        $stmtPaid = $this->conn->prepare($paidQuery);
        $stmtPaid->bindParam(':invoice_id', $this->invoice_id, PDO::PARAM_INT);
        $stmtPaid->execute();
        $paidData = $stmtPaid->fetch(PDO::FETCH_ASSOC);
        $totalPaid = (float) ($paidData['total_paid'] ?? 0);

        // 4. Check remaining balance
        $remainingBalance = $invoiceTotal - $totalPaid;
        if ($this->amount > $remainingBalance) {
            throw new Exception("Payment amount exceeds remaining balance. Remaining: {$remainingBalance}");
        }

        // 5. Start transaction
        $this->conn->beginTransaction();

        // 6. Insert payment
        $query = "INSERT INTO {$this->table} 
            (transaction_code, invoice_id, payment_mode, amount, payment_date, notes, client_id, received_by) 
            VALUES 
            (:transaction_code, :invoice_id, :payment_mode, :amount, :payment_date, :notes, :client_id, :received_by)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':transaction_code', $this->transaction_code);
        $stmt->bindParam(':invoice_id', $this->invoice_id);
        $stmt->bindParam(':payment_mode', $this->payment_mode);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':payment_date', $this->payment_date);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':client_id', $this->client_id);
        $stmt->bindParam(':received_by', $this->received_by);
        $stmt->execute();
        $lastInsertId = $this->conn->lastInsertId();

        // 7. Insert journal entry
        $entry_date = $this->payment_date;
        $description = $this->notes . " Payment received for invoice #" . $this->invoice_id . ", Transaction: " . $this->transaction_code;
        $amount = $this->amount;
        $method_id = $this->payment_mode;
        $reference_id = $lastInsertId;
        $created_at = date('Y-m-d H:i:s');

        $journalQuery = "INSERT INTO tbl_journal_entries 
            (entry_date, description, debit_account_id, amount, method_id, reference_id, created_at) 
            VALUES 
            (:entry_date, :description, :debit_account_id, :amount, :method_id, :reference_id, :created_at)";

        $stmtJournal = $this->conn->prepare($journalQuery);
        $stmtJournal->bindParam(':entry_date', $entry_date);
        $stmtJournal->bindParam(':description', $description);
        $stmtJournal->bindParam(':debit_account_id', $this->debit_account_id);
        $stmtJournal->bindParam(':amount', $amount);
        $stmtJournal->bindParam(':method_id', $method_id);
        $stmtJournal->bindParam(':reference_id', $reference_id);
        $stmtJournal->bindParam(':created_at', $created_at);
        $stmtJournal->execute();

        // 8. Commit
        $updateBalanceQuery = "
    UPDATE tbl_accounts 
    SET balance = balance + :amount 
    WHERE account_id = :account_id
";
$stmtUpdateBalance = $this->conn->prepare($updateBalanceQuery);
$stmtUpdateBalance->bindParam(':amount', $amount, PDO::PARAM_STR);
$stmtUpdateBalance->bindParam(':account_id', $this->debit_account_id, PDO::PARAM_INT);
$stmtUpdateBalance->execute();
        $this->conn->commit();

        return $this->transaction_code;

    } catch (Exception $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        echo "Error: " . $e->getMessage();
        return false;
    }
}

    
 public function registerOutPayment()
{
    try {
        $this->transaction_code = uniqid(date('YmdHis') . '-') . rand(1000, 9999);

        // 1. Check required fields
        if (
            empty($this->invoice_id) || empty($this->payment_mode) ||
            empty($this->amount) || empty($this->payment_date) ||
            empty($this->notes) || empty($this->received_by)
        ) {
            throw new Exception("All fields are required");
        }

        // 2. Fetch invoice total & client
        $invoiceQuery = "SELECT total_amount, client_id FROM tbl_invoices WHERE invoice_id = :invoice_id LIMIT 1";
        $stmtInvoice = $this->conn->prepare($invoiceQuery);
        $stmtInvoice->bindParam(':invoice_id', $this->invoice_id, PDO::PARAM_INT);
        $stmtInvoice->execute();

        if ($stmtInvoice->rowCount() === 0) {
            throw new Exception("Invoice not found.");
        }

        $invoiceData = $stmtInvoice->fetch(PDO::FETCH_ASSOC);
        $invoiceTotal = (float) $invoiceData['total_amount'];
        $this->client_id = $invoiceData['client_id'];

        // 3. Sum all previous payments
        $paidQuery = "SELECT SUM(amount) AS total_paid FROM {$this->table} WHERE invoice_id = :invoice_id";
        $stmtPaid = $this->conn->prepare($paidQuery);
        $stmtPaid->bindParam(':invoice_id', $this->invoice_id, PDO::PARAM_INT);
        $stmtPaid->execute();
        $paidData = $stmtPaid->fetch(PDO::FETCH_ASSOC);
        $totalPaid = (float) ($paidData['total_paid'] ?? 0);

        // 4. Check remaining balance
        $remainingBalance = $invoiceTotal - $totalPaid;
        if ($this->amount > $remainingBalance) {
            throw new Exception("Payment amount exceeds remaining balance. Remaining: {$remainingBalance}");
        }

        // 5. Start transaction
        $this->conn->beginTransaction();

        // 6. Insert payment
        $query = "INSERT INTO {$this->table} 
            (transaction_code, invoice_id, payment_mode, amount, payment_date, notes, client_id, received_by) 
            VALUES 
            (:transaction_code, :invoice_id, :payment_mode, :amount, :payment_date, :notes, :client_id, :received_by)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':transaction_code', $this->transaction_code);
        $stmt->bindParam(':invoice_id', $this->invoice_id);
        $stmt->bindParam(':payment_mode', $this->payment_mode);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':payment_date', $this->payment_date);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':client_id', $this->client_id);
        $stmt->bindParam(':received_by', $this->received_by);
        $stmt->execute();
        $lastInsertId = $this->conn->lastInsertId();

        // 7. Insert journal entry
        $entry_date = $this->payment_date;
        $description = $this->notes . " Payment received for invoice #" . $this->invoice_id . ", Transaction: " . $this->transaction_code;
        $amount = $this->amount;
        $method_id = $this->payment_mode;
        $reference_id = $lastInsertId;
        $created_at = date('Y-m-d H:i:s');

        $journalQuery = "INSERT INTO tbl_journal_entries 
            (entry_date, description, credit_account_id, amount, method_id, reference_id, created_at) 
            VALUES 
            (:entry_date, :description, :credit_account_id, :amount, :method_id, :reference_id, :created_at)";

        $stmtJournal = $this->conn->prepare($journalQuery);
        $stmtJournal->bindParam(':entry_date', $entry_date);
        $stmtJournal->bindParam(':description', $description);
        $stmtJournal->bindParam(':credit_account_id', $this->credit_account_id);
        $stmtJournal->bindParam(':amount', $amount);
        $stmtJournal->bindParam(':method_id', $method_id);
        $stmtJournal->bindParam(':reference_id', $reference_id);
        $stmtJournal->bindParam(':created_at', $created_at);
        $stmtJournal->execute();

        // update balance
        $updateBalanceQuery = "
            UPDATE tbl_accounts 
            SET balance = balance - :amount 
            WHERE account_id = :account_id
        ";
        $stmtUpdateBalance = $this->conn->prepare($updateBalanceQuery);
        $stmtUpdateBalance->bindParam(':amount', $amount, PDO::PARAM_STR);
        $stmtUpdateBalance->bindParam(':account_id', $this->credit_account_id, PDO::PARAM_INT);
        $stmtUpdateBalance->execute();

        // 8. Commit
        $this->conn->commit();

        return $this->transaction_code;

    } catch (Exception $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        echo "Error: " . $e->getMessage();
        return false;
    }
}






    public function getAllPayments()
    {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC limit 100";
       
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaymentById($center_id)
    {
        $query = "SELECT * FROM {$this->table} WHERE center_id = :center_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':center_id', $center_id);
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            return null; // No payment found for the given center_id
        }
        // Fetch all payments for the given center_id
    

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}