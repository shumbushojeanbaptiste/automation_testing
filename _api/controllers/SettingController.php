<?php
require_once './models/Setting.php';
require_once './config/database.php';
require_once './core/Response.php';

class SettingController {
    /**
     * General settings.
     * This method expects a JSON payload with the setting's details.
     */



    public function getAllRoles() {
        try {
            $db = (new Database())->connect();
            $setting = new Roles($db);
    
            // Fetch all roles
            $result = $setting->getAll();
    
            if ($result) {
                Response::json(["data" => $result], 200);
            } else {
                Response::json([
                    "message" => "No roles found."
                ], 404);
            }
        } catch (PDOException $e) {
            // Database error
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


public function getAllPaymentMethods() {
    try {
        $db = (new Database())->connect();
        $paymentMethod = new PaymentMethod($db);

        // Fetch all payment methods
        $result = $paymentMethod->getAll();

        if ($result) {
           Response::json(["data" => $result], 200);
        } else {
            Response::json([
                "message" => "No licenses found."
            ], 404);
        }
    } catch (PDOException $e) {
        // Database error
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

public function getAllClassifications() {
    try {
        $db = (new Database())->connect();
        $classification = new Classification($db);

        // Fetch all classifications
        $result = $classification->getAll();

        if ($result) {
            Response::json($result, 200);
        } else {
            Response::json([
                "message" => "No classifications found."
            ], 404);
        }
    } catch (PDOException $e) {
        // Database error
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

public function getAllExpenseCategories() { 
    try {
        $db = (new Database())->connect();
        $expenseCategory = new ExpenseCategory($db);
        // Fetch all expense categories
        $result = $expenseCategory->getAll();
        if ($result) {
            Response::json(["data" => $result], 200);
        } else {
            Response::json([
                "message" => "No expense categories found."
            ], 404);
        }
    } catch (PDOException $e) {
        // Database error
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

public function getAllInvoiceTypes() {
    try {
        $db = (new Database())->connect();
        $invoiceType = new InvoiceType($db);
        $result = $invoiceType->getAll();
        if ($result) {
            Response::json(["data" => $result], 200);
        } else {
            Response::json([
                "message" => "No invoice types found."
            ], 404);
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

public function getAllInvoiceStatuses() {
    try {
        $db = (new Database())->connect();
        $invoiceStatus = new InvoiceStatus($db);
        $result = $invoiceStatus->getAll();
        if ($result) {
            Response::json(["data" => $result], 200);
        } else {
            Response::json([
                "message" => "No invoice statuses found."
            ], 404);
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