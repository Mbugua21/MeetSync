<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/availability.php";
require_once "../includes/activity_log.php";

$message = "";
$message_type = "";
$suggestions = [];

$user_id = $_SESSION["user_id"];


/* Get all users except the meeting creator */
$users = [];

$stmt_users = $conn->prepare(
    "SELECT id, name, email
     FROM users
     WHERE id != ?
     ORDER BY name ASC"
);

$stmt_users->bind_param("i", $user_id);
$stmt_users->execute();

$result_users = $stmt_users->get_result();

while ($row = $result_users->fetch_assoc()) {
    $users[] = $row;
}

$stmt_users->close();


/* Create meeting */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $meeting_date = $_POST["meeting_date"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    $location = trim($_POST["location"]);

    $selected_participants = $_POST["participants"] ?? [];


    /* Convert participant IDs to integers */
    $selected_participants = array_map(
        "intval",
        $selected_participants
    );


    /* Remove duplicate participant IDs */
    $selected_participants = array_unique(
        $selected_participants
    );


    /* Validate required fields */

    if (
        empty($title) ||
        empty($meeting_date) ||
        empty($start_time) ||
        empty($end_time)
    ) {

        $message = "Please fill in all required fields.";
        $message_type = "error";


    } elseif (
        strtotime($end_time) <= strtotime($start_time)
    ) {

        $message = "End time must be later than start time.";
        $message_type = "error";


    } else {

        /*
         * People whose availability must be checked.
         *
         * This includes the meeting creator and
         * all selected participants.
         */
        $people_to_check = $selected_participants;

        $people_to_check[] = $user_id;

        $people_to_check = array_unique(
            $people_to_check
        );


        $conflict_found = false;


        /*
         * Check every person for an existing conflict.
         */
        foreach ($people_to_check as $person_id) {

            $person_id = (int) $person_id;


            /* Get person's name */
            $person_stmt = $conn->prepare(
                "SELECT name
                 FROM users
                 WHERE id = ?"
            );

            $person_stmt->bind_param(
                "i",
                $person_id
            );

            $person_stmt->execute();

            $person_result = $person_stmt->get_result();

            $person = $person_result->fetch_assoc();

            $person_stmt->close();


            /*
             * Check meetings created by the person
             * OR meetings where the person is a participant.
             */
            $conflict_stmt = $conn->prepare(
                "SELECT m.title, m.start_time, m.end_time
                 FROM meetings m
                 LEFT JOIN meeting_participants mp
                 ON m.id = mp.meeting_id
                 WHERE m.meeting_date = ?
                 AND m.status = 'scheduled'
                 AND (m.created_by = ? OR mp.user_id = ?)
                 AND m.start_time < ?
                 AND m.end_time > ?
                 LIMIT 1"
            );

            $conflict_stmt->bind_param(
                "siiss",
                $meeting_date,
                $person_id,
                $person_id,
                $end_time,
                $start_time
            );

            $conflict_stmt->execute();

            $conflict_result = $conflict_stmt->get_result();


            if ($conflict_result->num_rows > 0) {

                $existing_meeting =
                    $conflict_result->fetch_assoc();

                $person_name =
                    $person["name"]
                    ?? "Selected participant";


                $message =
                    "Scheduling conflict detected for "
                    . $person_name
                    . ". Existing meeting: "
                    . $existing_meeting["title"]
                    . " ("
                    . date(
                        "g:i A",
                        strtotime($existing_meeting["start_time"])
                    )
                    . " - "
                    . date(
                        "g:i A",
                        strtotime($existing_meeting["end_time"])
                    )
                    . ").";


                $message_type = "error";

                $conflict_found = true;

                $conflict_stmt->close();

                break;
            }


            $conflict_stmt->close();
        }


        /*
         * If a conflict exists, find alternative times.
         */
        if ($conflict_found) {

            $suggestions = getAlternativeSlots(
                $conn,
                $people_to_check,
                $meeting_date,
                60
            );
        }


        /*
         * Only create the meeting when
         * no conflict has been found.
         */
        if (!$conflict_found) {

            $created_by = $_SESSION["user_id"];


            /* Save meeting */
            $stmt = $conn->prepare(
                "INSERT INTO meetings
                (
                    created_by,
                    title,
                    description,
                    meeting_date,
                    start_time,
                    end_time,
                    location
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "issssss",
                $created_by,
                $title,
                $description,
                $meeting_date,
                $start_time,
                $end_time,
                $location
            );


            if ($stmt->execute()) {

                $meeting_id = $stmt->insert_id;

                $stmt->close();


                /*
                 * Add selected participants
                 * and create notifications.
                 */
                if (!empty($selected_participants)) {

                    $participant_stmt = $conn->prepare(
                        "INSERT INTO meeting_participants
                        (meeting_id, user_id, status)
                        VALUES (?, ?, 'invited')"
                    );


                    $notification_stmt = $conn->prepare(
                        "INSERT INTO notifications
                        (user_id, meeting_id, message)
                        VALUES (?, ?, ?)"
                    );


                    foreach ($selected_participants as $participant_id) {

                        if ($participant_id != $created_by) {

                            /*
                             * Add participant to the meeting.
                             */
                            $participant_stmt->bind_param(
                                "ii",
                                $meeting_id,
                                $participant_id
                            );

                            $participant_stmt->execute();


                            /*
                             * Create notification for the participant.
                             */
                            $notification_message =
                                "You have been invited to a meeting: "
                                . $title;

                            $notification_stmt->bind_param(
                                "iis",
                                $participant_id,
                                $meeting_id,
                                $notification_message
                            );

                            $notification_stmt->execute();
                        }
                    }


                    $participant_stmt->close();
                    $notification_stmt->close();
                }


                /*
                 * Record successful meeting creation
                 * in the activity log.
                 */
                logActivity(
                    $conn,
                    $created_by,
                    "Meeting Created",
                    "Created meeting: " . $title
                );


                $message =
                    "Meeting created and participants invited successfully.";

                $message_type = "success";


            } else {

                $message =
                    "Failed to create meeting.";

                $message_type = "error";

                $stmt->close();
            }
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

<title>MeetSync - Create Meeting</title>

<link rel="stylesheet"
      href="../css/style.css">


<script src="../js/app.js"></script>

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

            <a href="../dashboard.php">
                Dashboard
            </a>

            &nbsp;|&nbsp;

            <a href="../logout.php">
                Logout
            </a>

        </div>

    </div>

</div>

</header>

<main class="container">


<div class="form-container">


    <section class="card">

        <h2>Create New Meeting</h2>

        <p>
            Welcome,
            <strong>
                <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
            </strong>.
        </p>


        <?php if (!empty($message)): ?>

            <div class="message
                <?php
                if ($message_type == "success") {
                    echo "message-success";
                } elseif ($message_type == "error") {
                    echo "message-error";
                } else {
                    echo "message-info";
                }
                ?>
            ">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($suggestions)): ?>

            <div class="card">

                <h3>Suggested Available Times</h3>

                <p>
                    The requested time is not available.
                    The following one-hour slots are available
                    for all selected participants:
                </p>


                <ul>

                    <?php foreach ($suggestions as $suggestion): ?>

                        <li>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $suggestion["start"]
                                );
                                ?>

                                -

                                <?php
                                echo htmlspecialchars(
                                    $suggestion["end"]
                                );
                                ?>
                            </strong>

                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php elseif ($message_type == "error"): ?>

            <div class="message message-info">

                No alternative one-hour slots were found
                between 8:00 AM and 5:00 PM.

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-group">

                <label for="title">
                    Meeting Title
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    required
                >

            </div>


            <div class="form-group">

                <label for="description">
                    Agenda / Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                ></textarea>

            </div>


            <div class="form-group">

                <label for="meeting_date">
                    Meeting Date
                </label>

                <input
                    type="date"
                    id="meeting_date"
                    name="meeting_date"
                    required
                >

            </div>


            <div class="form-group">

                <label for="start_time">
                    Start Time
                </label>

                <input
                    type="time"
                    id="start_time"
                    name="start_time"
                    required
                >

            </div>


            <div class="form-group">

                <label for="end_time">
                    End Time
                </label>

                <input
                    type="time"
                    id="end_time"
                    name="end_time"
                    required
                >

            </div>


            <div class="form-group">

                <label for="location">
                    Venue / Location
                </label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    placeholder="Enter meeting venue"
                >

            </div>


            <div class="form-group">

                <label>
                    Select Participants
                </label>


                <?php if (empty($users)): ?>

                    <div class="message message-info">

                        No other employees are registered yet.

                    </div>

                <?php else: ?>

                    <div class="card">

                        <?php foreach ($users as $user): ?>

                            <div>

                                <label>

                                    <input
                                        type="checkbox"
                                        name="participants[]"
                                        value="<?php echo $user["id"]; ?>"
                                    >

                                    <?php
                                    echo htmlspecialchars(
                                        $user["name"]
                                    );
                                    ?>

                                    -

                                    <?php
                                    echo htmlspecialchars(
                                        $user["email"]
                                    );
                                    ?>

                                </label>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>


            <button type="submit"
                    class="btn">

                Create Meeting

            </button>


            <a href="../dashboard.php"
               class="btn btn-secondary">

                Back to Dashboard

            </a>


        </form>

    </section>


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
