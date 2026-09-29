
<?php

require_once "includes/auth.php";
require_once "includes/db.php";

$message = "";

$user_id = $_SESSION["user_id"];


/*
 * Get current user information.
 */
$stmt = $conn->prepare(
    "SELECT name, email, department
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();


/*
 * Update profile.
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $department = trim($_POST["department"]);


    if (
        empty($name) ||
        empty($email) ||
        empty($department)
    ) {

        $message = "Please fill in all fields.";

    } else {

        /*
         * Check whether another user
         * already has this email.
         */
        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             AND id != ?"
        );

        $check->bind_param(
            "si",
            $email,
            $user_id
        );

        $check->execute();

        $check_result = $check->get_result();


        if ($check_result->num_rows > 0) {

            $message =
                "That email address is already in use.";

        } else {

            /*
             * Update profile information.
             */
            $update = $conn->prepare(
                "UPDATE users
                 SET name = ?,
                     email = ?,
                     department = ?
                 WHERE id = ?"
            );

            $update->bind_param(
                "sssi",
                $name,
                $email,
                $department,
                $user_id
            );


            if ($update->execute()) {

                /*
                 * Update session information.
                 */
                $_SESSION["user_name"] = $name;
                $_SESSION["user_email"] = $email;


                $message =
                    "Profile updated successfully.";


                /*
                 * Refresh displayed information.
                 */
                $user["name"] = $name;
                $user["email"] = $email;
                $user["department"] = $department;

            } else {

                $message =
                    "Profile update failed.";
            }


            $update->close();
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

    <title>MeetSync - My Profile</title>

    <link rel="stylesheet" href="css/style.css">

<script src="js/app.js"></script>

</head>


<body>

    <div class="container">

        <div class="profile-header">

            <h2>My Profile</h2>

            <p>
                Manage your MeetSync account information.
            </p>

        </div>


        <div class="profile-card">


            <?php if (!empty($message)): ?>

                <div class="message">

                    <?php
                    echo htmlspecialchars($message);
                    ?>

                </div>

            <?php endif; ?>


            <form method="POST">


                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php
                            echo htmlspecialchars(
                                $user["name"]
                            );
                        ?>"
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
                        value="<?php
                            echo htmlspecialchars(
                                $user["email"]
                            );
                        ?>"
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
                        value="<?php
                            echo htmlspecialchars(
                                $user["department"] ?? ""
                            );
                        ?>"
                        placeholder="e.g. ICT"
                        required
                    >

                </div>


                <button type="submit">
                    Update Profile
                </button>


            </form>


            <a href="dashboard.php" class="back-link">
                ← Back to Dashboard
            </a>


        </div>

    </div>

</body>

</html>
