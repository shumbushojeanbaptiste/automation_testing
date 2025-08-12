<?php
class Project {

    private $conn;
    private $table = "tbl_client_projects";

    public $project_name;
    public $description;
    public $client_id;
    public $product_id;
    public $start_date;
    public $end_date;
    public $budget;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create(){

           try {
        // Validate required fields
        if (
            empty($this->project_name) || 
            empty($this->description)
        ) {
            return "Missing required fields: project_name, description";
        }

        // Check if project_name already exists
        $checkQuery = "SELECT COUNT(*) FROM {$this->table} WHERE project_name = :project_name";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":project_name", $this->project_name);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            return "$this->project_name already exists.";
        }

        // Prepare and execute insert query

       
        $query = "INSERT INTO {$this->table} 
            (project_name, description, client_id, product_id, start_date, end_date, budget) 
            VALUES 
            (:project_name, :description, :client_id, :product_id, :start_date, :end_date, :budget)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":project_name", $this->project_name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":client_id", $this->client_id);
        $stmt->bindParam(":product_id", $this->product_id);
        $stmt->bindParam(":start_date", $this->start_date);
        $stmt->bindParam(":end_date", $this->end_date);
        $stmt->bindParam(":budget", $this->budget);
       

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
        $checkQuery = "SELECT COUNT(project_name) FROM {$this->table} WHERE project_name = :project_name AND project_id != :project_id";
         // Prepare the query
        $checkStmt = $this->conn->prepare($checkQuery);
        // Bind parameters
        $checkStmt->bindParam(':project_name', $this->project_name);
        $checkStmt->bindParam(':project_id', $id);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            return "project_name already exists.";
        }

        // Perform update
        $query = "UPDATE {$this->table} SET 
                    project_name = :project_name,
                    description = :description,
                    client_id = :client_id,
                    product_id = :product_id,
                    start_date = :start_date,
                    end_date = :end_date,
                    budget = :budget
                  WHERE project_id = :project_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_name', $this->project_name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':client_id', $this->client_id);
        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':end_date', $this->end_date);
        $stmt->bindParam(':budget', $this->budget);
        $stmt->bindParam(':project_id', $id);

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
        $query = "DELETE FROM {$this->table} WHERE project_id = :id";   
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


class ProjectAssignment {
    private $conn;
    private $table = "tbl_project_assignments";
    public $project_id;
    public $user_id;
    public $assigned_role;
    public $assigned_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function assign() {
        try {
            // Validate required fields
            if (empty($this->project_id) || empty($this->user_id) || empty($this->assigned_role) || empty($this->assigned_by)) {
                return "Missing required fields.";
            }

            // Check for duplicate assignment
            $query = "SELECT COUNT(*) FROM {$this->table} WHERE project_id = :project_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':project_id', $this->project_id);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                return "Project is already assigned to this user.";
            }   

            // If no duplicate, proceed with assignment
            $query = "INSERT INTO {$this->table} (project_id, user_id, assigned_role, assigned_by) VALUES (:project_id, :user_id, :assigned_role, :assigned_by)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':project_id', $this->project_id);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':assigned_role', $this->assigned_role);
            $stmt->bindParam(':assigned_by', $this->assigned_by);

            if ($stmt->execute()) {
                return true;
            } else {
                return "Failed to execute assign query.";
            }
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }
    public function getAll() {
        try {
            $query = "SELECT tbl_project_assignments.assignment_id as id,tbl_users.last_name, tbl_users.first_name, 
            tbl_users.phone, tbl_users.email, tbl_client_projects.project_name,tbl_client_projects.start_date FROM {$this->table} 
            INNER JOIN tbl_users ON tbl_project_assignments.user_id = tbl_users.acc_id
            INNER JOIN tbl_client_projects ON tbl_project_assignments.project_id = tbl_client_projects.project_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Database error: " . $e->getMessage();
        }
    }
    public function delete($id) {
    try {
        $query = "DELETE FROM {$this->table} WHERE assignment_id = :id";
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
}
