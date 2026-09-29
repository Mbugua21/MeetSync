<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

$user_id = $_SESSION["user_id"];


$stmt = $conn->prepare(
    "SELECT
        m.title,
        m.meeting_date,
        m.start_time,
        m.end_time,
        m.location,
        m.status,
        u.name AS organizer
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
     ORDER BY m.meeting_date ASC,
              m.start_time ASC"
);

$stmt->bind_param(
    "ii",
    $user_id,
    $user_id
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

<title>MeetSync - Meeting Schedule Report</title>

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
        Meeting Schedule Report
    </h2>

    <p>
        This is a read-only meeting schedule.
    </p>

</section>


<!-- =========================
     ACTION BUTTONS
========================= -->

<div class="form-actions">

    <a href="download_schedule_report.php"
       class="btn btn-success">

        Download Schedule Report

    </a>


    <a href="../dashboard.php"
       class="btn btn-secondary">

        Back to Dashboard

    </a>

</div>


<br>


<!-- =========================
     REPORT TABLE
========================= -->

<?php if ($result->num_rows > 0): ?>


    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Date</th>

                    <th>Start Time</th>

                    <th>End Time</th>

                    <th>Meeting</th>

                    <th>Organizer</th>

                    <th>Location</th>

                </tr>

            </thead>


            <tbody>


                <?php while ($meeting = $result->fetch_assoc()): ?>


                    <tr>


                        <td>

                            <?php

                            echo date(
                                "d M Y",
                                strtotime(
                                    $meeting["meeting_date"]
                                )
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo date(
                                "g:i A",
                                strtotime(
                                    $meeting["start_time"]
                                )
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo date(
                                "g:i A",
                                strtotime(
                                    $meeting["end_time"]
                                )
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $meeting["title"]
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $meeting["organizer"]
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $meeting["location"]
                                ?: "Not specified"
                            );

                            ?>

                        </td>


                    </tr>


                <?php endwhile; ?>


            </tbody>

        </table>

    </div>


<?php else: ?>


    <div class="message message-info">

        No scheduled meetings found.

    </div>


<?php endif; ?>

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
