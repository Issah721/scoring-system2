<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // For navigation.php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoreboard - Scoring System</title> <!-- Updated Title -->
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'navigation.php'; ?>
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
        <div id="message" class="message" style="margin-top: 15px;"></div> <!-- Ensure class 'message' for styling -->
    </div>

    <script>
        const scoreboardBody = document.getElementById('scoreboardBody');
        const messageDiv = document.getElementById('message');

        // Corrected htmlspecialchars function (consistent with judge.php)
        function htmlspecialchars(str) {
            if (typeof str !== 'string') return '';
            const SCRIPT_REGEX = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi;
            str = str.replace(SCRIPT_REGEX, ""); 

            const replacements = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return str.replace(/[&<>"']/g, match => replacements[match]);
        }

        async function fetchScoreboard() {
            try {
                const response = await fetch('api.php?action=get_scoreboard');
                const scoresData = await response.json().catch(() => null); // Try to parse JSON always

                if (response.ok) {
                    // Check for a structured error within a 200 OK response
                    if (scoresData && scoresData.error) {
                        messageDiv.textContent = 'Error loading scoreboard: ' + htmlspecialchars(scoresData.error);
                        messageDiv.className = 'message error-message';
                        scoreboardBody.innerHTML = '<tr><td colspan="3">Error loading data.</td></tr>';
                        return;
                    }
                    
                    // Assuming scoresData is the array of scores if no error object
                    messageDiv.textContent = ''; 
                    messageDiv.className = 'message';
                    scoreboardBody.innerHTML = ''; 

                    if (!scoresData || scoresData.length === 0) {
                        const row = scoreboardBody.insertRow();
                        row.classList.add('fade-in');
                        const cell = row.insertCell(0);
                        cell.colSpan = 3;
                        cell.textContent = 'No scores submitted yet.';
                        cell.style.textAlign = 'center';
                        return;
                    }

                    scoresData.forEach((score, index) => {
                        const row = scoreboardBody.insertRow();
                        row.classList.add('fade-in'); 

                        if (index === 0) { 
                            row.classList.add('top-scorer'); 
                        }

                        const rankCell = row.insertCell();
                        rankCell.textContent = index + 1;

                        const nameCell = row.insertCell();
                        nameCell.textContent = htmlspecialchars(score.display_name); 

                        const pointsCell = row.insertCell();
                        pointsCell.textContent = score.total_points;
                    });
                } else {
                    // Handle non-200 responses
                    const errorMsg = scoresData && scoresData.error ? scoresData.error : `HTTP ${response.status} - ${response.statusText}`;
                    messageDiv.textContent = 'Error loading scoreboard: ' + htmlspecialchars(errorMsg);
                    messageDiv.className = 'message error-message';
                    scoreboardBody.innerHTML = '<tr><td colspan="3">Error loading data.</td></tr>';
                }
            } catch (error) {
                console.error('Failed to fetch scoreboard:', error);
                messageDiv.textContent = 'Failed to fetch scoreboard. Check console for details or network issues.';
                messageDiv.className = 'message error-message';
                scoreboardBody.innerHTML = '<tr><td colspan="3">Error loading data.</td></tr>';
            }
        }
        
        document.addEventListener('DOMContentLoaded', fetchScoreboard);
        setInterval(fetchScoreboard, 10000); // Auto-refresh every 10 seconds
    </script>
</body>
</html>
