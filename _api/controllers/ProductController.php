<?php
require_once './models/Product.php';
require_once './config/database.php';
require_once './core/Response.php';
class ProductController
{
    public function registerProduct()
    {
        // Logic for registering a product
        $data = json_decode(file_get_contents("php://input"), true);
        $db = (new Database())->connect();
        $product = new Product($db);

        $product->product_name = $data['product_name'];
        $product->description = $data['description'];

         $result = $product->create();

         if ($result === true) {
    Response::json([
        "message" => "{$product->product_name} product registered successfully."
    ], 201);
} else {
    Response::json([
        "message" => "Failed to register product.",
        "error" => $result
    ], 400);
}


    }

    public function updateProduct(){
 try {
        $data = json_decode(file_get_contents("php://input"), true);
        $db = (new Database())->connect();
        $product = new Product($db);

        // Populate student fields
        $product->product_name = $data['product_name'];
        $product->description = $data['description'];
        $product->product_id = $data['product_id'];

        // Validate required fields
        if (empty($product->product_name) || empty($product->description)) {
            Response::json([
                "error" => "Missing required fields: product_name, description"
            ], 400);
            return;
        }

        // Call update method and get result
        $result = $product->update($product->product_id);

        if ($result === true) {
            Response::json(["message" => "product updated successfully."], 200);
        } else {
            // Return the actual error message from update() function
            Response::json([
                "error" => is_string($result) ? $result : "Failed to update product."
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
    public function deleteProduct($id)
    {
        try {
            $db = (new Database())->connect();
            $product = new Product($db);
            $result = $product->delete($id);
            if ($result === true) {
                Response::json(["message" => "Product deleted successfully."], 200);
            } else {
                Response::json(["message" => "Failed to delete product."], 400);
            }
        } catch (PDOException $e) {
            Response::json([
                "error" => "Database error",
                "details" => $e->getMessage()
            ], 500);
        }
    }

    public function getAllProducts()
    {
        try {
            $db = (new Database())->connect();
            $product = new Product($db);
            $result = $product->getAll();

            if ($result) {
                Response::json($result, 200);
            } else {
                Response::json(["message" => "No products found."], 404);
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