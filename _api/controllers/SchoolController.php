<?php
require_once './models/School.php';
require_once './config/database.php';
require_once './core/Response.php';

class SchoolController {
  public function registerSchool() {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        $db = (new Database())->connect();
        $school = new School($db);
        // Validate required fields
        if (empty($data['school_full_name']) || empty($data['school_short_name']) || empty($data['phone']) || empty($data['email'])) {
            Response::json([
                "error" => "Missing required fields: school_full_name, school_short_name, phone, or email."
            ], 400);
            return;
        }
        // Populate school fields
        $school->school_full_name = $data['school_full_name'];
        $school->school_short_name = $data['school_short_name'];
        $school->referal_code = $data['referal_code'] ?? null;
        $school->address = $data['address'] ?? null;
        $school->phone = $data['phone'];
        $school->email = $data['email'];
        $school->reg_date = date('Y-m-d H:i:s');
        $school->status = "1"; // default active status
        // Try to create the school
       $result = $school->create();

       if ($result === true) {
           Response::json([
               "message" => " $school->school_full_name registered successfully."
           ], 201);
       } else {
           Response::json([
               "message" => "Failed to register school.",
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
           // End of register method
           public function getSchool($id) {
               try {
                   $db = (new Database())->connect();
                   $school = new School($db);
                   $school->id = $id;
                   $result = $school->read();

                   if ($result) {
                       Response::json($result, 200);
                   } else {
                       Response::json(["message" => "School not found."], 404);
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
    public function updateSchool() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $db = (new Database())->connect();
            $school = new School($db);
            

            // Validate required fields
            if (empty($data['school_full_name']) || empty($data['school_short_name']) || empty($data['phone']) || empty($data['email'])) {
                Response::json([
                    "error" => "Missing required fields: school_full_name, school_short_name, phone, or email."
                ], 400);
                return;
            }

            // Populate school fields
            $school->school_full_name = $data['school_full_name'];
            $school->school_short_name = $data['school_short_name'];
            $school->referal_code = $data['referal_code'] ?? null;
            $school->address = $data['address'] ?? null;
            $school->phone = $data['phone'];
            $school->email = $data['email'];
            $school->id= $data['id'];// Ensure the ID is set for the update
            $school->status = isset($data['status']) ? $data['status'] : "1"; // default active status

            // Try to update the school
           $result = $school->update($school->id);

           if ($result === true) {
               Response::json([
                   "message" => " {$school->school_full_name} updated successfully."
               ], 200);
           } else {
               Response::json([
                   "message" => "Failed to update school.",
                   "error" => $result
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
  //  Delete a school by ID

  
    // Delete a school by ID
    public function deleteSchool() {
        try {
            $db = (new Database())->connect();  
            $school = new School($db);
            $data = json_decode(file_get_contents("php://input"), true);
            $school->id = $data['id']; // Ensure the ID is set for the delete

            if ($school->delete($school->id)) {  
                Response::json(["message" => "School deleted successfully."], 200);
            } else {
                Response::json(["message" => "Failed to delete school."], 400);
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

    public function getAllSchools() {
        try {
            $db = (new Database())->connect();
            $school = new School($db);
            $schools = $school->getAlldata();

            if ($schools) {
                Response::json($schools, 200);
            } else {
                Response::json(["message" => "No schools found."], 404);
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