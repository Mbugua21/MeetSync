<?php

require_once "includes/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $department = trim($_POST["department"]);
    $password = $_POST["password"];


    if (
        empty($name) ||
        empty($email) ||
        empty($department) ||
        empty($password)
    ) {

        $message = "Please fill in all fields.";

    } else {

        // Check whether email already exists
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();


        if ($result->num_rows > 0) {

            $message = "This email is already registered.";

        } else {

            // Securely hash the password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // Insert new user
            $stmt = $conn->prepare(
                "INSERT INTO users
                (name, email, department, password)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $department,
                $hashed_password
            );


            if ($stmt->execute()) {

                $message =
                    "Registration successful! You can now log in.";

            } else {

                $message =
                    "Registration failed. Please try again.";
            }


            $stmt->close();
        }


        $check->close();
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>MeetSync - Registration</title>

<link rel="stylesheet"
      href="css/style.css">


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

        <div>

            <a href="login.php">
                Login
            </a>

        </div>

    </div>

</div>

</header>

<main class="container">


<div class="form-container">

    <div class="card">

        <h2>Create Your Account</h2>

        <p>
            Register to use the MeetSync meeting scheduling system.
        </p>


        <?php if (!empty($message)): ?>

            <div class="message
                <?php
                if (
                    strpos(
                        $message,
                        "successful"
                    ) !== false
                ) {
                    echo "message-success";
                } else {
                    echo "message-error";
                }
                ?>
            ">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <form method="POST" action="">


            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    autocomplete="name"
                    required
                >

            </div>


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

                <label for="department">
                    Department
                </label>

                <input
                    type="text"
                    id="department"
                    name="department"
                    placeholder="e.g. ICT"
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
                    autocomplete="new-password"
                    required
                >

            </div>


            <button type="submit"
                    class="btn">

                Register

            </button>


        </form>


        <p>

            Already have an account?

            <a href="login.php">
                Login here
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
