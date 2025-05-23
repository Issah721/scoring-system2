<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session check for judge role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'judge') {
    header('Location: login.php');
    exit;
}

// Get logged-in judge's details for convenience
$loggedInJudgeId = $_SESSION['user_id']; // This is the judge_user_id for API calls
$loggedInJudgeName = $_SESSION['display_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judge Portal - Score Participants</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Styles for the list of users to score */
        #usersToScoreArea ul {
            list-style: none;
            padding: 0;
        }
        #usersToScoreArea li {
            background-color: #3c3c3c; /* Card background for each item, from styles.css */
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .score-form {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }
        .score-form span { /* Participant name */
            flex-grow: 1;
            margin-right: 10px; 
        }
        .score-form input[type="number"] {
            width: 100px; 
            margin-right: 10px;
            margin-left: 5px; /* Original had this, keeping for consistency */
        }
         /* Responsive adjustments for smaller screens */
        @media (max-width: 600px) {
            .score-form {
                flex-direction: column;
                align-items: flex-start;
            }
            .score-form span, 
            .score-form input[type="number"], 
            .score-form button {
                width: 100%;
                margin-bottom: 10px;
            }
            .score-form input[type="number"] {
                 margin-right: 0; 
                 margin-left: 0;
            }
            .score-form button {
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>
    <div class="container">
        <h1>Judge Portal</h1>

        <!-- Display logged-in judge's name -->
        <div id="judgeInfoArea" style="margin-bottom: 20px; padding: 10px; background-color: rgba(0,0,0,0.2); border-radius: 5px;">
             <h2>Scoring as: <?php echo htmlspecialchars($loggedInJudgeName, ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>

        <div id="usersToScoreArea">
            <h3>Participants to Score</h3>
            <!-- Participants to score will be dynamically loaded here -->
        </div>

        <div id="message" class="message" style="margin-top: 15px;"></div>
    </div>

    <script>
        // Note: loggedInJudgeIdJS is not strictly needed if API solely relies on session for judge_id
        // const loggedInJudgeIdJS = <?php echo json_encode($loggedInJudgeId); ?>; 

        const usersToScoreArea = document.getElementById('usersToScoreArea');
        const messageDiv = document.getElementById('message');

        // Corrected htmlspecialchars function
        function htmlspecialchars(str) {
            if (typeof str !== 'string') return '';
            const SCRIPT_REGEX = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi;
            str = str.replace(SCRIPT_REGEX, ""); 

            const replacements = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return str.replace(/[&<>"']/g, match => replacements[match]);
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadUsersToScore(); 
        });

        async function loadUsersToScore() {
            const existingHeader = usersToScoreArea.querySelector('h3') || document.createElement('h3');
            if (!usersToScoreArea.querySelector('h3')) { // If h3 was not there (e.g. after clearing)
                existingHeader.textContent = 'Participants to Score';
            }
            usersToScoreArea.innerHTML = ''; // Clear previous content
            usersToScoreArea.appendChild(existingHeader); // Add header back
            
            const loadingP = document.createElement('p');
            loadingP.textContent = 'Loading participants...';
            usersToScoreArea.appendChild(loadingP);

            try {
                const response = await fetch(`api.php?action=get_users_not_scored`); 
                
                if (!response.ok) { // Check HTTP status code first
                    const errorData = await response.json().catch(() => ({error: `HTTP error! status: ${response.status}`}));
                    throw new Error(errorData.error);
                }
                
                const participants = await response.json();

                // Remove "Loading participants..." message
                if (loadingP.parentNode === usersToScoreArea) {
                    usersToScoreArea.removeChild(loadingP);
                }

                if (participants.error) { 
                    const errorP = document.createElement('p');
                    errorP.style.color = 'red';
                    errorP.textContent = `Error loading participants: ${htmlspecialchars(participants.error)}`;
                    usersToScoreArea.appendChild(errorP);
                    return;
                }
                if (participants.length === 0) {
                    const noUsersP = document.createElement('p');
                    noUsersP.textContent = 'No participants left to score.';
                    usersToScoreArea.appendChild(noUsersP);
                    return;
                }
                
                const ul = document.createElement('ul');
                // Styling from prompt's example
                ul.style.listStyle = 'none'; 
                ul.style.padding = '0';

                participants.forEach(participant => {
                    const li = document.createElement('li');
                    li.setAttribute('data-participant-id', participant.participant_id); 
                    // Styling from prompt's example
                    // li.style.marginBottom = '15px'; (Handled by CSS)

                    const form = document.createElement('form');
                    form.classList.add('score-form'); // For styling
                    form.innerHTML = `
                        <span>${htmlspecialchars(participant.display_name)} (${htmlspecialchars(participant.username)})</span>
                        <input type="number" name="points" min="1" max="100" required placeholder="Points (1-100)" style="margin: 0 10px; width: auto;">
                        <button type="submit" class="button-style">Submit Score</button> 
                    `; // Added button-style class
                    
                    form.addEventListener('submit', function(event) {
                        event.preventDefault();
                        const pointsInput = form.querySelector('input[name="points"]');
                        const points = parseInt(pointsInput.value, 10);
                        if (points >= 1 && points <= 100) {
                            submitScore(participant.participant_id, points, li); 
                        } else {
                            messageDiv.textContent = 'Points must be between 1 and 100.';
                            messageDiv.className = 'message error-message';
                            pointsInput.focus();
                        }
                    });
                    li.appendChild(form);
                    ul.appendChild(li);
                });
                usersToScoreArea.appendChild(ul);
            } catch (error) {
                console.error('Failed to load participants:', error);
                if (loadingP.parentNode === usersToScoreArea) { // Ensure loading message is removed on error too
                    usersToScoreArea.removeChild(loadingP);
                }
                const errorP = document.createElement('p');
                errorP.style.color = 'red';
                errorP.textContent = `Failed to load participants: ${htmlspecialchars(error.message)}. Check console.`;
                usersToScoreArea.appendChild(errorP);
            }
        }

        async function submitScore(participantId, points, listItemElement) {
            const formData = new URLSearchParams();
            formData.append('participant_id', participantId); 
            formData.append('points', points);
            // judge_id is handled by session in API

            messageDiv.textContent = 'Submitting...'; 
            messageDiv.className = 'message';

            try {
                const response = await fetch('api.php?action=add_score', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });
                const result = await response.json();

                if (response.status === 201 && result.success) {
                    messageDiv.textContent = result.message || 'Score submitted successfully!';
                    messageDiv.className = 'message success-message';
                    listItemElement.remove(); 
                    if (usersToScoreArea.querySelectorAll('li').length === 0) {
                         const existingHeader = usersToScoreArea.querySelector('h3') || document.createElement('h3');
                         if (!usersToScoreArea.querySelector('h3')) {
                             existingHeader.textContent = 'Participants to Score';
                         }
                         usersToScoreArea.innerHTML = ''; 
                         usersToScoreArea.appendChild(existingHeader);
                         const noUsersP = document.createElement('p');
                         noUsersP.textContent = 'No participants left to score.';
                         usersToScoreArea.appendChild(noUsersP);
                    }
                } else {
                    messageDiv.textContent = 'Error: ' + (result.error || 'Could not submit score.');
                    messageDiv.className = 'message error-message';
                }
            } catch (error) {
                console.error('Failed to submit score:', error);
                messageDiv.textContent = 'An unexpected error occurred while submitting the score. Check console.';
                messageDiv.className = 'message error-message';
            }
        }
    </script>
</body>
</html>
