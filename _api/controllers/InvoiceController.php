<?php  


require_once './models/Invoice.php';
require_once './config/database.php';
require_once './core/Response.php'; 

class InvoiceController {
    private $invoice;

    public function __construct() {
        $db = (new Database())->connect();
        $this->invoice = new Invoice($db);
    }

    public function createInvoice() {
        // Logic for creating an invoice
        $data = json_decode(file_get_contents("php://input"), true);
        $this->invoice->invoice_number = $data['invoice_number'] ?? '';
        $this->invoice->invoice_date = $data['invoice_date'] ?? '';
        $this->invoice->due_date = $data['due_date'] ?? '';
        $this->invoice->total_amount = $data['total_amount'] ?? 0;
        $this->invoice->notes = $data['notes'] ?? '';
        $this->invoice->client_id = $data['client_id'] ?? 0;
        $this->invoice->invoice_type = $data['invoice_type'] ?? 1;
       
        // Call the create method
        $result = $this->invoice->create();
        if ($result) {
            Response::json(["message" => "Invoice created successfully.", "invoice_id" => $result], 201);
        } else {
            Response::json($result);
        }
    }
    public function getInvoice($id) {
        try {
            $this->invoice->invoice_number = $id;
            $result = $this->invoice->getById();
            if ($result) {
                Response::json(["data" => $result], 200);
            } else {
                Response::json(["message" => "Invoice not found."], 404);
            }
        } catch (Exception $e) {
            Response::json(["message" => "Error fetching invoice: " . $e->getMessage()], 500);
        }
    }
    public function updateInvoice() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $this->invoice->invoice_number = $data['invoice_number'] ?? '';
            $this->invoice->invoice_date = $data['invoice_date'] ?? '';
            $this->invoice->due_date = $data['due_date'] ?? '';
            $this->invoice->total_amount = $data['total_amount'] ?? 0;
            $this->invoice->notes = $data['notes'] ?? '';
            $this->invoice->client_id = $data['client_id'] ?? 0;
            $this->invoice->invoice_type = $data['invoice_type'] ?? 1;

            $result = $this->invoice->update();
            if ($result) {
                Response::json(["message" => "Invoice updated successfully."], 200);
            } else {
                Response::json($result);
            }
        } catch (Exception $e) {
            Response::json(["message" => "Error updating invoice: " . $e->getMessage()], 500);
        }
    }
    public function deleteInvoice($id) {
        try {
            $this->invoice->invoice_number = $id;
            $result = $this->invoice->delete();
            if ($result) {
                Response::json(["message" => "Invoice deleted successfully."], 200);
            } else {
                Response::json($result);
            }
        } catch (Exception $e) {
            Response::json(["message" => "Error deleting invoice: " . $e->getMessage()], 500);
        }
    }
    public function getAllInvoices() {
    try {
        $db = (new Database())->connect();
        $this->invoice = new Invoice($db);
        $result = $this->invoice->getAll();
        if ($result) {
            Response::json(["data" => $result], 200);
        } else {
            Response::json(["message" => "No invoices found."], 404);
        }
    } catch (Exception $e) {
        Response::json(["message" => "Error fetching invoices: " . $e->getMessage()], 500);
    }
}
  
  
}
class ItemInvoiceController {
    private $itemInvoice;
    private $conn;

   
     public function __construct() {
        $db = (new Database())->connect();
        $this->conn = $db;
        $this->itemInvoice = new ItemInvoice($db);
    }

 public function createItemInvoice()
{
    try {
        $items = json_decode(file_get_contents("php://input"), true);

        if (!is_array($items) || empty($items)) {
            Response::json(["message" => "No items provided."], 400);
            return;
        }

        $this->conn->beginTransaction();
        $insertedItems = [];

        foreach ($items as $data) {
            if (empty($data['invoice_number']) || 
                empty($data['product_id']) ||
                empty($data['quantity']) || 
                empty($data['unit_price'])) {
                $this->conn->rollBack();
                Response::json([
                    "message" => "One of the items is missing required fields.",
                    "invalid_item" => $data
                ], 400);
                return;
            }

            $this->itemInvoice->invoice_number = $data['invoice_number'];
            $this->itemInvoice->product_id = $data['product_id'];
            $this->itemInvoice->quantity = $data['quantity'];
            $this->itemInvoice->unit_price = $data['unit_price'];
            $this->itemInvoice->invoice_type = $data['invoice_type'] ?? 0;
            $this->itemInvoice->tax_rate = $data['tax_rate'] ?? 0;
            $this->itemInvoice->discount = $data['discount'] ?? 0;

            $result = $this->itemInvoice->create();
            if (!$result) {
                $this->conn->rollBack();
                Response::json([
                    "message" => "Failed to insert one of the items.",
                    "failed_item" => $data
                ], 500);
                return;
            }
            $insertedItems[] = $result;
        }

        $this->conn->commit();
        Response::json([
            "message" => "All items inserted successfully.",
            "inserted_ids" => $insertedItems
        ], 201);

    } catch (Exception $e) {
        $this->conn->rollBack();
        Response::json(["message" => "Error creating item invoice: " . $e->getMessage()], 500);
    }
}

    public function getItemInvoices($invoice_number) {
        try {
            $this->itemInvoice->invoice_number = $invoice_number;
            $result = $this->itemInvoice->getAll();
            if ($result) {
                Response::json(["data" => $result], 200);
            } else {
                Response::json(["message" => "No items found for this invoice."], 404);
            }
        } catch (Exception $e) {
            Response::json(["message" => "Error fetching item invoices: " . $e->getMessage()], 500);
        }
    }

    public function deleteItemInvoice($item_id) {
        try {
            $this->itemInvoice->item_id = $item_id;
            $result = $this->itemInvoice->delete();
            if ($result) {
                Response::json(["message" => "Item invoice deleted successfully."], 200);
            } else {
                Response::json(["message" => "Failed to delete item invoice."], 400);
            }
        } catch (Exception $e) {
            Response::json(["message" => "Error deleting item invoice: " . $e->getMessage()], 500);
        }
    }
 
}