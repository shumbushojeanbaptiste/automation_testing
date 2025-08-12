<?php
class Student {
    private $conn;
    private $table = "tbl_students";

    public $first_name;
    public $familly_name;
    public $email;

    public $phone;
    public $ID_number;
   

    public $status = "1"; // default active
    public $stu_code;
   

    public function __construct($db) {
        $this->conn = $db;
    }

public function create() {
    try {
        // Step 1: Validate required fields
        if (empty($this->familly_name) || empty($this->first_name) || empty($this->phone) ||
            empty($this->ID_number)) {
            return "Missing required fields: familly_name, first_name, phone, ID_number.";
        }

        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format.";
        }

        if (strlen($this->ID_number) < 6) {
            return "ID_number must be at least 6 characters.";
        }

        // Step 2: Check if ID_number already exists
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} WHERE ID_number = :ID_number";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':ID_number', $this->ID_number);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn() > 0) {
            return "ID_number already exists.";
        }

        // Step 3: Generate stu_code based on latest record
        $yearPrefix = date("y"); // e.g., "25" for 2025
        $codeQuery = "SELECT stu_code FROM {$this->table} WHERE stu_code LIKE :yearPrefix ORDER BY stu_code DESC LIMIT 1";
        $codeStmt = $this->conn->prepare($codeQuery);
        $likePattern = $yearPrefix . "%";
        $codeStmt->bindParam(':yearPrefix', $likePattern);
        $codeStmt->execute();
        $lastCode = $codeStmt->fetchColumn();

        if ($lastCode) {
            // Increment numeric part
            $lastNumber = (int)substr($lastCode, 2); // skip year prefix
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = "0001";
        }
        $this->user_code = $yearPrefix . $newNumber;

        // Step 4: Prepare and insert user
        $query = "INSERT INTO {$this->table} 
            (stu_code, first_name, familly_name, phone, email, ID_number, status)
            VALUES (:stu_code, :first_name, :familly_name, :phone, :email, :ID_number, :status)";
        $stmt = $this->conn->prepare($query);

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":stu_code", $this->stu_code);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":familly_name", $this->familly_name);

        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":ID_number", $this->ID_number);
        // Set status to active
        $this->status = "1"; // default active status
        $status = "1";
        $stmt->bindParam(":status", $status);

        if ($stmt->execute()) {
            return true;
        } else {
            return "Database error during insert.";
        }
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}


    public function read($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE stu_code = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    public function update($id) {
    try {
        // Check for duplicate email (excluding current user)
        $checkQuery = "SELECT COUNT(ID_number) FROM {$this->table} WHERE ID_number = :ID_number AND id != :id";
         // Prepare the query
        $checkStmt = $this->conn->prepare($checkQuery);
        // Bind parameters
        $checkStmt->bindParam(':ID_number', $this->ID_number);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            return "ID_number already exists.";
        }

        // Perform update
        $query = "UPDATE {$this->table} SET 
                    first_name = :first_name,
                    familly_name = :familly_name,
                    email = :email,
                    phone = :phone,
                    ID_number = :ID_number
                    
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':familly_name', $this->familly_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':ID_number', $this->ID_number);
        
        $stmt->bindParam(':id', $id);

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
            $query = "DELETE FROM {$this->table} WHERE user_code = :id";
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


    public function getAll() {
        try {
            $query = "SELECT id,stu_code, first_name, familly_name, email, phone, ID_number, status FROM {$this->table} order by id desc";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
  


}

class Registration {
    private $conn;
    private $table = " tbl_registration";

    public $stu_code;
    public $school_id;
    public $center_id;
    public $license_categ;
    public $booking_day;
    public $amount;
    public $due_date;
    public $sts = "1"; // default active

    public function __construct($db) {
        $this->conn = $db;
    }

   public function create() {
    try {
        // Validate required fields
        if (empty($this->stu_code) || empty($this->license_categ) || empty($this->booking_day) || empty($this->amount) || empty($this->due_date)) {
            return "Missing required fields: stu_code, license_categ, booking_day, amount, due_date.";
        }

        // Check if stu_code and center_id combination already exists
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} 
                       WHERE stu_code = :stu_code AND center_id = :center_id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":stu_code", $this->stu_code);
        $checkStmt->bindParam(":center_id", $this->center_id);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            return "A booking for this student at this center already exists.";
        }

        // Prepare and execute insert query
        $query = "INSERT INTO {$this->table} 
                  (stu_code, school_id, center_id, license_categ, booking_day, amount, due_date, sts)
                  VALUES (:stu_code, :school_id, :center_id, :license_categ, :booking_day, :amount, :due_date, :status)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":stu_code", $this->stu_code);
        $stmt->bindParam(":school_id", $this->school_id);
        $stmt->bindParam(":center_id", $this->center_id);
        $stmt->bindParam(":license_categ", $this->license_categ);
        $stmt->bindParam(":booking_day", $this->booking_day);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":due_date", $this->due_date);
        $status = "1"; // default active status
        $stmt->bindParam(":status", $status);

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
            $query = "SELECT * FROM {$this->table} ORDER BY reg_id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
}
