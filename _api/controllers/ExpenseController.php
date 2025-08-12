<?php
require_once './models/Expense.php';
require_once './config/database.php';
require_once './core/Response.php';
class ExpenseController
{
    public function registerExpense()
    {
        // Logic for registering an expense
        $data = json_decode(file_get_contents("php://input"), true);
        $db = (new Database())->connect();
        $expense = new Expense($db);

        $expense->expense_category = $data['expense_category'];
        $expense->paid_amount = $data['paid_amount'];
        $expense->due_date = $data['due_date'];
        $expense->description = $data['description'];
        $expense->consumer_id = $data['consumer_id'];
        $expense->payment_mode = $data['payment_mode'];
        $expense->recorded_by = $data['recorded_by'];



         $result = $expense->create();

         if ($result === true) {
    Response::json([
        "message" => "{$expense->description} expense registered successfully."
    ], 201);
} else {
    Response::json([
        "message" => "Failed to register expense.",
        "error" => $result
    ], 400);
}


    }

    public function updateExpense(){
 try {
        $data = json_decode(file_get_contents("php://input"), true);
        $db = (new Database())->connect();
        $expense = new Expense($db);

        // Populate expense fields
        $expense->expense_id = $data['expense_id'];
        $expense->expense_category = $data['expense_category'];
        $expense->paid_amount = $data['paid_amount'];
        $expense->due_date = $data['due_date'];
        $expense->description = $data['description'];
        $expense->consumer_id = $data['consumer_id'];
        $expense->payment_mode = $data['payment_mode'];
        $expense->recorded_by = $data['recorded_by'];

        // Validate required fields
        if (empty($expense->expense_category) || empty($expense->paid_amount) || empty($expense->due_date) || empty($expense->description)) {
            Response::json([
                "error" => "Missing required fields: expense_category, paid_amount, due_date, description"
            ], 400);
            return;
        }

        // Call update method and get result
        $result = $expense->update($expense->expense_id);

        if ($result === true) {
            Response::json(["message" => "Expense updated successfully."], 200);
        } else {
            // Return the actual error message from update() function
            Response::json([
                "error" => is_string($result) ? $result : "Failed to update expense."
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
    public function deleteExpense($id)
    {
        try {
            $db = (new Database())->connect();
            $expense = new Expense($db);
            $result = $expense->delete($id);
            if ($result === true) {
                Response::json(["message" => "Expense deleted successfully."], 200);
            } else {
                Response::json(["message" => "Failed to delete expense."], 400);
            }
        } catch (PDOException $e) {
            Response::json([
                "error" => "Database error",
                "details" => $e->getMessage()
            ], 500);
        }
    }

    public function getAllExpenses()
    {
        try {
            $db = (new Database())->connect();
            $expense = new Expense($db);
            $result = $expense->getAll();

            if ($result) {
                Response::json($result, 200);
            } else {
                Response::json(["message" => "No expenses found."], 404);
            }
        } catch (PDOException $e) {
            Response::json([
                "error" => "Database error",
                "details" => $e->getMessage()
            ], 500);
        }
    }
}
?>