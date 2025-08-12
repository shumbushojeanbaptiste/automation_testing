<?php
require_once './models/Student.php';
require_once './config/database.php';
require_once './core/Response.php';

class StudentController {
    /**
     * Register a new student.
     * This method expects a JSON payload with the student's details.
     */
  public function register() {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        $db = (new Database())->connect();
        $student = new Student($db);

        // student fields
        $student->first_name = $data['first_name'];
        $student->familly_name = $data['familly_name'];
        $student->email = $data['email'];
        $student->phone = $data['phone'];
        $student->ID_number = $data['ID_number'] ?? null;
        $student->status = "1"; // default active status
        $student->stu_code = $data['stu_code'] ?? "123456"; // default student code



        // Try to create the student
       $result = $student->create();

if ($result === true) {
    Response::json([
        "message" => "{$student->first_name} Student registered successfully."
    ], 201);
} else {
    Response::json([
        "message" => "Failed to register student.",
        "error" => $result
    ], 400);
}

    } catch (PDOException $e) {
        // Database error (e.g., duplicate email, constraint violation)
        Response::json([
            "error" => "Database error",
            "details" => $e->getMessage()
        ], 500);
    } catch (Exception $e) {
        // General PHP error
        Response::json([
            "error" => "Server error",
            "details" => $e->getMessage()
        ], 500);
    }
}



    /**
     * Update an existing student's details.
     * This method expects a JSON payload with the student's updated details.
     */
   public function updateStudent() {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        $db = (new Database())->connect();
        $student = new Student($db);

        // Populate student fields
        $student->first_name = $data['first_name'];
        $student->familly_name = $data['familly_name'];
        $student->email = $data['email'];
        $student->phone = $data['phone'];
        $student->ID_number = $data['ID_number'] ?? null;
        $student->status = "1"; // default active status
        $student->id = $data['id']; // Ensure the ID is set for the update

        // Validate required fields
        if (empty($student->first_name) || empty($student->familly_name) || empty($student->email) || empty($student->phone)) {
            Response::json([
                "error" => "Missing required fields: first_name, familly_name, email, or phone."
            ], 400);
            return;
        }

        // Call update method and get result
        $result = $student->update($student->id);

        if ($result === true) {
            Response::json(["message" => "Student updated successfully."], 200);
        } else {
            // Return the actual error message from update() function
            Response::json([
                "error" => is_string($result) ? $result : "Failed to update student."
            ], 400);
        }

    } catch (PDOException $e) {
        Response::json([
            "error" => "Database error",
            "details" => $e->getMessage()
        ], 500);
    } catch (Exception $e) {
        Response::json([
            "error" => "Server error",
            "details" => $e->getMessage()
        ], 500);
    }
}

    public function deleteStudent($id) {
        try {
            $db = (new Database())->connect();
            $student = new Student($db);
            $student->stu_code = $id;

            if ($student->delete()) {
                Response::json(["message" => "Student deleted successfully."], 200);
            } else {
                Response::json(["message" => "Failed to delete student."], 400);
            }
    
        } catch (PDOException $e) {
            Response::json([
                "error" => "Database error",
                "details" => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            Response::json([
                "error" => "Server error",
                "details" => $e->getMessage()
            ], 500);
        }
    }
    public function getAllStudents() {
            try {
                $db = (new Database())->connect();
                $student = new Student($db);
                $students = $student->getAll();

                if ($students) {
                    Response::json($students, 200);
                } else {
                    Response::json(["message" => "No students found."], 404);
                }
        
            } catch (PDOException $e) {
                Response::json([
                    "error" => "Database error",
                    "details" => $e->getMessage()
                ], 500);
            } catch (Exception $e) {
                Response::json([
                    "error" => "Server error",
                    "details" => $e->getMessage()
                ], 500);
            }
        }

        public function studentLicenseRegistration() {
            try {
                $data = json_decode(file_get_contents("php://input"), true);
                $db = (new Database())->connect();
                $registration = new Registration($db);

                // Populate registration fields
                $registration->stu_code = $data['stu_code'];
                $registration->license_categ = $data['license_categ'];
                $registration->booking_day = $data['booking_day'];
                $registration->amount = $data['amount'];
                $registration->due_date = $data['due_date'];
                $registration->school_id = $data['school_id'] ?? null; // Optional field
                $registration->center_id = $data['center_id'] ?? null; // Optional field
                

                // Call create method and get result
                $result = $registration->create();

                if ($result === true) {
                    Response::json(["message" => "Student registered successfully."], 201);
                } else {
                    Response::json(["error" => "Failed to register student.", "details" => $result], 400);
                }

            } catch (PDOException $e) {
                Response::json(["error" => "Database error", "details" => $e->getMessage()], 500);
            } catch (Exception $e) {
                Response::json(["error" => "Server error", "details" => $e->getMessage()], 500);
            }
        }

        public function getAllStudentsRegistrations() {
            try {
                $db = (new Database())->connect();
                $registration = new Registration($db);
                $registrations = $registration->getAll();

                if ($registrations) {
                    Response::json($registrations, 200);
                } else {
                    Response::json(["message" => "No registrations found."], 404);
                }
        
            } catch (PDOException $e) {
                Response::json([
                    "error" => "Database error",
                    "details" => $e->getMessage()
                ], 500);
            } catch (Exception $e) {
                Response::json([
                    "error" => "Server error",
                    "details" => $e->getMessage()
                ], 500);
            }
        }
}
?>