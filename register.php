<?php
// register.php
session_start();
require_once 'db.php';

// Helper to send JSON and exit
function send_json($data, $status = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// detect request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// If POST, process registration
if ($method === 'POST') {
    // read body (support JSON)
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data)) {
        // fallback to form data
        $data = $_POST;
    }

    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? $data['password'] : '';
    $confirm = isset($data['confirm_password']) ? $data['confirm_password'] : '';

    // validation
    $errors = [];
    if ($email === '') $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if ($password === '') $errors[] = 'Password is required.';
    elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!empty($errors)) {
        // respond JSON for API, or show errors for browser
        if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            send_json(['status' => 'error', 'errors' => $errors], 422);
        } else {
            // set $form_errors for HTML render below
            $form_errors = $errors;
        }
    } else {
        // check duplicate
        if ($stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1')) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->close();
                $err = 'Email already registered.';
                if (isset($form_errors)) {
                    $form_errors = [$err];
                } else {
                    send_json(['status' => 'error', 'message' => $err], 409);
                }
            } else {
                $stmt->close();
                // insert
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($ins = $conn->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)')) {
                    $ins->bind_param('ss', $email, $hash);
                    if ($ins->execute()) {
                        $user_id = $ins->insert_id;
                        $ins->close();
                        if (isset($form_errors)) {
                            $success_msg = 'Registration successful. You can now log in.';
                        } else {
                            send_json(['status' => 'success', 'message' => 'User registered', 'user_id' => $user_id], 201);
                        }
                    } else {
                        $ins->close();
                        if (isset($form_errors)) {
                            $form_errors = ['Registration failed.'];
                        } else {
                            send_json(['status' => 'error', 'message' => 'Registration failed'], 500);
                        }
                    }
                } else {
                    if (isset($form_errors)) {
                        $form_errors = ['Database error (prepare failed).'];
                    } else {
                        send_json(['status' => 'error', 'message' => 'Database error'], 500);
                    }
                }
            }
        } else {
            if (isset($form_errors)) {
                $form_errors = ['Database error (prepare failed).'];
            } else {
                send_json(['status' => 'error', 'message' => 'Database error'], 500);
            }
        }
    }
}

// If not JSON POST, show simple HTML form (or if $form_errors set)
if ($method !== 'POST' || isset($form_errors) || isset($success_msg)) {
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <title>Register</title>
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:20px;}
        .form{max-width:420px;margin:0 auto;}
        input{width:100%;padding:8px;margin:6px 0;box-sizing:border-box;}
        .err{background:#ffe6e6;border:1px solid #ffb3b3;padding:10px;margin-bottom:10px;}
        .ok{background:#e6ffea;border:1px solid #b3ffcc;padding:10px;margin-bottom:10px;}
        button{padding:10px 16px;}
      </style>
    </head>
    <body>
      <div class="form">
        <h2>Create account</h2>

        <?php if (!empty($form_errors)): ?>
          <div class="err"><ul><?php foreach ($form_errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
          <div class="ok"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <form method="post" action="">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

          <label for="password">Password (min 6 chars)</label>
          <input id="password" name="password" type="password" required>

          <label for="confirm_password">Confirm password</label>
          <input id="confirm_password" name="confirm_password" type="password" required>

          <button type="submit">Register</button>
        </form>
      </div>
    </body>
    </html>
    <?php
    exit;
}
?>
