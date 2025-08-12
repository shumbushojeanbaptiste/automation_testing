<?php
class Roles {
    private $conn;
    private $table = "tbl_roles";

    public $role_name;
   

    public function __construct($db) {
        $this->conn = $db;
    }


    public function getAll() {
        try {
            $query = "SELECT * FROM {$this->table}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }
    



}


class PaymentMethod {
    private $conn;
    private $table = "payment_methods";

    public $full_name;
    public $short_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        try {
            $query = "SELECT * FROM {$this->table}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }

    
}


class LicensePermitted {
    private $conn;
    private $table = "tbl_licence_permitted";

    public $school_id;
    public $center_id;
    public $license_id;
    public $status = 1; // Default status is active

    public function __construct($db) {
        $this->conn = $db;
    }

   public function create() {
    try {
        // Validate required fields
        if (empty($this->license_id) || empty($this->center_id)) {
            return "Missing required fields: license_id, center_id.";
        }
        if (empty($this->school_id)) {
            return "Missing required field: school_id.";
        }

        // Check if the combination already exists
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} 
                       WHERE school_id = :school_id AND center_id = :center_id AND license_id = :license_id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":school_id", $this->school_id);
        $checkStmt->bindParam(":center_id", $this->center_id);
        $checkStmt->bindParam(":license_id", $this->license_id);
        $checkStmt->execute(); 
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            return "already exists.";
        }

        // Insert new record
        $query = "INSERT INTO {$this->table} 
                  (school_id, center_id, license_id, status) 
                  VALUES (:school_id, :center_id, :license_id, :status)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":school_id", $this->school_id);
        $stmt->bindParam(":center_id", $this->center_id);
        $stmt->bindParam(":license_id", $this->license_id);
        $stmt->bindParam(":status", $this->status); 

        if ($stmt->execute()) {
            return true;
        } else {
            return "Database error during insert.";
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
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }
  
 
}
class Classification {
    private $conn;
    private $table = "financial_classification";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        try {
            $query = "SELECT * FROM {$this->table}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }

}

class ExpenseCategory {
    private $conn;
    private $table = "expense_categories";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        try {
            $query = "SELECT * FROM {$this->table}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }

}

class InvoiceType {
    private $conn;
    private $table = "invoice_types";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        try {
            $query = "SELECT * FROM {$this->table}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }

}

class InvoiceStatus {
    private $conn;
    private $table = "invoice_statuses";            

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        try {
            $query = "SELECT * FROM {$this->table}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }

}
