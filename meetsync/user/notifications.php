<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

$user_id = $_SESSION["user_id"];

/*
 * Get notifications for the logged-in user.
 */
$stmt = $conn->prepare(
    "SELECT
        n.id,
        n.message,
        n.is_read,
        n.created_at,
        m.title AS meeting_title
     FROM notifications n
     LEFT JOIN meetings m
        ON n.meeting_id = m.id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>MeetSync - Notifications</title>

<link
    rel="stylesheet"
    href="../css/style.css"
>

<script src="../js/app.js"></script>

</head>

<body>

<header class="site-header">

<div class="container">

    <h1>MeetSync</h1>

    <nav class="navbar">

        <a href="../dashboard.php">
            Dashboard
        </a>

        <a href="my_meetings.php">
            My Meetings
        </a>

        <a href="my_invitations.php">
            My Invitations
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </nav>

</div>

</header>

<main class="container">

<div class="section-header">

    <h2>Notifications</h2>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION["user_name"]); ?>.
    </p>

</div>


<?php if ($result->num_rows == 0): ?>

    <div class="message message-info">

        You have no notifications.

    </div>

<?php else: ?>

    <div class="meeting-list">

        <?php while ($notification = $result->fetch_assoc()): ?>

            <div class="card">

                <p>
                    <strong>Message:</strong>

                    <?php
                    echo htmlspecialchars(
                        $notification["message"]
                    );
                    ?>
                </p>


                <?php if (!empty($notification["meeting_title"])): ?>

                    <p>
                        <strong>Meeting:</strong>

                        <?php
                        echo htmlspecialchars(
                            $notification["meeting_title"]
                        );
                        ?>
                    </p>

                <?php endif; ?>


                <p>
                    <strong>Date:</strong>

                    <?php
                    echo htmlspecialchars(
                        $notification["created_at"]
                    );
                    ?>
                </p>


                <?php if ($notification["is_read"] == 0): ?>

                    <p>

                        <strong>Status:</strong>

                        <span class="status status-pending">
                            Unread
                        </span>

                    </p>

                    <a
                        href="mark_notification_read.php?id=<?php echo (int) $notification["id"]; ?>"
                        class="btn btn-success"
                    >
                        Mark as Read
                    </a>

                <?php else: ?>

                    <p>

                        <strong>Status:</strong>

                        <span class="status status-completed">
                            Read
                        </span>

                    </p>

                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    </div>

<?php endif; ?>


<div class="form-actions">

    <a
        href="../dashboard.php"
        class="btn btn-secondary"
    >
        Back to Dashboard
    </a>

</div>

</main>

<footer class="site-footer">

<div class="container">

    <p>
        &copy; <?php echo date("Y"); ?> MeetSync Kenya.
        Meeting Scheduling and Coordination System.
    </p>

</div>


</footer>

</body>

</html>

<?php

$stmt->close();

?>
