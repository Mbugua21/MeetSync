<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

$user_id = $_SESSION["user_id"];

/*
 * Get meetings where the logged-in user
 * has been invited.
 */
$stmt = $conn->prepare(
    "SELECT
        mp.id AS participant_id,
        mp.status AS invitation_status,
        m.id AS meeting_id,
        m.title,
        m.description,
        m.meeting_date,
        m.start_time,
        m.end_time,
        m.location,
        u.name AS organizer_name
     FROM meeting_participants mp
     INNER JOIN meetings m
        ON mp.meeting_id = m.id
     INNER JOIN users u
        ON m.created_by = u.id
     WHERE mp.user_id = ?
     ORDER BY m.meeting_date ASC, m.start_time ASC"
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

<title>MeetSync - My Invitations</title>

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

        <a href="../logout.php">
            Logout
        </a>

    </nav>

</div>

</header>

<main class="container">


<div class="section-header">

    <h2>My Meeting Invitations</h2>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION["user_name"]); ?>.
    </p>

</div>


<?php if ($result->num_rows == 0): ?>

    <div class="message message-info">

        You do not have any meeting invitations.

    </div>

<?php else: ?>

    <div class="meeting-list">

        <?php while ($meeting = $result->fetch_assoc()): ?>

            <div class="card">

                <h3>
                    <?php
                    echo htmlspecialchars($meeting["title"]);
                    ?>
                </h3>


                <p>
                    <strong>Organizer:</strong>
                    <?php
                    echo htmlspecialchars(
                        $meeting["organizer_name"]
                    );
                    ?>
                </p>


                <p>
                    <strong>Date:</strong>
                    <?php
                    echo htmlspecialchars(
                        $meeting["meeting_date"]
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
                    <strong>Location:</strong>

                    <?php
                    echo htmlspecialchars(
                        $meeting["location"]
                        ?: "Not specified"
                    );
                    ?>

                </p>


                <p>
                    <strong>Description:</strong>

                    <?php
                    echo htmlspecialchars(
                        $meeting["description"]
                        ?: "No description"
                    );
                    ?>

                </p>


                <p>

                    <strong>Invitation Status:</strong>

                    <span class="status status-<?php
                        echo strtolower(
                            $meeting["invitation_status"]
                        );
                    ?>">

                        <?php
                        echo htmlspecialchars(
                            $meeting["invitation_status"]
                        );
                        ?>

                    </span>

                </p>


                <?php if ($meeting["invitation_status"] === "invited"): ?>

                    <div class="form-actions">

                        <a
                            href="invitation_response.php?id=<?php echo $meeting["participant_id"]; ?>&response=accepted"
                            class="btn btn-success"
                            onclick="return confirm('Accept this meeting invitation?');"
                        >
                            Accept
                        </a>

                        <a
                            href="invitation_response.php?id=<?php echo $meeting["participant_id"]; ?>&response=declined"
                            class="btn btn-danger"
                            onclick="return confirm('Decline this meeting invitation?');"
                        >
                            Decline
                        </a>

                    </div>

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
