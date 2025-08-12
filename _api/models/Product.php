<?php   
class Product {

    private $conn;
    private $table = "tbl_products";

    public $product_name;
    public $description;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create(){

           try {
        // Validate required fields
        if (
            empty($this->product_name) || 
            empty($this->description)
        ) {
            return "Missing required fields: product_name, description  ";
        }

        // Check if product_name already exists
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} WHERE product_name = :product_name";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":product_name", $this->product_name);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            return "$this->product_name already exists.";
        }

        // Prepare and execute insert query

       
        $query = "INSERT INTO {$this->table} 
            (product_name, description) 
            VALUES 
            (:product_name, :description)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":product_name", $this->product_name);
        $stmt->bindParam(":description", $this->description);
       

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
        // Check for duplicate email (excluding current user)
        $checkQuery = "SELECT COUNT(product_name) FROM {$this->table} WHERE product_name = :product_name AND product_id != :product_id";
         // Prepare the query
        $checkStmt = $this->conn->prepare($checkQuery);
        // Bind parameters
        $checkStmt->bindParam(':product_name', $this->product_name);
        $checkStmt->bindParam(':product_id', $id);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            return "product_name already exists.";
        }

        // Perform update
        $query = "UPDATE {$this->table} SET 
                    product_name = :product_name,
                    description = :description
                  WHERE product_id = :product_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_name', $this->product_name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':product_id', $id);

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
        $query = "DELETE FROM {$this->table} WHERE product_id = :id";   
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
        $query = "SELECT * FROM {$this->table}";    
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }   catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}
}
?>
<?php

?>