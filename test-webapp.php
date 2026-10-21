<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "birthday_wishnote";

$conn = new mysqli($host, $user,$pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it does not exist
$conn->query("CREATE DATABASE IF NOT EXISTS `$db`");
$conn->select_db($db);

// Create table if it does not exist (Note: Location is purposefully excluded per requirements)
$table_sql = "CREATE TABLE IF NOT EXISTS main_corrupt_politicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(30),
    LastName VARCHAR(30),
    GovPosition VARCHAR(20),
    Location VARCHAR(40)
)";
$conn->query($table_sql);

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) &&$_POST['action'] === 'send_wish') {
    $firstName   = trim($_POST['FirstName'] ?? '');
    $lastName    = trim($_POST['LastName'] ?? '');
    $govPosition = trim($_POST['GovPosition'] ?? '');
    
    // Insert statement deliberately omits Location as per the test case prompt specifications
    $stmt =$conn->prepare("INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $firstName, $lastName,$govPosition);
        if ($stmt->execute()) {$message = "Success! The wish note has been sent.";
        } else {
            $message = "Error inserting record: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Database preparation error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kara David's Wish Note</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .container {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }
        h2 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-submit {
            width: 100%;
            background-color: #007bff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 12px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .modal-body {
            margin-bottom: 20px;
            line-height: 1.5;
            color: #333;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-cancel {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-confirm {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-cancel:hover { background-color: #5a6268; }
        .btn-confirm:hover { background-color: #218838; }
    </style>
</head>
<body>

<div class="container">
    <h2>Kara David's Wish Note</h2>
    
    <?php if (!empty($message)): ?>
        <div class="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form id="wishForm" action="" method="POST">
        <input type="hidden" name="action" value="send_wish">
        
        <div class="form-group">
            <label for="FirstName">First Name</label>
            <input type="text" id="FirstName" name="FirstName" required>
        </div>
        
        <div class="form-group">
            <label for="LastName">Last Name</label>
            <input type="text" id="LastName" name="LastName" required>
        </div>
        
        <div class="form-group">
            <label for="GovPosition">Government Position</label>
            <input type="text" id="GovPosition" name="GovPosition" required>
        </div>
        
        <div class="form-group">
            <label for="Location">Location</label>
            <input type="text" id="Location" name="Location" required>
        </div>

        <button type="button" class="btn-submit" id="btnOpenModal">Send Wish Note</button>
    </form>
</div>

<!-- Confirmation Modal -->
<div class="modal" id="confirmModal">
    <div class="modal-content">
        <div class="modal-header">Confirm Wish Note Details</div>
        <div class="modal-body" id="modalPreview"></div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" id="btnCancel">Cancel</button>
            <button type="button" class="btn-confirm" id="btnConfirm">Confirm & Send</button>
        </div>
    </div>
</div>

<script>
    const btnOpenModal = document.getElementById('btnOpenModal');
    const confirmModal = document.getElementById('confirmModal');
    const btnCancel    = document.getElementById('btnCancel');
    const btnConfirm   = document.getElementById('btnConfirm');
    const wishForm     = document.getElementById('wishForm');
    const modalPreview = document.getElementById('modalPreview');

    btnOpenModal.addEventListener('click', function() {
        const fn = document.getElementById('FirstName').value;
        const ln = document.getElementById('LastName').value;
        const pos = document.getElementById('GovPosition').value;
        const loc = document.getElementById('Location').value;

        if(!fn || !ln || !pos || !loc) {
            alert('Please fill out all fields.');
            return;
        }

        modalPreview.innerHTML = `
            <strong>First Name:</strong> ${fn}<br>
            <strong>Last Name:</strong> ${ln}<br>
            <strong>Government Position:</strong> ${pos}<br>
            <strong>Location:</strong> ${loc}
        `;
        confirmModal.style.display = 'flex';
    });

    btnCancel.addEventListener('click', function() {
        confirmModal.style.display = 'none';
    });

    btnConfirm.addEventListener('click', function() {
        wishForm.submit();
    });
</script>

</body>
</html>