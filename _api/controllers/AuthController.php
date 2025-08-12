<?php
require_once './models/User.php';
require_once './config/database.php';
require_once './core/Response.php';

class AuthController {
  public function register() {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        $db = (new Database())->connect();
        $user = new User($db);
        
        // Populate user fields
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];
        // use hashing for security key by Argon2
        $user->security_key = password_hash($data['security_key'], PASSWORD_ARGON2I);
        $user->role = $data['role_id'];
        $user->user_code = $data['user_code'] ?? "123456"; // default user code
        $user->role_id = $data['role_id'] ?? 2; // default to user role if not provided
        $user->status = "1"; // default active status
        $user->user_code = $data['user_code'] ?? "123456"; // default user code


        // Try to create the user
       $result = $user->create();

if ($result === true) {
    Response::json([
        "message" => "{$user->first_name} User registered successfully."
    ], 201);
} else {
    Response::json([
        "message" => "Failed to register user.",
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

    public function getUser($id) {
        try {
            $db = (new Database())->connect();
            $user = new User($db);
            $user->user_code = $id;
            $result = $user->read();
    
            if ($result) {
                Response::json($result, 200);
            } else {
                Response::json(["message" => "User not found."], 404);
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
    public function updateUser() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $db = (new Database())->connect();
            $user = new User($db);
            
            // Populate user fields
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->email = $data['email'];
            $user->phone = $data['phone'];
           
            $user->role_id = $data['role_id'] ?? 2; // default to user role if not provided
            $user->status = "1"; // default active status
           
            $user->id = $data['acc_id']; // Ensure the ID is set for the update
            // Validate required fields
            if (empty($user->first_name) || empty($user->last_name) || empty($user->email) || empty($user->phone)) {
                Response::json([
                    "error" => "Missing required fields: first_name, last_name, email, or phone."
                ], 400);
                return;
            }

            // Update user

            $result = $user->update($data['acc_id']);

            if ($result === true) {
                Response::json(["message" => "User updated successfully."], 200);
            } else {
                Response::json(["message" => "Failed to update user."], 400);
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
    public function deleteUser($id) {
        try {
            $db = (new Database())->connect();
            $user = new User($db);
            $user->user_code = $id;
    
            if ($user->delete()) {
                Response::json(["message" => "User deleted successfully."], 200);
            } else {
                Response::json(["message" => "Failed to delete user."], 400);
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
    public function getAllUsers() {
            try {
                $db = (new Database())->connect();
                $user = new User($db);
                $users = $user->getAll();
        
                if ($users) {
                    Response::json($users, 200);
                } else {
                    Response::json(["message" => "No users found."], 404);
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

class AuthanticationChecker {
    public static function checkAuth() {
        // Check if the user is authenticated
        if (!isset($_SESSION['user_id'])) {
            Response::json(["error" => "Unauthorized access"], 401);
            exit();
        }
    }
public function login() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $db = (new Database())->connect();
            $authantication = new Authantication($db);


            // Attempt to log in
            // hash the security key for comparison using Argon2
            if (empty($data['email']) || empty($data['security_key'])) {
                Response::json(["error" => "Email and security key are required."], 400);
                return;
            }
            // $data['security_key'] = password_hash($data['security_key'], PASSWORD_ARGON2I);
            $user = $authantication->login($data['email'], $data['security_key']);
            if ($user) {
                session_start();
                $_SESSION['user'] = [
                    'acc_id' => $user['acc_id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role_id' => $user['role_id'],
                    'role_name' => $user['role_name'],
                    'status' => $user['status']
                ];
                Response::json([
                    "message" => "Login successful.",
                    "user-info" => $_SESSION['user'],
                    "redirect" => "../finance/dashboard"
                ], 200);
               
            } else {
                Response::json(["message" => "Invalid email or security key."], 401);
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

   public function getUserProfile() {

          try {
                $db = (new Database())->connect();
                $authantication = new Authantication($db);

                $acc_id = $_SESSION['acc_id'] ?? null;
                if (!$acc_id) {
                    Response::json(["error" => "User not authenticated."], 401);
                    return;
                }
                $users = $authantication->getUserProfile($acc_id);

                if ($users) {
                    Response::json($users, 200);
                } else {
                    Response::json(["message" => "No users found."], 404);
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
    public function logout() {
    session_start();
    session_unset();
    session_destroy();

    Response::json(["message" => "Logged out successfully."], 200);
}
}
?>