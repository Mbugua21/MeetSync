<?php

require_once "includes/auth.php";
require_once "includes/db.php";

$user_id = $_SESSION["user_id"];


/*
 * Count unread notifications.
 */
$notification_stmt = $conn->prepare(
    "SELECT COUNT(*) AS unread_count
     FROM notifications
     WHERE user_id = ?
     AND is_read = 0"
);

$notification_stmt->bind_param(
    "i",
    $user_id
);

$notification_stmt->execute();

$notification_result =
    $notification_stmt->get_result();

$notification_data =
    $notification_result->fetch_assoc();

$unread_count =
    (int) $notification_data["unread_count"];

$notification_stmt->close();


/*
 * Get upcoming meetings.
 *
 * The user can be:
 * - the meeting creator
 * - an invited participant
 *
 * Cancelled meetings are excluded.
 */
$meeting_stmt = $conn->prepare(
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
        m.meeting_date > CURDATE()
        OR (
            m.meeting_date = CURDATE()
            AND m.start_time >= CURTIME()
        )
     )
     ORDER BY m.meeting_date ASC,
              m.start_time ASC
     LIMIT 5"
);

$meeting_stmt->bind_param(
    "ii",
    $user_id,
    $user_id
);

$meeting_stmt->execute();

$meeting_result =
    $meeting_stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>MeetSync Dashboard</title>

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

            <a href="profile.php">
                My Profile
            </a>

            &nbsp;|&nbsp;

            <a href="logout.php">
                Logout
            </a>

        </div>

    </div>

</div>

</header>

<main class="container">

<!-- Welcome Section -->

<section class="card">

    <h2>Dashboard</h2>

    <p>

        Welcome,
        <strong>
            <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
        </strong>!

    </p>

    <p>

        <strong>Email:</strong>
        <?php echo htmlspecialchars($_SESSION["user_email"]); ?>

        &nbsp;&nbsp;

        <strong>Role:</strong>
        <?php echo htmlspecialchars($_SESSION["user_role"]); ?>

    </p>

</section>


<!-- Dashboard Summary -->

<section class="dashboard-grid">

    <div class="stat-card">

        <h3>Notifications</h3>

        <p class="stat-number">
            <?php echo $unread_count; ?>
        </p>

        <p>
            Unread notification(s)
        </p>

        <a class="btn btn-secondary"
           href="user/notifications.php">

            View Notifications

        </a>

    </div>


    <div class="stat-card">

        <h3>Meetings</h3>

        <p class="stat-number">
            <?php echo $meeting_result->num_rows; ?>
        </p>

        <p>
            Upcoming meetings
        </p>

        <a class="btn btn-secondary"
           href="user/calendar.php">

            Open Calendar

        </a>

    </div>

</section>


<!-- Upcoming Meetings -->

<section class="card">

    <div class="section-header">

        <div>

            <h2>Upcoming Meetings</h2>

            <p>
                Your next scheduled meetings.
            </p>

        </div>

        <a class="btn"
           href="user/calendar.php">

            Full Calendar

        </a>

    </div>


    <?php if ($meeting_result->num_rows == 0): ?>

        <div class="message message-info">

            You have no upcoming meetings.

        </div>

    <?php else: ?>

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>Meeting</th>

                        <th>Date</th>

                        <th>Time</th>

                        <th>Organizer</th>

                        <th>Location</th>

                    </tr>

                </thead>


                <tbody>

                    <?php while ($meeting = $meeting_result->fetch_assoc()): ?>

                        <tr>

                            <td>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $meeting["title"]
                                    );
                                    ?>
                                </strong>

                            </td>


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

                                -

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
                                    $meeting["organizer_name"]
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

    <?php endif; ?>

</section>


<!-- Meeting Management -->

<section class="card">

    <h2>Meeting Management</h2>

    <div class="menu-grid">

        <a class="menu-item"
           href="user/create_meeting.php">

            <h3>Create Meeting</h3>

            <p>
                Schedule a new meeting and check participant availability.
            </p>

        </a>


        <a class="menu-item"
           href="user/my_meetings.php">

            <h3>My Meetings</h3>

            <p>
                View, update and manage meetings you have created.
            </p>

        </a>


        <a class="menu-item"
           href="user/my_invitations.php">

            <h3>My Invitations</h3>

            <p>
                View and respond to meeting invitations.
            </p>

        </a>


        <a class="menu-item"
           href="user/reminders.php">

            <h3>Meeting Reminders</h3>

            <p>
                View your upcoming meeting reminders.
            </p>

        </a>


        <a class="menu-item"
           href="user/department_schedule.php">

            <h3>Department Schedule</h3>

            <p>
                View scheduled meetings within your department.
            </p>

        </a>


        <a class="menu-item"
           href="user/schedule_report.php">

            <h3>Schedule Reports</h3>

            <p>
                View and download meeting schedule reports.
            </p>

        </a>

    </div>

</section>


<!-- Account Management -->

<section class="card">

    <h2>Account Management</h2>

    <div class="menu-grid">

        <a class="menu-item"
           href="profile.php">

            <h3>My Profile</h3>

            <p>
                View and update your account information.
            </p>

        </a>


        <a class="menu-item"
           href="change_password.php">

            <h3>Change Password</h3>

            <p>
                Update your account password.
            </p>

        </a>

    </div>

</section>


<!-- Administration -->

<?php if ($_SESSION["user_role"] === "admin"): ?>

    <section class="card">

        <h2>Administration</h2>

        <p>
            Administrator tools for monitoring and managing the system.
        </p>

        <a class="btn btn-success"
           href="admin/index.php">

            Open Administration

        </a>

    </section>

<?php endif; ?>

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

<?php

$meeting_stmt->close();

?>
