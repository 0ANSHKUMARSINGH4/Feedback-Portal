<?php 
session_start(); 
include 'config.php'; 

$pageTitle = "Feedback Form";

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $error = "You must be logged in to submit feedback.";
    } else {
        $user_id = $_SESSION['user_id'];

        $check_query = "SELECT id FROM feedback WHERE user_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "You have already submitted feedback.";
            $stmt->close();
        } else {
            $stmt->close();

            $department = trim($_POST['department'] ?? '');
            $feedback_message = trim($_POST['written_feedback'] ?? '');
            $q1 = intval($_POST['q1'] ?? 0);
            $q2 = intval($_POST['q2'] ?? 0);
            $q3 = intval($_POST['q3'] ?? 0);
            $q4 = intval($_POST['q4'] ?? 0);
            $q5 = intval($_POST['q5'] ?? 0);

            if (empty($department) || empty($feedback_message) || !$q1 || !$q2 || !$q3 || !$q4 || !$q5) {
                $error = "Please fill in all feedback fields.";
            } else {
                $insert_query = "INSERT INTO feedback (user_id, department, feedback_message, q1, q2, q3, q4, q5, submitted_at, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("sssiiiii", $user_id, $department, $feedback_message, $q1, $q2, $q3, $q4, $q5);

                if ($stmt->execute()) {
                    $stmt->close();
                    $conn->close();
                    header("Location: user_dashboard.php");
                    exit();
                } else {
                    $error = "Database error: " . $stmt->error;
                    $stmt->close();
                }
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Feedback Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #90D5FF, #77B1D4);
            margin: 0;
        }

        .feedback-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .feedback-box {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px 40px;
            margin: 40px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .field-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            height: 100px;
        }

        .stars {
            display: flex;
            gap: 10px;
            margin-top: 6px;
        }

        .stars input {
            margin-top: 2px;
        }

        button {
            padding: 12px 30px;
            background-color: #57B9FF;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: block;
            margin: 30px auto 0;
            font-size: 16px;
        }

        button:hover {
            background-color: #517891;
        }

        .message {
            text-align: center;
            font-weight: bold;
            margin-top: 20px;
        }

        .message.success {
            color: green;
        }

        .message.error {
            color: red;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="feedback-wrapper">
    <div class="feedback-box">
        <h2>Feedback Form</h2>

        <?php if (!empty($error)): ?>
            <p class="message error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="field-group">
                <label for="department">Department:</label>
                <input type="text" name="department" id="department" required>
            </div>

            <div class="field-group">
                <label for="written_feedback">Written Feedback:</label>
                <textarea name="written_feedback" id="written_feedback" required></textarea>
            </div>

            <?php
            $questions = [
                "How would you rate the overall quality of this training session?",
                "Was the training program interactive and engaging?",
                "Do you feel you were given enough time and resources to complete the training?",
                "Overall, how would you rate the training?",
                "Was the training program well organized and easy to follow?"
            ];

            foreach ($questions as $index => $question) {
                $qnum = $index + 1;
                echo "
                <div class='field-group'>
                    <label>{$qnum}. {$question}</label>
                    <div class='stars'>";
                for ($i = 1; $i <= 5; $i++) {
                    $required = ($i === 1) ? "required" : "";
                    echo "<input type='radio' name='q{$qnum}' value='{$i}' {$required}> {$i} ";
                }
                echo "</div></div>";
            }
            ?>

            <button type="submit">Submit Feedback</button>
        </form>
    </div>
</div>
</body>
</html>
