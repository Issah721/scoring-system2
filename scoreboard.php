<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoreboard</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Additional styles for message div for better visibility */
        #message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        #message.error { /* Only style if it's an error message */
            color: #ffffff; /* White text */
            background-color: #ff4d4d; /* A clear red */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Scoreboard</h1>
        <table id="scoreboardTable">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Participant</th>
                    <th>Total Points</th>
                </tr>
            </thead>
            <tbody id="scoreboardBody">
                <!-- Scores will be loaded here by JavaScript -->
            </tbody>
        </table>
        <div id="message"></div> <!-- Message div, styled by JS/CSS -->
    </div>

    <script>
        const scoreboardBody = document.getElementById('scoreboardBody');
        const messageDiv = document.getElementById('message');

        async function fetchScoreboard() {
            try {
                const response = await fetch('api.php?action=get_scoreboard');
                // Try to parse JSON regardless of response.ok, as API might send error details in JSON
                const scoresData = await response.json().catch(() => null);

                if (response.ok) {
                    messageDiv.textContent = ''; // Clear previous errors
                    messageDiv.className = ''; // Clear error class
                    scoreboardBody.innerHTML = ''; // Clear existing rows

                    if (!scoresData || scoresData.length === 0) {
                        const row = scoreboardBody.insertRow();
                        row.classList.add('fade-in'); // Apply fade-in to this message row too
                        const cell = row.insertCell(0);
                        cell.colSpan = 3;
                        cell.textContent = 'No scores submitted yet.';
                        cell.style.textAlign = 'center';
                        return;
                    }

                    scoresData.forEach((score, index) => {
                        const row = scoreboardBody.insertRow();
                        row.classList.add('fade-in'); // Add fade-in animation class

                        if (index === 0) { // Rank 1
                            row.classList.add('top-scorer'); // Highlight top scorer
                        }

                        const rankCell = row.insertCell();
                        rankCell.textContent = index + 1; // 1-based rank

                        const nameCell = row.insertCell();
                        nameCell.textContent = score.display_name;

                        const pointsCell = row.insertCell();
                        pointsCell.textContent = score.total_points;
                    });
                } else {
                    // Use error message from JSON if available, else default
                    messageDiv.textContent = 'Error loading scoreboard: ' + (scoresData && scoresData.error ? scoresData.error : `HTTP ${response.status}`);
                    messageDiv.className = 'error';
                }
            } catch (error) {
                console.error('Failed to fetch scoreboard:', error);
                messageDiv.textContent = 'Failed to fetch scoreboard. Check console for details or network issues.';
                messageDiv.className = 'error';
            }
        }

        // Initial load
        document.addEventListener('DOMContentLoaded', fetchScoreboard);

        // Auto-refresh every 10 seconds
        setInterval(fetchScoreboard, 10000);
    </script>
</body>
</html>
