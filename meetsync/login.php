<?php

session_start();

require_once "includes/db.php";
require_once "includes/activity_log.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                logActivity(
                    $conn,
                    $user["id"],
                    "User Login",
                    "User logged into the system."
                );

                header("Location: dashboard.php");
                exit;

            } else {

                $message = "Invalid email or password.";
            }

        } else {

            $message = "Invalid email or password.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>MeetSync - Login</title>

<link rel="stylesheet"
      href="css/style.css?">


<script src="js/app.js"></script>

</head>

<body>

<header class="site-header">


<div class="container">

    <div class="navbar">

        <div>

            <h1>MeetSync</h1>

            <p>
                Meeting Scheduling and Coordination System
            </p>

        </div>

    </div>

</div>


</header>

<main class="container">


<div class="form-container">

    <div class="card">

        <h2>Sign In</h2>

        <p>
            Sign in to access your MeetSync account.
        </p>


        <?php if (!empty($message)): ?>

            <div class="message message-error">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <form method="POST" action="">


            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="email"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >

            </div>


            <button type="submit"
                    class="btn">

                Login

            </button>


        </form>


        <p>

            Don't have an account?

            <a href="register.php">
                Register here
            </a>

        </p>

    </div>

</div>


</main>

<footer class="site-footer">


<div class="container">

    <p>
        MeetSync &copy; <?php echo date("Y"); ?>
        — Meeting Scheduling and Coordination System
    </p>

</div>


</footer>

</body>

</html>
