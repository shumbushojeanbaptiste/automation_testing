<?php
require_once './models/Payments.php';
require_once './config/database.php';
require_once './core/Response.php';

class PaymentController {
    private $payment;

    public function __construct() {
        $db = (new Database())->connect();
        $this->payment = new Payments($db);
    }

    public function registerOutgoingInvoPayment() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            // Set payment properties
            $this->payment->debit_account_id = $data['debit_account_id'] ?? '';
            $this->payment->invoice_id = $data['invoice_id'] ?? '';
            $this->payment->payment_mode = $data['payment_mode'] ?? '';
            $this->payment->amount = $data['amount'] ?? 0;
            $this->payment->payment_date = $data['payment_date'] ?? date('Y-m-d');
            $this->payment->notes = $data['notes'] ?? '';
            $this->payment->client_id = $data['client_id'] ?? 0;
            $this->payment->received_by = $data['received_by'] ?? 0;
            
            // Call registerPayment method
            $result = $this->payment->registerInPayment();

            if ($result) {
                Response::json(["message" => "Payment registered successfully.", "transaction_code" => $result], 201);
            } else {
                Response::json(["error" => "Failed to register payment."], 400);
            }
        } catch (Exception $e) {
            Response::json(["error" => "Server error ", "details" => $e->getMessage()], 500);
        }
    }
    
    public function registerIncomingInvoPayment() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            // Set payment properties
            $this->payment->credit_account_id = $data['credit_account_id'] ?? '';
            $this->payment->invoice_id = $data['invoice_id'] ?? '';
            $this->payment->payment_mode = $data['payment_mode'] ?? '';
            $this->payment->amount = $data['amount'] ?? 0;
            $this->payment->payment_date = $data['payment_date'] ?? date('Y-m-d');
            $this->payment->notes = $data['notes'] ?? '';
            $this->payment->client_id = $data['client_id'] ?? 0;
            $this->payment->received_by = $data['received_by'] ?? 0;
            
            // Call registerPayment method
            $result = $this->payment->registerOutPayment();

            if ($result) {
                Response::json(["message" => "Payment registered successfully.", "transaction_code" => $result], 201);
            } else {
                Response::json(["error" => "Failed to register payment."], 400);
            }
        } catch (Exception $e) {
            Response::json(["error" => "Server error ", "details" => $e->getMessage()], 500);
        }
    }
    public function getAllPayments() {
        try {
            $payments = $this->payment->getAllPayments();
            if ($payments) {
                Response::json($payments, 200);
            } else {
                Response::json(["message" => "No payments found."], 404);
            }
        } catch (Exception $e) {
            Response::json(["error" => "Server error", "details" => $e->getMessage()], 500);
        }
    }
    public function getPaymentById() {
        $data = json_decode(file_get_contents("php://input"), true);
        $center_id = isset($data['center_id']) ? $data['center_id'] : null;
        try {
            $payment = $this->payment->getPaymentById($center_id);
            if ($payment) {
                Response::json($payment, 200);
            } else {
                Response::json(["message" => "Payment not found."], 404);
            }
        } catch (Exception $e) {
            Response::json(["error" => "Server error", "details" => $e->getMessage()], 500);
        }
    }
}