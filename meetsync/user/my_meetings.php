<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";


/*
 * Get the ID of the logged-in user.
 */
$user_id = $_SESSION["user_id"];


/*
 * Get meetings created by the logged-in user.
 */
$stmt = $conn->prepare(
    "SELECT id, title, description, meeting_date,
            start_time, end_time, location, status
     FROM meetings
     WHERE created_by = ?
     ORDER BY meeting_date ASC, start_time ASC"
);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>


<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>MeetSync - My Meetings</title>

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


<section class="card">

    <div class="section-header">

        <div>

            <h2>My Meetings</h2>

            <p>
                Welcome,
                <strong>
                    <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                </strong>.
            </p>

        </div>

        <a href="create_meeting.php"
           class="btn">

            Create New Meeting

        </a>

    </div>


    <?php if ($result->num_rows == 0): ?>

        <div class="message message-info">

            You have not created any meetings yet.

        </div>

    <?php else: ?>


        <div class="meeting-list">

            <?php while ($meeting = $result->fetch_assoc()): ?>

                <div class="card">


                    <div class="section-header">

                        <div>

                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $meeting["title"]
                                );
                                ?>
                            </h3>

                        </div>


                        <span class="status
                            status-<?php
                            echo htmlspecialchars(
                                $meeting["status"]
                            );
                            ?>">

                            <?php
                            echo htmlspecialchars(
                                ucfirst(
                                    $meeting["status"]
                                )
                            );
                            ?>

                        </span>

                    </div>


                    <div class="dashboard-grid">


                        <div>

                            <p>

                                <strong>Date:</strong><br>

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

                                <strong>Time:</strong><br>

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

                        </div>


                        <div>

                            <p>

                                <strong>Location:</strong><br>

                                <?php
                                echo htmlspecialchars(
                                    $meeting["location"]
                                    ?: "Not specified"
                                );
                                ?>

                            </p>


                            <p>

                                <strong>Description:</strong><br>

                                <?php
                                echo htmlspecialchars(
                                    $meeting["description"]
                                    ?: "No description"
                                );
                                ?>

                            </p>

                        </div>


                    </div>


                    <?php if ($meeting["status"] != "cancelled"): ?>

                        <div>

                            <a
                                href="edit_meeting.php?id=<?php echo $meeting["id"]; ?>"
                                class="btn btn-secondary"
                            >

                                Edit Meeting

                            </a>


                            <a
                                href="cancel_meeting.php?id=<?php echo $meeting["id"]; ?>"
                                class="btn btn-danger"
                                onclick="return confirm('Are you sure you want to cancel this meeting?');"
                            >

                                Cancel Meeting

                            </a>

                        </div>

                    <?php endif; ?>


                </div>

            <?php endwhile; ?>

        </div>


    <?php endif; ?>

</section>


<section class="card">

    <h3>Quick Navigation</h3>

    <div class="menu-grid">

        <a class="menu-item"
           href="create_meeting.php">

            <h3>Create Meeting</h3>

            <p>
                Schedule a new meeting.
            </p>

        </a>


        <a class="menu-item"
           href="../dashboard.php">

            <h3>Dashboard</h3>

            <p>
                Return to your main dashboard.
            </p>

        </a>

    </div>

</section>

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

$stmt->close();

?>
