<?php

require_once "includes/auth.php";
require_once "includes/db.php";

$message = "";
$message_type = "";

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {

        $message = "Please fill in all fields.";
        $message_type = "error";

    } elseif ($new_password !== $confirm_password) {

        $message = "New passwords do not match.";
        $message_type = "error";

    } elseif (strlen($new_password) < 8) {

        $message = "New password must be at least 8 characters.";
        $message_type = "error";

    } else {

        // Get the user's current password
        $stmt = $conn->prepare(
            "SELECT password FROM users WHERE id = ?"
        );

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        if (!$user || !password_verify($current_password, $user["password"])) {

            $message = "Current password is incorrect.";
            $message_type = "error";

        } else {

            // Hash the new password
            $hashed_password = password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );

            // Update password
            $update = $conn->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );

            $update->bind_param(
                "si",
                $hashed_password,
                $user_id
            );

            if ($update->execute()) {

                $message = "Password changed successfully.";
                $message_type = "success";

            } else {

                $message = "Password change failed. Please try again.";
                $message_type = "error";
            }

            $update->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>MeetSync - Change Password</title>

    <link rel="stylesheet" href="css/style.css">

<script src="js/app.js"></script>

</head>

<body>

    <div class="container">

        <div class="password-header">

            <h2>Change Password</h2>

            <p>
                Update your MeetSync account password securely.
            </p>

        </div>


        <div class="password-card">


            <?php if (!empty($message)): ?>

                <div class="message <?php echo htmlspecialchars($message_type); ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <form method="POST">


                <div class="form-group">

                    <label for="current_password">
                        Current Password
                    </label>

                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="new_password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        required
                    >

                </div>


                <div class="show-password">

                    <input
                        type="checkbox"
                        id="showPasswords"
                    >

                    <label for="showPasswords">
                        Show passwords
                    </label>

                </div>


                <button type="submit">
                    Change Password
                </button>


            </form>


            <a href="dashboard.php" class="back-link">
                ← Back to Dashboard
            </a>


        </div>

    </div>


    <script>

        const showPasswords = document.getElementById("showPasswords");

        const passwordFields = [
            document.getElementById("current_password"),
            document.getElementById("new_password"),
            document.getElementById("confirm_password")
        ];

        showPasswords.addEventListener("change", function () {

            passwordFields.forEach(function (field) {

                field.type = this.checked ? "text" : "password";

            }, this);

        });

    </script>

</body>

</html>