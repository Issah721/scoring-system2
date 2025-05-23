<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judge Portal - Score Users</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Judge Portal</h1>

        <div id="judgeSelectionArea">
            <h2>Select Judge</h2>
            <label for="judgeSelect" style="display:block; margin-bottom:5px;">Judge:</label> <!-- Added label for clarity -->
            <select id="judgeSelect" name="judge_id">
                <option value="">-- Select a Judge --</option>
            </select>
        </div>

        <div id="usersToScoreArea" style="margin-top: 20px;">
            <!-- Users to score will be dynamically loaded here -->
        </div>

        <div id="message" style="margin-top: 15px; font-weight: bold; text-align: center;"></div> <!-- Basic message div -->
    </div>

    <script>
        const judgeSelect = document.getElementById('judgeSelect');
        const usersToScoreArea = document.getElementById('usersToScoreArea');
        const messageDiv = document.getElementById('message');
        let currentJudgeId = null;

        async function loadJudges() {
            try {
                const response = await fetch('api.php?action=get_judges'); // V1 endpoint
                if (!response.ok) { // Check HTTP status first
                    const errorData = await response.json().catch(() => ({ error: `HTTP error ${response.status}` }));
                    throw new Error(errorData.error);
                }
                const judges = await response.json();
                
                // Check for error property in the parsed JSON, even if response.ok
                if (judges.error) { 
                    messageDiv.textContent = 'Error loading judges: ' + judges.error;
                    messageDiv.style.color = 'red';
                    return;
                }

                judges.forEach(judge => {
                    const option = new Option(judge.display_name + ' (' + judge.username + ')', judge.judge_id);
                    judgeSelect.add(option);
                });
            } catch (error) {
                console.error('Failed to load judges:', error);
                messageDiv.textContent = 'Failed to load judges: ' + error.message + '. Check console.';
                messageDiv.style.color = 'red';
            }
        }

        judgeSelect.addEventListener('change', function() {
            currentJudgeId = this.value;
            messageDiv.textContent = ''; // Clear message
            usersToScoreArea.innerHTML = ''; // Clear previous user list
            if (currentJudgeId) {
                // Add a header before loading users
                const header = document.createElement('h3');
                header.textContent = 'Users to Score';
                usersToScoreArea.appendChild(header);
                loadUsersToScore(currentJudgeId);
            }
        });

        async function loadUsersToScore(judgeId) {
            if (!judgeId) return;
            
            // Ensure usersToScoreArea has the header, then add loading message
            let header = usersToScoreArea.querySelector('h3');
            if (!header) {
                header = document.createElement('h3');
                header.textContent = 'Users to Score';
                usersToScoreArea.innerHTML = ''; // Clear if header was missing
                usersToScoreArea.appendChild(header);
            }
            const loadingP = document.createElement('p');
            loadingP.textContent = 'Loading users...';
            usersToScoreArea.appendChild(loadingP);

            try {
                const response = await fetch(`api.php?action=get_users_not_scored&judge_id=${judgeId}`); // V1
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: `HTTP error ${response.status}` }));
                    throw new Error(errorData.error);
                }
                const users = await response.json(); // V1: these are participants

                // Clear only loading message, keep header
                if (loadingP.parentNode === usersToScoreArea) {
                     usersToScoreArea.removeChild(loadingP);
                }
                
                if (users.error) {
                    const errorP = document.createElement('p');
                    errorP.style.color = 'red';
                    errorP.textContent = `Error: ${users.error}`;
                    usersToScoreArea.appendChild(errorP);
                    return;
                }

                if (users.length === 0) {
                    const noUsersP = document.createElement('p');
                    noUsersP.textContent = 'No users left to score for this judge.';
                    usersToScoreArea.appendChild(noUsersP);
                    return;
                }
                const ul = document.createElement('ul');
                ul.style.listStyle = 'none';
                ul.style.padding = '0';

                users.forEach(user => { // user here is a participant
                    const li = document.createElement('li');
                    li.setAttribute('data-user-id', user.user_id); // V1: participant's user_id
                    li.style.backgroundColor = '#3c3c3c'; // V1 style for item
                    li.style.padding = '15px';
                    li.style.marginBottom = '10px';
                    li.style.borderRadius = '5px';
                    li.style.border = '1px solid rgba(255, 255, 255, 0.2)';


                    const form = document.createElement('form');
                    // V1 styling for form items might be simpler or rely on global styles.css
                    form.style.display = 'flex';
                    form.style.justifyContent = 'space-between';
                    form.style.alignItems = 'center';
                    
                    form.innerHTML = `
                        <span style="flex-grow: 1; margin-right: 10px;">${user.display_name} (${user.username})</span>
                        <input type="number" name="points" min="1" max="100" required placeholder="Points (1-100)" style="width: 100px; margin-right: 10px;">
                        <button type="submit">Submit Score</button>
                    `;
                    
                    form.addEventListener('submit', function(event) {
                        event.preventDefault();
                        const pointsInput = form.querySelector('input[name="points"]');
                        const points = parseInt(pointsInput.value, 10);
                        if (points >= 1 && points <= 100) {
                            submitScore(user.user_id, judgeId, pointsInput.value, li); 
                        } else {
                            messageDiv.textContent = 'Points must be between 1 and 100.';
                            messageDiv.style.color = 'red';
                            pointsInput.focus();
                        }
                    });
                    li.appendChild(form);
                    ul.appendChild(li);
                });
                usersToScoreArea.appendChild(ul);
            } catch (error) {
                console.error('Failed to load users:', error);
                if (loadingP.parentNode === usersToScoreArea) { // Remove loading if error occurs
                    usersToScoreArea.removeChild(loadingP);
                }
                const errorP = document.createElement('p');
                errorP.style.color = 'red';
                errorP.textContent = 'Failed to load users: ' + error.message + '. Check console.';
                usersToScoreArea.appendChild(errorP);
            }
        }

        async function submitScore(userId, judgeId, points, listItemElement) { // userId is participant's
            const formData = new URLSearchParams();
            formData.append('user_id', userId); 
            formData.append('judge_id', judgeId);
            formData.append('points', points);

            messageDiv.textContent = 'Submitting...';
            messageDiv.style.color = ''; // Reset color

            try {
                const response = await fetch('api.php?action=add_score', { // V1 endpoint
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });
                const result = await response.json();

                if (response.status === 201) { 
                    messageDiv.textContent = result.message || 'Score submitted successfully!';
                    messageDiv.style.color = '#00ff88';
                    listItemElement.remove();
                    
                    // Check if no users are left to score after removal
                    const remainingItems = usersToScoreArea.querySelectorAll('li');
                    if (remainingItems.length === 0) {
                        let header = usersToScoreArea.querySelector('h3');
                        if (!header) { // Should not happen if logic is correct, but as a safeguard
                            header = document.createElement('h3');
                            header.textContent = 'Users to Score';
                            usersToScoreArea.innerHTML = ''; // Clear area
                            usersToScoreArea.appendChild(header);
                        } else {
                            // Clear everything except the header
                            let child = usersToScoreArea.lastChild; 
                            while (child && child !== header) {
                                usersToScoreArea.removeChild(child);
                                child = usersToScoreArea.lastChild;
                            }
                        }
                        const noUsersP = document.createElement('p');
                        noUsersP.textContent = 'No users left to score for this judge.';
                        usersToScoreArea.appendChild(noUsersP);
                    }
                } else {
                    messageDiv.textContent = 'Error: ' + (result.error || 'Could not submit score.');
                    messageDiv.style.color = 'red';
                }
            } catch (error) {
                console.error('Failed to submit score:', error);
                messageDiv.textContent = 'An unexpected error occurred while submitting the score. Check console.';
                messageDiv.style.color = 'red';
            }
        }

        document.addEventListener('DOMContentLoaded', loadJudges);
    </script>
</body>
</html>
