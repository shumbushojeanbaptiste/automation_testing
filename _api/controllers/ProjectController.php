<?php
require_once './models/Project.php';
require_once './config/database.php';
require_once './core/Response.php';
class ProjectController
{
    public function registerProject()
    {
        // Logic for registering a project
        $data = json_decode(file_get_contents("php://input"), true);
        $db = (new Database())->connect();
        $project = new Project($db);

        $project->project_name = $data['project_name'];
        $project->description = $data['description'];
        $project->client_id = $data['client_id'];
        $project->product_id = $data['product_id'];
        $project->start_date = $data['start_date'];
        $project->end_date = $data['end_date'];
        $project->budget = $data['budget'];

         $result = $project->create();

         if ($result === true) {
    Response::json([
        "message" => "{$project->project_name} project registered successfully."
    ], 201);
} else {
    Response::json([
        "message" => "Failed to register project.",
        "error" => $result
    ], 400);
}


    }

    public function updateProject(){
 try {
        $data = json_decode(file_get_contents("php://input"), true);
        $db = (new Database())->connect();
        $project = new Project($db);

        // Populate project fields
        $project->project_id = $data['project_id'];
        $project->project_name = $data['project_name'];
        $project->description = $data['description'];
        $project->client_id = $data['client_id'];
        $project->product_id = $data['product_id'];
        $project->start_date = $data['start_date'];
        $project->end_date = $data['end_date'];
        $project->budget = $data['budget'];


        // Validate required fields
        if (empty($project->project_name) || empty($project->description)) {
            Response::json([
                "error" => "Missing required fields: project_name, description"
            ], 400);
            return;
        }

        // Call update method and get result
        $result = $project->update($project->project_id);

        if ($result === true) {
            Response::json(["message" => "project updated successfully."], 200);
        } else {
            // Return the actual error message from update() function
            Response::json([
                "error" => is_string($result) ? $result : "Failed to update project."
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
    public function deleteProject($id)
    {
        try {
            $db = (new Database())->connect();
            $project = new Project($db);
            $result = $project->delete($id);
            if ($result === true) {
                Response::json(["message" => "Project deleted successfully."], 200);
            } else {
                Response::json(["message" => "Failed to delete project."], 400);
            }
        } catch (PDOException $e) {
            Response::json([
                "error" => "Database error",
                "details" => $e->getMessage()
            ], 500);
        }
    }

    public function getAllProjects()
    {
        try {
            $db = (new Database())->connect();
            $project = new Project($db);
            $result = $project->getAll();

            if ($result) {
                Response::json($result, 200);
            } else {
                Response::json(["message" => "No projects found."], 404);
            }
        } catch (PDOException $e) {
            Response::json([
                "error" => "Database error",
                "details" => $e->getMessage()
            ], 500);
        }
    }
    public function getProjectById($id)
    {
        try {
            $db = (new Database())->connect();
            $project = new Project($db);
            $result = $project->getById($id);

            if ($result) {
                Response::json($result, 200);
            } else {
                Response::json(["message" => "Project not found."], 404);
            }
        } catch (PDOException $e) {
            Response::json([
                "error" => "Database error",
                "details" => $e->getMessage()
            ], 500);
        }
    }

    public function assignation(){
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $db = (new Database())->connect();
            $project = new ProjectAssignment($db);

            // Populate project fields
            $project->project_id = $data['project_id'];
            $project->user_id = $data['user_id'];
            $project->assigned_role = $data['assigned_role'];
            $project->assigned_by = $data['assigned_by'];

            // Validate required fields
            if (empty($project->project_id) || empty($project->user_id)) {
                Response::json([
                    "error" => "Missing required fields: project_id, user_id"
                ], 400);
                return;
            }

            // Call assign method and get result
            $result = $project->assign();

            if ($result === true) {
                Response::json(["message" => "Project assigned successfully."], 200);
            } else {
                // Return the actual error message from assign() function
                Response::json([
                    "error" => is_string($result) ? $result : "Failed to assign project."
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
    public function getAllAssignments() {
    try {
        $db = (new Database())->connect();
        $project = new ProjectAssignment($db);
        $result = $project->getAll();

        if ($result) {
            Response::json($result, 200);
        } else {
            Response::json(["message" => "No assignments found."], 404);
        }
    } catch (PDOException $e) {
        Response::json([
            "error" => "Database error",
            "details" => $e->getMessage()
        ], 500);
    }
}
public function deleteAssignment($id) {
    try {
        $db = (new Database())->connect();
        $project = new ProjectAssignment($db);
        $result = $project->delete($id);

        if ($result === true) {
            Response::json(["message" => "Assignment deleted successfully."], 200);
        } else {
            Response::json([
                "error" => is_string($result) ? $result : "Failed to delete assignment."
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
}
?>