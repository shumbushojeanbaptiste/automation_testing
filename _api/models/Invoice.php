<?php 
class Invoice
{
    private $conn;
    private $table = "tbl_invoices";

    public $invoice_number;
    public $invoice_date;
    public $due_date;
    public $total_amount;
    public $notes;
    public $client_id;
    public $invoice_type;
    public $status=1;

    public function __construct($db)
    {
        $this->conn = $db;
    }

   public function create()
{
    // Step 1: Check for duplicate invoice_number
    $checkQuery = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE invoice_number = :invoice_number";
    $checkStmt = $this->conn->prepare($checkQuery);
    $checkStmt->bindParam(':invoice_number', $this->invoice_number);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        // Duplicate invoice_number found
        return false;
    }

    // Step 2: Proceed with insert if not duplicate
    $query = "INSERT INTO " . $this->table . " (invoice_number, invoice_date, due_date, total_amount, notes, client_id, invoice_type, status) 
              VALUES (:invoice_number, :invoice_date, :due_date, :total_amount, :notes, :client_id, :invoice_type, :status)";
    
    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(':invoice_number', $this->invoice_number);
    $stmt->bindParam(':invoice_date', $this->invoice_date);
    $stmt->bindParam(':due_date', $this->due_date);
    $stmt->bindParam(':total_amount', $this->total_amount);
    $stmt->bindParam(':notes', $this->notes);
    $stmt->bindParam(':client_id', $this->client_id);
    $stmt->bindParam(':invoice_type', $this->invoice_type);
    $stmt->bindParam(':status', $this->status); 

    if ($stmt->execute()) {
        return $this->conn->lastInsertId();
    }

    return false;
}
public function getById() {
    $query = "SELECT * FROM " . $this->table . " WHERE invoice_number = :invoice_number";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':invoice_number', $this->invoice_number);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}
public function update() {
    $query = "UPDATE " . $this->table . " SET 
              invoice_date = :invoice_date,
              due_date = :due_date,
              total_amount = :total_amount,
              notes = :notes,
              client_id = :client_id,
              invoice_type = :invoice_type,
              status = :status
              WHERE invoice_number = :invoice_number";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(':invoice_date', $this->invoice_date);
    $stmt->bindParam(':due_date', $this->due_date);
    $stmt->bindParam(':total_amount', $this->total_amount);
    $stmt->bindParam(':notes', $this->notes);
    $stmt->bindParam(':client_id', $this->client_id);
    $stmt->bindParam(':invoice_type', $this->invoice_type);
    $stmt->bindParam(':status', $this->status);
    $stmt->bindParam(':invoice_number', $this->invoice_number);

    if ($stmt->execute()) {
        return true;
    }

    return false;
}
public function delete() {
    $query = "DELETE FROM " . $this->table . " WHERE invoice_number = :invoice_number";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':invoice_number', $this->invoice_number);
    if ($stmt->execute()) {
        return true;
    }
    return false;
}
public function getAll() {
    $query = "SELECT * FROM " . $this->table;
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);

}
}
class ItemInvoice
{
    private $conn;
    private $table = "tbl_invoice_items";   
    public $item_id;
    public $invoice_number;
    public $quantity;
    public $unit_price;
    public $total_price;
    public $product_id;

    public function __construct($db)
    {
        $this->conn = $db;
    }
    public function create() {
       // here try to check error of foreign key  to
        $query = "INSERT INTO " . $this->table . "(invoice_id, product_id, quantity, unit_price, tax_rate, discount, invoice_type) 
                  VALUES (:invoice_id, :product_id, :quantity, :unit_price, :tax_rate, :discount, :invoice_type)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':invoice_id', $this->invoice_number);
        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':unit_price', $this->unit_price);
        $stmt->bindParam(':tax_rate', $this->tax_rate);
        $stmt->bindParam(':discount', $this->discount);
        $stmt->bindParam(':invoice_type', $this->invoice_type); 
        if ($stmt->execute()) {
            return $this->conn->lastInsertId(); 
        }
        return false;
    }
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " WHERE invoice_id = :invoice_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':invoice_id', $this->invoice_number);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}