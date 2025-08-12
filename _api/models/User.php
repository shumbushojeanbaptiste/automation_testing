<?php
class User {
    private $conn;
    private $table = "tbl_users";

    public $last_name;
    public $first_name;
    public $phone;

    public $email;
    public $center_id;
    public $school_id;
    public $security_key;

    public $status = "1"; // default active
    public $role_id;
    public $user_code="123456"; // default user code, can be changed later
    public $role; // 'admin' or 'user'
    public $last_logged_in=null;
   

    public function __construct($db) {
        $this->conn = $db;
    }

public function create() {
    try {
        // Step 1: Validate required fields
        if (empty($this->last_name) || empty($this->first_name) || empty($this->phone) ||
            empty($this->security_key) || empty($this->role)) {
            return "Missing required fields: last_name, first_name, phone, password, or role.";
        }

        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format.";
        }

        if (strlen($this->security_key) < 6) {
            return "Password must be at least 6 characters.";
        }

        // Step 2: Check if email already exists
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $this->email);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn() > 0) {
            return "Email already exists.";
        }

        // Step 3: Generate user_code based on latest record
        $yearPrefix = date("y"); // e.g., "25" for 2025
        $codeQuery = "SELECT user_code FROM {$this->table} WHERE user_code LIKE :yearPrefix ORDER BY user_code DESC LIMIT 1";
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
            (user_code, last_name, first_name, phone, email, security_key, role_id, status, last_logged_in)
            VALUES (:user_code, :last_name, :first_name, :phone, :email, :security_key, :role_id, :status, :last_logged_in)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_code", $this->user_code);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":security_key", $this->security_key); // Optional: password_hash() here
        $stmt->bindParam(":role_id", $this->role_id);
        $status = "1";
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":last_logged_in", $this->last_logged_in);

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
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email AND acc_id != :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $this->email);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            return "Email already exists.";
        }

        // Perform update
        $query = "UPDATE {$this->table} SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone,
                    role_id = :role_id,
                    status = :status
                  WHERE acc_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':role_id', $this->role_id);
        $stmt->bindParam(':status', $this->status);
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
            $query = "SELECT acc_id, first_name,
             last_name, tbl_users.email, tbl_users.phone, role_name,
            tbl_users.status FROM {$this->table}
            inner join tbl_roles on tbl_users.role_id = tbl_roles.role_id
    
            ORDER BY tbl_users.acc_id DESC
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    public function getAllActive() {
        try {
            $query = "SELECT acc_id, first_name, last_name, email, phone, center_id, school_id, role_id, status FROM {$this->table} WHERE status = '1'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    } 



    public function getByRole($role_id) {
        try {
            $query = "SELECT acc_id, first_name, last_name, email, phone, center_id, school_id, role_id, status FROM {$this->table} WHERE role_id = :role_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':role_id', $role_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    public function getBySchool($school_id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE school_id = :school_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':school_id', $school_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

}

class Authantication {
    private $conn;
    private $table = "tbl_users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        try {
            // Validate input
            if (empty($email) || empty($password)) {
                return false; // Invalid input
            }
            // Prepare and execute the query
            $email = trim($email);
            $password = trim($password);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false; // Invalid email format
            }
// check pass user using password_verify as create by PASSWORD_ARGON2I
// Note: In a real application, you should hash the password and store it securely.
            $query = "SELECT acc_id, first_name, last_name, email, phone,
             tbl_roles.role_id, tbl_roles.role_name, status, security_key FROM {$this->table}
             INNER JOIN tbl_roles ON {$this->table}.role_id = tbl_roles.role_id
             WHERE email = :email AND status = '1'";
            // Note: In a real application, you should hash the password and compare it with the hashed value in the database.
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['security_key'])) {
                    return $user;
                } 
            }
            return false; // Invalid credentials
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    public function getUserProfile($id) {
        try {
            $query = "SELECT acc_id, first_name, last_name, email, phone, center_id, school_id, role_id, status FROM {$this->table} WHERE acc_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
}
