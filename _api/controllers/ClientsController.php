<?php
require_once './models/Clients.php';
require_once './config/database.php';
require_once './core/Response.php';

class ClientsController {

    private $client;
    public function __construct() {
        $db = (new Database())->connect();
        $this->client = new Client($db);
    }

    public function register() {
        // Get input data
        $data = json_decode(file_get_contents("php://input"), true);
        // Set client properties
        $this->client->client_code = $data['client_code'];
        $this->client->first_name = $data['first_name'];
        $this->client->last_name = $data['last_name'];
        $this->client->phone = $data['phone'];
        $this->client->client_type = $data['client_type'];
        $this->client->email = $data['email'];
        $this->client->tin_number = $data['tin_number'];
        $this->client->financial_type = $data['financial_type'];
        $this->client->location_address = $data['location_address'];

        // Call create method
        $result = $this->client->create();

        // Return response
        if ($result === true) {
            Response::json(["message" => "Client registered successfully."], 201);
        } else {
            Response::json(["error" => $result], 400);
        }
    }

    public function updateClient() {
        // Get input data
        $data = json_decode(file_get_contents("php://input"), true);

        // Set client properties  use like this $data['role_name'];
        if (!isset($data['client_id']) || !isset($data['first_name']) || !isset($data['last_name']) || !isset($data['phone']) || !isset($data['tin_number']) || !isset($data['email'])) {
            Response::json(["error" => "Missing required fields."], 400);
            return;
        }
        $this->client->id = $data['client_id'] ;
        $this->client->first_name = $data['first_name'];
        $this->client->last_name = $data['last_name'];
        $this->client->phone = $data['phone'];
        $this->client->client_type = $data['client_type'];
        $this->client->email = $data['email'];
        $this->client->tin_number = $data['tin_number'];
        $this->client->financial_type = $data['financial_type'];
        $this->client->location_address = $data['location_address'];
        // Check if ID is provided
        if (!isset($data['client_id'])) {
            Response::json(["error" => "Missing required field: client_id."], 400);
            return;
        }

        // Call update method
        $result = $this->client->update($data['client_id']);

        // Return response
        if ($result === true) {
            Response::json(["message" => "Client updated successfully."], 200);
        } else {
            Response::json(["error" => $result], 400);
        }
    }
  
    
       

    public function getAllClients() {
        $result = $this->client->getAll(); 

        if (is_array($result)) {
            Response::json(["data" => $result], 200);
        } else {
            Response::json(["error" => $result], 400); 
        }
    }
    public function getAllClientsType() {
        $result = $this->client->getAllClientsType();

        if (is_array($result)) {
            Response::json(["data" => $result], 200);
        } else {
            Response::json(["error" => $result], 400);
        }
    }
    public function getClientById($id) {
        $this->client->id = $id;
        $result = $this->client->getById();

        if ($result) {
            Response::json(["data" => $result], 200);
        } else {
            Response::json(["error" => "Client not found."], 404);
        }
        }

    }
