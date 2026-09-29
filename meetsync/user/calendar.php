<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

$user_id = $_SESSION["user_id"];

/*
 * Determine calendar view.
 */
$view = $_GET["view"] ?? "daily";

/*
 * Get selected date.
 */
$selected_date = $_GET["date"] ?? date("Y-m-d");

/*
 * Make sure only supported views are used.
 */
if (!in_array($view, ["daily", "weekly", "monthly"])) {
    $view = "daily";
}

/*
 * Determine date range.
 */
if ($view === "weekly") {

    $timestamp = strtotime($selected_date);

    /*
     * Monday is the first day of the week.
     */
    $day_of_week = date("N", $timestamp);

    $start_date = date(
        "Y-m-d",
        strtotime("-" . ($day_of_week - 1) . " days", $timestamp)
    );

    $end_date = date(
        "Y-m-d",
        strtotime("+6 days", strtotime($start_date))
    );

} elseif ($view === "monthly") {

    /*
     * Get the first and last day of the selected month.
     */
    $start_date = date(
        "Y-m-01",
        strtotime($selected_date)
    );

    $end_date = date(
        "Y-m-t",
        strtotime($selected_date)
    );

} else {

    /*
     * Daily view uses only the selected date.
     */
    $start_date = $selected_date;
    $end_date = $selected_date;
}

/*
 * Get meetings within the selected date range.
 */
$stmt = $conn->prepare(
    "SELECT DISTINCT
        m.id,
        m.title,
        m.description,
        m.meeting_date,
        m.start_time,
        m.end_time,
        m.location,
        m.status,
        u.name AS organizer_name
     FROM meetings m
     INNER JOIN users u
        ON m.created_by = u.id
     LEFT JOIN meeting_participants mp
        ON m.id = mp.meeting_id
     WHERE m.status = 'scheduled'
     AND m.meeting_date BETWEEN ? AND ?
     AND (
        m.created_by = ?
        OR mp.user_id = ?
     )
     ORDER BY m.meeting_date ASC, m.start_time ASC"
);

$stmt->bind_param(
    "ssii",
    $start_date,
    $end_date,
    $user_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

/*
 * Store meetings by date.
 */
$meetings_by_date = [];

while ($meeting = $result->fetch_assoc()) {

    $meeting_date = $meeting["meeting_date"];

    $meetings_by_date[$meeting_date][] = $meeting;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>MeetSync - Calendar</title>

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

<div class="section-header">

    <h2>Meeting Calendar</h2>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION["user_name"]); ?>.
    </p>

</div>


<div class="card">

    <h3>Calendar View</h3>

    <div class="form-actions">

        <a
            href="calendar.php?view=daily&date=<?php echo htmlspecialchars($selected_date); ?>"
            class="btn <?php echo $view === "daily" ? "btn-success" : "btn-secondary"; ?>"
        >
            Daily View
        </a>

        <a
            href="calendar.php?view=weekly&date=<?php echo htmlspecialchars($selected_date); ?>"
            class="btn <?php echo $view === "weekly" ? "btn-success" : "btn-secondary"; ?>"
        >
            Weekly View
        </a>

        <a
            href="calendar.php?view=monthly&date=<?php echo htmlspecialchars($selected_date); ?>"
            class="btn <?php echo $view === "monthly" ? "btn-success" : "btn-secondary"; ?>"
        >
            Monthly View
        </a>

    </div>

</div>


<div class="card">

    <form method="GET">

        <input
            type="hidden"
            name="view"
            value="<?php echo htmlspecialchars($view); ?>"
        >

        <div class="form-group">

            <label for="date">
                Select Date
            </label>

            <input
                type="date"
                id="date"
                name="date"
                value="<?php echo htmlspecialchars($selected_date); ?>"
                required
            >

        </div>

        <button
            type="submit"
            class="btn btn-success"
        >
            View Calendar
        </button>

    </form>

</div>


<?php if ($view === "monthly"): ?>

    <div class="section-header">

        <h3>
            Monthly View -
            <?php
            echo date(
                "F Y",
                strtotime($selected_date)
            );
            ?>
        </h3>

    </div>


    <?php if (empty($meetings_by_date)): ?>

        <div class="message message-info">

            You have no scheduled meetings
            in this month.

        </div>

    <?php else: ?>

        <div class="meeting-list">

            <?php foreach ($meetings_by_date as $meeting_date => $day_meetings): ?>

                <div class="card">

                    <h3>
                        <?php
                        echo date(
                            "l, d M Y",
                            strtotime($meeting_date)
                        );
                        ?>
                    </h3>


                    <?php foreach ($day_meetings as $meeting): ?>

                        <div class="card">

                            <h4>
                                <?php
                                echo htmlspecialchars(
                                    $meeting["title"]
                                );
                                ?>
                            </h4>


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

                    <?php endforeach; ?>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


<?php else: ?>


    <?php if (empty($meetings_by_date)): ?>

        <div class="message message-info">

            You have no scheduled meetings
            for the selected period.

        </div>

    <?php else: ?>

        <div class="meeting-list">

            <?php foreach ($meetings_by_date as $meeting_date => $day_meetings): ?>

                <?php if ($view === "weekly"): ?>

                    <div class="section-header">

                        <h3>
                            <?php
                            echo date(
                                "l, d M Y",
                                strtotime($meeting_date)
                            );
                            ?>
                        </h3>

                    </div>

                <?php endif; ?>


                <?php foreach ($day_meetings as $meeting): ?>

                    <div class="card">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $meeting["title"]
                            );
                            ?>
                        </h3>


                        <?php if ($view === "daily"): ?>

                            <p>

                                <strong>Date:</strong>

                                <?php
                                echo date(
                                    "l, d M Y",
                                    strtotime($meeting_date)
                                );
                                ?>

                            </p>

                        <?php endif; ?>


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

                <?php endforeach; ?>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


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
