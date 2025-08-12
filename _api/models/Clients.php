<?php
class Client {
    private $conn;
    private $table = "tbl_clients";

    public $client_code;
    public $first_name;
    public $last_name;
    public $phone;
    public $email;
    public $client_type;
    public $tin_number;
    public $financial_type;
    public $location_address;
    


    public function __construct($db) {
        $this->conn = $db;
    }

   public function create() {
    try {
        // Validate required fields
        if (
            empty($this->client_code) || 
            empty($this->first_name) || 
            empty($this->last_name) || 
            empty($this->client_type) || 
            empty($this->phone) || 
            empty($this->tin_number) || 
            empty($this->email) ||
            empty($this->financial_type) ||
            empty($this->location_address)
        ) {
            return "Missing required fields: client_code, first_name, last_name, client_type, phone, tin_number, email, financial_type, location_address.";
        }

        // Check if tin_number already exists
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} WHERE tin_number = :tin_number";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":tin_number", $this->tin_number);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            return "tin_number already exists.";
        }

        // Prepare and execute insert query

       
        $query = "INSERT INTO {$this->table} 
            (client_code, f_name, l_name, phone_number, tin_number, email_address, financial_type, location_address, client_type) 
            VALUES 
            (:client_code, :f_name, :l_name, :phone_number, :tin_number, :email_address, :financial_type, :location_address, :client_type)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":client_code", $this->client_code);
        $stmt->bindParam(":f_name", $this->first_name);
        $stmt->bindParam(":l_name", $this->last_name);
        $stmt->bindParam(":phone_number", $this->phone);
        $stmt->bindParam(":tin_number", $this->tin_number);
        $stmt->bindParam(":email_address", $this->email);
        $stmt->bindParam(":financial_type", $this->financial_type);
        $stmt->bindParam(":location_address", $this->location_address);
        $stmt->bindParam(":client_type", $this->client_type);

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
        // Validate required fields
        if (
            empty($id) ||
            empty($this->first_name) || 
            empty($this->last_name) || 
            empty($this->phone) || 
            empty($this->tin_number) || 
            empty($this->email) ||
            empty($this->financial_type) ||
            empty($this->location_address)
        ) {
            return "Missing required fields:first_name, last_name, phone, tin_number, email, financial_type, location_address.";
        }

        // Check if tin_number already exists for another ID
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} WHERE tin_number = :tin_number AND client_id != :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":tin_number", $this->tin_number);
        $checkStmt->bindParam(":id", $id);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            return "client with this TIN_number already exists.";
        }

        // Prepare and execute update query
       
        $query = "UPDATE {$this->table} SET 
            f_name = :first_name, 
            l_name = :familly_name, 
            phone_number = :phone, 
            tin_number = :tin_number, 
            email_address = :email ,
            financial_type = :financial_type,
            location_address = :location_address,
            client_type = :client_type
            WHERE client_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":familly_name", $this->familly_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":tin_number", $this->tin_number);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":financial_type", $this->financial_type);
        $stmt->bindParam(":location_address", $this->location_address);
        $stmt->bindParam(":client_type", $this->client_type);
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
    public function getAllClientsType() {
        try {
            $query = "SELECT * FROM client_type";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }

    public function getAssignedInstructors($center_id) {
        try {
            $query = "SELECT * FROM tbl_school_instructors WHERE center_id = :center_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":center_id", $center_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }
}

