<?php   
class Expense {

    private $conn;
    private $table = "tbl_expenses";

    public $expense_category;
    public $paid_amount;
    public $due_date;
    public $description;
    public $consumer_id;
    public $payment_mode;
    public $recorded_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create(){

           try {
        // Validate required fields
        if (
            empty($this->expense_category) || 
            empty($this->description)
        ) {
            return "Missing required fields: expense_category, description";
        }

        // Prepare and execute insert query

       $query = "INSERT INTO {$this->table} 
            (expense_category, amount, payment_date, description, consumer_id, payment_mode, recorded_by) 
            VALUES 
            (:expense_category, :paid_amount, :due_date, :description, :consumer_id, :payment_mode, :recorded_by)";

       $stmt = $this->conn->prepare($query);
       $stmt->bindParam(":expense_category", $this->expense_category);
       $stmt->bindParam(":paid_amount", $this->paid_amount);
       $stmt->bindParam(":due_date", $this->due_date);
       $stmt->bindParam(":description", $this->description);
       $stmt->bindParam(":consumer_id", $this->consumer_id);
       $stmt->bindParam(":payment_mode", $this->payment_mode);
       $stmt->bindParam(":recorded_by", $this->recorded_by);

       if ($stmt->execute()) {
           return true;
       } else {
           return "Database error during insert.";
       }
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
    }


    public function update($id) {
    try {
        

        // Perform update
        $query = "UPDATE {$this->table} SET 
                    expense_category = :expense_category,
                    amount = :amount,
                    payment_date = :payment_date,
                    description = :description,
                    consumer_id = :consumer_id,
                    payment_mode = :payment_mode,
                    recorded_by = :recorded_by
                  WHERE expense_id = :expense_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':expense_category', $this->expense_category);
        $stmt->bindParam(':amount', $this->paid_amount);
        $stmt->bindParam(':payment_date', $this->due_date);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':consumer_id', $this->consumer_id);
        $stmt->bindParam(':payment_mode', $this->payment_mode);
        $stmt->bindParam(':recorded_by', $this->recorded_by);
        $stmt->bindParam(':expense_id', $id);

        if ($stmt->execute()) {
            return true;
        } else {
            return "Failed to execute update query.";
        }

    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}
public function delete($id) {
    try {
        $query = "DELETE FROM {$this->table} WHERE expense_id = :id";   
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return true;
        } else {
            return "Failed to execute delete query.";
        }

    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}

public function getAll() {
    try {
        $query = "SELECT tbl_expenses.*,expense_categories.category_name,tbl_users.first_name,payment_methods.method_name FROM {$this->table}
        INNER JOIN tbl_users ON tbl_expenses.consumer_id = tbl_users.acc_id
        INNER JOIN expense_categories ON tbl_expenses.expense_category = expense_categories.category_id
        INNER JOIN payment_methods ON tbl_expenses.payment_mode = payment_methods.method_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }   catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}
}
?>
