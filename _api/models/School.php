<?php
class School {
    private $conn;
    private $table = "tbl_driving_schools";

    public $school_full_name;
    public $school_short_name;
    public $referal_code;
    public $address;
    public $phone;
    public $email;
    public $id; // Assuming ID is used for updates and deletes
    
    public $status = "1"; // default active status

    public function __construct($db) {
        $this->conn = $db;
    }

public function create() {
    try {
        // Step 1: Validate required fields
        if (empty($this->school_full_name) || empty($this->school_short_name) || empty($this->phone) || empty($this->email)) {
            return "Missing required fields: school_full_name, school_short_name, phone, or email.";
        }

        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format.";
        }

        // Step 2: Check if school name or email already exists
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} WHERE school_full_name = :school_full_name  OR email = :email";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':school_full_name', $this->school_full_name);
        $checkStmt->bindParam(':email', $this->email);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn() > 0) {
            return "School full name or email already exists.";
        }

        // Step 3: Generate referal_code
        $yearPrefix = date("y"); // e.g., "25" for 2025
        $codePrefix = "DS" . $yearPrefix;

        $codeQuery = "SELECT referal_code FROM {$this->table} WHERE referal_code LIKE :prefix ORDER BY referal_code DESC LIMIT 1";
        $codeStmt = $this->conn->prepare($codeQuery);
        $likePattern = $codePrefix . "%";
        $codeStmt->bindParam(":prefix", $likePattern);
        $codeStmt->execute();
        $lastCode = $codeStmt->fetchColumn();

        if ($lastCode) {
            $lastNumber = (int)substr($lastCode, 4); // skip "DSyy"
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = "0001";
        }
        $this->referal_code = $codePrefix . $newNumber;

        // Step 4: Prepare registration and insert
        $this->reg_date = date('Y-m-d H:i:s'); // current time
        $this->status = "1"; // active

        $query = "INSERT INTO {$this->table} 
            (school_full_name, school_short_name, referal_code, address, phone, email, reg_date, status) 
            VALUES (:school_full_name, :school_short_name, :referal_code, :address, :phone, :email, :reg_date, :status)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":school_full_name", $this->school_full_name);
        $stmt->bindParam(":school_short_name", $this->school_short_name);
        $stmt->bindParam(":referal_code", $this->referal_code);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":reg_date", $this->reg_date);
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
 // Update a school by ID
    // This method assumes that the school ID is passed as a parameter
    // and that the other fields are set in the object before calling this method.
public function update($id) {
    try {
        // Check if another record has the same email or full name (excluding current ID)
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} 
                       WHERE (email = :email OR school_full_name = :school_full_name) 
                       AND school_id != :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $this->email);
        $checkStmt->bindParam(':school_full_name', $this->school_full_name);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            return "Another school with the same email or full name already exists.";
        }

        // Proceed to update
        $query = "UPDATE {$this->table} SET 
                    school_full_name = :school_full_name, 
                    school_short_name = :school_short_name, 
                    referal_code = :referal_code, 
                    address = :address, 
                    phone = :phone, 
                    email = :email, 
                    status = :status 
                  WHERE school_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":school_full_name", $this->school_full_name);
        $stmt->bindParam(":school_short_name", $this->school_short_name);
        $stmt->bindParam(":referal_code", $this->referal_code);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return true;
        } else {
            return "Database error during update.";
        }
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}

    // Delete a school by ID
   public function delete($id) {
    try {
        // Step 1: Check if school is referenced in tbl_registration
        $checkQuery = "SELECT COUNT(*) FROM tbl_registration WHERE school_id = :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            return "Cannot delete school. It is referenced in registration records.";
        }

        // Step 2: Proceed with deletion
        $query = "DELETE FROM {$this->table} WHERE school_id = :id";  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return true;
        } else {
            return "Database error during delete.";
        }
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}


    // Read all schools
    // This method can be extended to include filtering, sorting, etc.
    public function read() {
        $query = "SELECT * FROM {$this->table}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Example usage
    public function getAlldata() {
        $schools = $this->read();
        $schoolList = [];
        while ($row = $schools->fetch(PDO::FETCH_ASSOC)) {
            $schoolList[] = $row;
        }
        return $schoolList;
    }
    public function getSchoolById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
   
}
