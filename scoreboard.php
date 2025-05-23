<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoreboard</title> <!-- Original V1 Title -->
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
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
        <div id="message" style="margin-top: 15px; font-weight: bold; text-align: center;"></div> <!-- Basic message div -->
    </div>

    <script>
        const scoreboardBody = document.getElementById('scoreboardBody');
        const messageDiv = document.getElementById('message');

        async function fetchScoreboard() {
            try {
                const response = await fetch('api.php?action=get_scoreboard'); // V1 endpoint
                
                // Always try to parse JSON, even for non-ok responses, as API might send error details
                const scores = await response.json().catch(() => null);

                if (response.ok) {
                    // Handle cases where API might send an error object within a 200 OK response (less common for V1 but good practice)
                    if (scores && scores.error) { // Check if 'scores' itself is an error object
                        messageDiv.textContent = 'Error loading scoreboard: ' + scores.error;
                        messageDiv.style.color = 'red'; // V1 style
                        scoreboardBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">Error loading data.</td></tr>';
                        return;
                    }

                    messageDiv.textContent = ''; // Clear previous errors
                    messageDiv.style.color = ''; // Reset color
                    scoreboardBody.innerHTML = ''; // Clear existing rows

                    if (!scores || scores.length === 0) { // Check if 'scores' is null or empty array
                        const row = scoreboardBody.insertRow();
                        row.classList.add('fade-in'); // fade-in was a V1 feature
                        const cell = row.insertCell(0);
                        cell.colSpan = 3;
                        cell.textContent = 'No scores yet.';
                        cell.style.textAlign = 'center';
                        return;
                    }

                    scores.forEach((score, index) => {
                        const row = scoreboardBody.insertRow();
                        row.classList.add('fade-in'); 

                        if (index === 0) {
                            row.classList.add('top-scorer'); 
                        }

                        const rankCell = row.insertCell();
                        rankCell.textContent = index + 1;

                        const nameCell = row.insertCell();
                        // V1 'users' table (participants) has 'display_name'
                        nameCell.textContent = score.display_name; 

                        const pointsCell = row.insertCell();
                        pointsCell.textContent = score.total_points;
                    });
                } else {
                    // Handle non-200 responses
                    const errorMsg = (scores && scores.error) ? scores.error : `HTTP ${response.status} Error`;
                    messageDiv.textContent = 'Error loading scoreboard: ' + errorMsg;
                    messageDiv.style.color = 'red'; // V1 style
                    scoreboardBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">Error loading data.</td></tr>';
                }
            } catch (error) {
                console.error('Failed to fetch scoreboard:', error);
                messageDiv.textContent = 'Failed to fetch scoreboard. Check console for details.';
                messageDiv.style.color = 'red'; // V1 style
                scoreboardBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">Error loading data.</td></tr>';
            }
        }

        document.addEventListener('DOMContentLoaded', fetchScoreboard);
        setInterval(fetchScoreboard, 10000);
    </script>
</body>
</html>
