<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

$user_id = $_SESSION["user_id"];


/*
 * Get the logged-in user's department.
 */
$user_stmt = $conn->prepare(
    "SELECT department
     FROM users
     WHERE id = ?"
);

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();

$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

$user_stmt->close();


$department = $user["department"] ?? "";


/*
 * Get scheduled meetings involving users
 * from the same department.
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
     LEFT JOIN users participant
        ON mp.user_id = participant.id
     WHERE m.status = 'scheduled'
     AND (
        u.department = ?
        OR participant.department = ?
     )
     ORDER BY m.meeting_date ASC,
              m.start_time ASC"
);

$stmt->bind_param(
    "ss",
    $department,
    $department
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

<title>MeetSync - Department Schedule</title>

<link rel="stylesheet"
      href="../css/style.css">

<script src="../js/app.js"></script>

</head>

<body>

<!-- =========================
     HEADER
========================= -->

<header class="site-header">

<div class="container">

    <h1>MeetSync</h1>

</div>

</header>

<!-- =========================
     NAVIGATION
========================= -->

<nav class="navbar">

<div class="container">

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

    <a href="reminders.php">
        Reminders
    </a>

    <a href="../logout.php">
        Logout
    </a>

</div>

</nav>

<!-- =========================
     MAIN CONTENT
========================= -->

<main class="container">

<section class="section-header">

    <h2>
        Department Meeting Schedule
    </h2>

    <p>
        View scheduled meetings for your department.
    </p>

</section>


<!-- Department Information -->

<div class="card">

    <p>

        <strong>Department:</strong>

        <?php

        echo htmlspecialchars(
            $department ?: "Not specified"
        );

        ?>

    </p>

</div>


<!-- =========================
     MEETING LIST
========================= -->

<?php if ($result->num_rows == 0): ?>

    <div class="message message-info">

        There are no scheduled meetings
        for this department.

    </div>


<?php else: ?>


    <div class="meeting-list">


        <?php while ($meeting = $result->fetch_assoc()): ?>


            <div class="card">


                <h3>

                    <?php

                    echo htmlspecialchars(
                        $meeting["title"]
                    );

                    ?>

                </h3>


                <p>

                    <strong>Date:</strong>

                    <?php

                    echo date(
                        "d M Y",
                        strtotime(
                            $meeting["meeting_date"]
                        )
                    );

                    ?>

                </p>


                <p>

                    <strong>Time:</strong>

                    <?php

                    echo date(
                        "g:i A",
                        strtotime(
                            $meeting["start_time"]
                        )
                    );

                    ?>

                    -

                    <?php

                    echo date(
                        "g:i A",
                        strtotime(
                            $meeting["end_time"]
                        )
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


<!-- =========================
     ACTION BUTTONS
========================= -->

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

<!-- =========================
     FOOTER
========================= -->

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
