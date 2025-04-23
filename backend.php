<?php
session_start();
header('Content-Type: application/json');

class Database {
    private $host = 'localhost';
    private $db_name = 'attsans_apartment';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo json_encode(['error' => "Connection failed: " . $e->getMessage()]);
            exit;
        }
        return $this->conn;
    }
}

class User {
    private $conn;
    private $table = 'users';

    public $first_name, $last_name, $username, $email, $password, $phone_number, $dob, $agreed_terms;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function signUp() {
        $query = "INSERT INTO $this->table (first_name, last_name, username, email, password, phone_number, date_of_birth, agreed_terms)
                  VALUES(:first_name, :last_name, :username, :email, :password, :phone_number, :dob, :agreed_terms)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':phone_number', $this->phone_number);
        $stmt->bindParam(':dob', $this->dob);
        $stmt->bindParam(':agreed_terms', $this->agreed_terms);

        return $stmt->execute();
    }

    public function login() {
        $query = "SELECT * FROM $this->table WHERE username = :identifier OR email = :identifier";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':identifier', $this->username);
        $stmt->execute();

        if ($stmt->rowCount()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($this->password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function getUserInfo($user_id) {
        $query = "SELECT first_name, last_name, email, phone_number FROM $this->table WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

class PropertyManager {
    private $conn;

    public function __construct($db) {
        $this->conn = $db->connect();
    }

    public function getRoomById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM properties WHERE property_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        return $room ?: ["error" => "Room not found"];
    }

    public function getRandomRooms($limit = 2) {
        $stmt = $this->conn->prepare("SELECT * FROM properties ORDER BY RAND() LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchAllRooms() {
        try {
            $stmt = $this->conn->query("SELECT * FROM properties");
            return ['success' => true, 'rooms' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function submitInquiry($userId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gov_id']) && $_FILES['gov_id']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/id/';
            $filename = uniqid() . '_' . basename($_FILES['gov_id']['name']);
            $targetFile = $uploadDir . $filename;
    
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
    
            if (move_uploaded_file($_FILES['gov_id']['tmp_name'], $targetFile)) {
                $stmt = $this->conn->prepare("
                    INSERT INTO inquiries (user_id, property_id, emergency_first_name, emergency_last_name, emergency_contact, valid_id_filename, move_in_date, agreed_rules, status, inquiry_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
    
                $property_id = $_POST['property_id'];
                $emergency_first_name = $_POST['emergency_first_name'];
                $emergency_last_name = $_POST['emergency_last_name'];
                $emergency_contact = $_POST['emergency_contact'];
                $move_in_date = $_POST['move_in_date'];
                $agreed_rules = isset($_POST['agree_rules']) ? 1 : 0;
    
                $stmt->bind_param("iisssssi", 
                    $userId, 
                    $property_id, 
                    $emergency_first_name, 
                    $emergency_last_name, 
                    $emergency_contact, 
                    $filename, 
                    $move_in_date, 
                    $agreed_rules
                );
    
                if ($stmt->execute()) {
                    echo json_encode(["status" => "success", "message" => "Inquiry submitted successfully."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Failed to insert inquiry."]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to upload ID."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Incomplete form or missing file."]);
        }
    }
}


// Instantiate DB and User class
$database = new Database();
$db = $database->connect();
$user = new User($db);
$propertyManager = new PropertyManager($database);

// Handle GET: Check login status
if (isset($_GET['check_login_status'])) {
    echo json_encode([
        'logged_in' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null
    ]);
    exit;
}

// Handle GET: Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (isset($_SESSION['login_history_id'])) {
        $stmt = $db->prepare("UPDATE login_history SET logout_time = NOW() WHERE login_id = ?");
        $stmt->execute([$_SESSION['login_history_id']]);
    }

    session_unset();
    session_destroy();

    echo json_encode(['logout' => true]);
    exit;
}

// Handle GET: Get user data
if (isset($_GET['action']) && $_GET['action'] === 'get_user_info') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'User not logged in']);
        exit;
    }

    $info = $user->getUserInfo($_SESSION['user_id']);
    echo json_encode($info ?: ['error' => 'User not found']);
    exit;
}

// Handle POST: Sign up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'signup') {
    $dob = new DateTime($_POST['dob']);
    $age = (new DateTime())->diff($dob)->y;

    if ($age < 18) {
        echo json_encode(['error' => 'You must be at least 18 years old to sign up.']);
        exit;
    }

    if ($_POST['password'] !== $_POST['confirm_password']) {
        echo json_encode(['error' => 'Passwords do not match.']);
        exit;
    }

    if (strlen($_POST['password']) < 8) {
        echo json_encode(['error' => 'Password must be at least 8 characters.']);
        exit;
    }

    // Check if username or email already exists
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
    $stmt->execute([
        ':username' => $_POST['username'],
        ':email' => $_POST['email']
    ]);

    if ($stmt->rowCount()) {
        echo json_encode(['error' => 'Username or email already taken']);
        exit;
    }

    $user->first_name = $_POST['first_name'];
    $user->last_name = $_POST['last_name'];
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];
    $user->password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $user->phone_number = $_POST['phone_number'];
    $user->dob = $_POST['dob'];
    $user->agreed_terms = isset($_POST['agreed_terms']) ? 'Yes' : 'No';

    echo json_encode(['success' => $user->signUp()]);
    exit;
}

// Handle POST: Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'login') {
    $user->username = $_POST['username_email'];
    $user->password = $_POST['password'];

    $found = $user->login();
    if ($found) {
        $_SESSION['user_id'] = $found['user_id'];
        $_SESSION['first_name'] = $found['first_name'];
        $_SESSION['last_name'] = $found['last_name'];
        $_SESSION['email'] = $found['email'];
        $_SESSION['phone_number'] = $found['phone_number'];
        $_SESSION['username'] = $found['username'];

        // Track login time
        $stmt = $db->prepare("INSERT INTO login_history (user_id, login_time) VALUES (?, NOW())");
        $stmt->execute([$found['user_id']]);
        $_SESSION['login_history_id'] = $db->lastInsertId();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Login failed.']);
    }
    exit;
}


// Route based on query parameters
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_room':
            if (isset($_GET['id'])) {
                echo json_encode($propertyManager->getRoomById($_GET['id']));
            } else {
                echo json_encode(["error" => "No ID provided"]);
            }
            break;

        case 'get_random_rooms':
            echo json_encode($propertyManager->getRandomRooms());
            break;

        case 'fetch_rooms':
                $result = $propertyManager->fetchAllRooms();
                echo json_encode($result['success'] ? $result['rooms'] : ['error' => $result['error']]);
        break;
                
            

        default:
            echo json_encode(["error" => "Invalid action"]);
    }
}

// Handle POST: Submit inquiry / inquiry form
if ($_POST['action'] === 'submit_inquiry') {
    session_start();
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        $propertyManager->submitInquiry($userId);
    } else {
        echo json_encode(["status" => "error", "message" => "Not logged in."]);
    }
}

?>
