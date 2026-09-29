<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

$user_id = $_SESSION["user_id"];


/*
 * Get today's date and current time.
 */
$today = date("Y-m-d");
$current_time = date("H:i:s");


/*
 * Get upcoming meetings for the logged-in user.
 *
 * The user can be:
 * - the meeting creator
 * - an invited participant
 *
 * Cancelled meetings are excluded.
 */
$stmt = $conn->prepare(
    "SELECT DISTINCT
        m.id,
        m.title,
        m.meeting_date,
        m.start_time,
        m.end_time,
        m.location,
        u.name AS organizer_name
     FROM meetings m
     INNER JOIN users u
        ON m.created_by = u.id
     LEFT JOIN meeting_participants mp
        ON m.id = mp.meeting_id
     WHERE m.status = 'scheduled'
     AND (
        m.created_by = ?
        OR mp.user_id = ?
     )
     AND (
        m.meeting_date > ?
        OR (
            m.meeting_date = ?
            AND m.start_time >= ?
        )
     )
     ORDER BY m.meeting_date ASC,
              m.start_time ASC"
);

$stmt->bind_param(
    "iisss",
    $user_id,
    $user_id,
    $today,
    $today,
    $current_time
);

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>MeetSync - Meeting Reminders</title>

<link rel="stylesheet"
      href="../css/style.css">

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

        <a href="calendar.php">
            Calendar
        </a>

        <a href="my_invitations.php">
            Invitations
        </a>

        <a href="notifications.php">
            Notifications
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </nav>

</div>

</header>

<main class="container">


<section class="section-header">

    <h2>Meeting Reminders</h2>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION["user_name"]); ?>.
    </p>

</section>


<div class="card">


    <h3>Upcoming Meetings</h3>


    <?php if ($result->num_rows == 0): ?>

        <div class="message message-info">

            You have no upcoming meetings.

        </div>

    <?php else: ?>


        <div class="meeting-list">


            <?php while ($meeting = $result->fetch_assoc()): ?>

                <div class="card">


                    <h4>
                        <?php
                        echo htmlspecialchars(
                            $meeting["title"]
                        );
                        ?>
                    </h4>


                    <p>

                        <strong>Date:</strong>

                        <?php
                        echo date(
                            "d M Y",
                            strtotime($meeting["meeting_date"])
                        );
                        ?>

                    </p>


                    <p>

                        <strong>Time:</strong>

                        <?php
                        echo date(
                            "g:i A",
                            strtotime($meeting["start_time"])
                        );
                        ?>

                        -

                        <?php
                        echo date(
                            "g:i A",
                            strtotime($meeting["end_time"])
                        );
                        ?>

                    </p>


                    <p>

                        <strong>Organizer:</strong>

                        <?php
                        echo htmlspecialchars(
                            $meeting["organizer_name"]
                        );
                        ?>

                    </p>


                    <p>

                        <strong>Location:</strong>

                        <?php
                        echo htmlspecialchars(
                            $meeting["location"]
                            ?: "Not specified"
                        );
                        ?>

                    </p>


                </div>

            <?php endwhile; ?>


        </div>


    <?php endif; ?>


</div>


<div class="form-actions">

    <a href="calendar.php"
       class="btn btn-success">

        Open Calendar

    </a>


    <a href="../dashboard.php"
       class="btn btn-secondary">

        Back to Dashboard

    </a>

</div>

</main>

<footer class="site-footer">


<div class="container">

    <p>
        &copy; <?php echo date("Y"); ?> MeetSync.
        Meeting Scheduling and Coordination System.
    </p>

</div>

</footer>

</body>

</html>

<?php

$stmt->close();

?>
