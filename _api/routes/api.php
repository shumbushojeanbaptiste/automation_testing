<?php

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove "/public" or subdirectory from URI if hosted under subfolder
$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$request = str_replace($basePath, '', $requestUri);

// Normalize request URI
$request = rtrim($request, '/') ?: '/';

switch (true) {
    case $request === '/':
        echo json_encode(['message' => 'Welcome to internal finance API']);
        break;

    case $request === '/auth':
        echo json_encode(['message' => 'Welcome to the Auth API']);
        break;
        // Authentication routes
    case $request === '/auth/register' && $method === 'POST':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthController())->register();
        break;
    case $request === '/auth/edit' && $method === 'PUT':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthController())->updateUser();
        break;
     case $request === '/auth/list' && $method === 'GET':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthController())->getAllUsers();
        break;
    case $request === '/auth/login' && $method === 'POST':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthanticationChecker())->login();
        break;
    // user profile

    case $request === '/auth/profile' && $method === 'GET':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthanticationChecker())->getUserProfile();
        break;
    case $request === '/auth/logout' && $method === 'POST':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthanticationChecker())->logout();
        break;
    
    // Clients routes
    case $request === '/clients/register' && $method === 'POST':
        require_once __DIR__ . '/../controllers/ClientsController.php';
        (new ClientsController())->register();
        break;
    case $request === '/clients/update' && $method === 'PUT':
        require_once __DIR__ . '/../controllers/ClientsController.php';
        (new ClientsController())->updateClient();
        break;
    case $request === '/clients/list' && $method === 'GET':
        require_once __DIR__ . '/../controllers/ClientsController.php';
        (new ClientsController())->getAllClients();
        break;
    case $request === '/type/clients/list' && $method === 'GET':
        require_once __DIR__ . '/../controllers/ClientsController.php';
        (new ClientsController())->getAllClientsType();
        break;
    
    // general settings routes
    case $request === '/general/settings/roles' && $method === 'GET':
        require_once __DIR__ . '/../controllers/SettingController.php';
        (new SettingController())->getAllRoles();
        break;
    case $request === '/general/settings/payment_methods' && $method === 'GET':
        require_once __DIR__ . '/../controllers/SettingController.php';
        (new SettingController())->getAllPaymentMethods();
        break;
    case $request === '/general/settings/classification' && $method === 'GET':
        require_once __DIR__ . '/../controllers/SettingController.php';
        (new SettingController())->getAllClassifications();
        break;
    case $request === '/general/settings/expense_categories' && $method === 'GET':
        require_once __DIR__ . '/../controllers/SettingController.php';
        (new SettingController())->getAllExpenseCategories();
        break;
    case $request === '/general/settings/invoice_types' && $method === 'GET':
        require_once __DIR__ . '/../controllers/SettingController.php';
        (new SettingController())->getAllInvoiceTypes();
        break;
    // Default case for unmatched routes
    case $request === '/general/settings/invoice_statuses' && $method === 'GET':
        require_once __DIR__ . '/../controllers/SettingController.php';
        (new SettingController())->getAllInvoiceStatuses();
        break;
    // Invoice creation routes
    case $request === '/invoices/creation' && $method === 'POST':
        require_once __DIR__ . '/../controllers/InvoiceController.php';
        (new InvoiceController())->createInvoice();
        break;
    case $request === '/invoices/list' && $method === 'GET':
        require_once __DIR__ . '/../controllers/InvoiceController.php';
        (new InvoiceController())->getAllInvoices();
        break;
    case $request === '/invoices/edit' && $method === 'PUT':
        require_once __DIR__ . '/../controllers/InvoiceController.php';
        (new InvoiceController())->updateInvoice();
        break;
    case $request === '/invoices/cancel' && $method === 'DELETE':
        require_once __DIR__ . '/../controllers/InvoiceController.php';
        (new InvoiceController())->deleteInvoice();
        break;
    case $request === '/invoices/item/create' && $method === 'POST':
        require_once __DIR__ . '/../controllers/InvoiceController.php';
        (new ItemInvoiceController())->createItemInvoice();
        break;
    case preg_match('/^\/invoices\/item\/list\/(\d+)$/', $request, $matches) && $method === 'GET':
        require_once __DIR__ . '/../controllers/InvoiceController.php';
        (new ItemInvoiceController())->getItemInvoices($matches[1]);
        break;

    // products routes
    case $request === '/products/register' && $method === 'POST':
        require_once __DIR__ . '/../controllers/ProductController.php';
        (new ProductController())->registerProduct();
        break;
    case $request === '/products/edit' && $method === 'PUT':
        require_once __DIR__ . '/../controllers/ProductController.php';
        (new ProductController())->updateProduct();
        break;
    case $request === '/products/list' && $method === 'GET':
        require_once __DIR__ . '/../controllers/ProductController.php';
        (new ProductController())->getAllProducts();
        break;
    // project routes
    case $request === '/projects/register' && $method === 'POST':
        require_once __DIR__ . '/../controllers/ProjectController.php';
        (new ProjectController())->registerProject();
        break;
    case $request === '/projects/edit' && $method === 'PUT':
        require_once __DIR__ . '/../controllers/ProjectController.php';
        (new ProjectController())->updateProject();
        break;
    case $request === '/projects/list' && $method === 'GET':
        require_once __DIR__ . '/../controllers/ProjectController.php';
        (new ProjectController())->getAllProjects();
        break;
    case $request === '/projects/assignation' && $method === 'POST':
        require_once __DIR__ . '/../controllers/ProjectController.php';
        (new ProjectController())->assignation();
        break;
    
     case $request === '/projects/assignation/list' && $method === 'GET':
        require_once __DIR__ . '/../controllers/ProjectController.php';
        (new ProjectController())->getAllAssignments();
        break;
    case preg_match('#^/projects/assignation/delete/(\d+)$#', $request, $matches) && $method === 'DELETE':
        $id = (int) $matches[1];
        require_once __DIR__ . '/../controllers/ProjectController.php';
        (new ProjectController())->deleteAssignment($id);
        break;

    // payments routes
    case $request === '/payments/outgoing/register' && $method === 'POST':
        require_once __DIR__ . '/../controllers/PaymentController.php';
        (new PaymentController())->registerOutgoingInvoPayment();
        break;
    case $request === '/payments/incoming/register' && $method === 'POST':
        require_once __DIR__ . '/../controllers/PaymentController.php';
        (new PaymentController())->registerIncomingInvoPayment();
        break;
    case $request === '/payments/alllist' && $method === 'GET':
        require_once __DIR__ . '/../controllers/PaymentController.php';
        (new PaymentController())->getAllPayments();
        break;
    // expense routes
    case $request === '/expenses/register' && $method === 'POST':
        require_once __DIR__ . '/../controllers/ExpenseController.php';
        (new ExpenseController())->registerExpense();
        break;
    case $request === '/expenses/edit' && $method === 'PUT':
        require_once __DIR__ . '/../controllers/ExpenseController.php';
        (new ExpenseController())->updateExpense();
        break;
    case $request === '/expenses/list' && $method === 'GET':
        require_once __DIR__ . '/../controllers/ExpenseController.php';
        (new ExpenseController())->getAllExpenses();
        break;
    case preg_match('#^/expenses/delete/(\d+)$#', $request, $matches) && $method === 'DELETE':
        $id = (int) $matches[1];
        require_once __DIR__ . '/../controllers/ExpenseController.php';
        (new ExpenseController())->deleteExpense($id);
        break;

    default:
        http_response_code(404);
        echo json_encode(['message' => 'Route not found']);
        break;
}
