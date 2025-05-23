<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judge Portal - Score Users</title>
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
        #message.success {
            color: #1e1e1e; /* Dark text for contrast */
            background-color: #00ff88; /* Primary color */
        }
        #message.error {
            color: #ffffff; /* White text */
            background-color: #ff4d4d; /* A clear red */
        }
        #usersToScoreArea ul {
            list-style: none;
            padding: 0;
        }
        #usersToScoreArea li {
            background-color: #3c3c3c; /* Card background for each item */
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }
        #usersToScoreArea li span {
            flex-grow: 1;
            margin-right: 10px; /* Space before input */
        }
        #usersToScoreArea input[type="number"] {
            width: 100px; /* Fixed width for points */
            margin-right: 10px; /* Space before button */
            margin-left: 5px;
        }
        /* Responsive adjustments for smaller screens */
        @media (max-width: 600px) {
            #usersToScoreArea li {
                flex-direction: column;
                align-items: flex-start;
            }
            #usersToScoreArea li span, 
            #usersToScoreArea input[type="number"], 
            #usersToScoreArea button {
                width: 100%;
                margin-bottom: 10px;
            }
            #usersToScoreArea input[type="number"] {
                 margin-right: 0; 
                 margin-left: 0;
            }
            #usersToScoreArea button {
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Judge Portal</h1>

        <div id="judgeSelectionArea">
            <h2>Select Judge</h2>
            <label for="judgeSelect" style="display:block; margin-bottom:5px;">Judge:</label>
            <select id="judgeSelect" name="judge_id">
                <option value="">-- Select a Judge --</option>
            </select>
        </div>

        <div id="usersToScoreArea" style="margin-top: 20px;">
            <!-- Users to score will be dynamically loaded here -->
        </div>

        <div id="message"></div>
    </div>

    <script>
        const judgeSelect = document.getElementById('judgeSelect');
        const usersToScoreArea = document.getElementById('usersToScoreArea');
        const messageDiv = document.getElementById('message');
        let currentJudgeId = null;

        async function loadJudges() {
            try {
                const response = await fetch('api.php?action=get_judges');
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: 'Failed to parse error response' }));
                    throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                }
                const judges = await response.json();
                
                judges.forEach(judge => {
                    const option = new Option(`${judge.display_name} (${judge.username})`, judge.judge_id);
                    judgeSelect.add(option);
                });
            } catch (error) {
                console.error('Failed to load judges:', error);
                messageDiv.textContent = `Error loading judges: ${error.message}`;
                messageDiv.className = 'error';
            }
        }

        judgeSelect.addEventListener('change', function() {
            currentJudgeId = this.value;
            messageDiv.textContent = ''; // Clear previous messages
            messageDiv.className = '';
            usersToScoreArea.innerHTML = ''; // Clear previous user list
            if (currentJudgeId) {
                const selectedJudgeName = this.options[this.selectedIndex].text;
                const h2 = document.createElement('h2');
                h2.textContent = `Users to Score (Judge: ${selectedJudgeName})`;
                usersToScoreArea.appendChild(h2);
                loadUsersToScore(currentJudgeId);
            }
        });

        async function loadUsersToScore(judgeId) {
            if (!judgeId) return;
            try {
                const response = await fetch(`api.php?action=get_users_not_scored&judge_id=${judgeId}`);
                if (!response.ok) {
                     const errorData = await response.json().catch(() => ({ error: 'Failed to parse error response' }));
                    throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                }
                const users = await response.json();

                if (users.length === 0) {
                    const p = document.createElement('p');
                    p.textContent = 'No users left to score for this judge.';
                    usersToScoreArea.appendChild(p);
                    return;
                }

                const ul = document.createElement('ul');
                users.forEach(user => {
                    const li = document.createElement('li');
                    li.setAttribute('data-user-id', user.user_id);

                    const userInfo = document.createElement('span');
                    userInfo.textContent = `${user.display_name} (${user.username})`;
                    
                    const pointsInput = document.createElement('input');
                    pointsInput.type = 'number';
                    pointsInput.name = 'points';
                    pointsInput.min = '1';
                    pointsInput.max = '100';
                    pointsInput.required = true;
                    pointsInput.placeholder = 'Points (1-100)';
                    
                    const submitButton = document.createElement('button');
                    submitButton.type = 'button'; // Important: type=button to prevent form submission if wrapped in a form tag later
                    submitButton.textContent = 'Submit Score';
                    submitButton.classList.add('button-style'); // Apply general button styling

                    submitButton.addEventListener('click', function() {
                        const points = parseInt(pointsInput.value, 10);
                        if (points >= 1 && points <= 100) {
                            submitScore(user.user_id, judgeId, points, li);
                        } else {
                            messageDiv.textContent = 'Points must be an integer between 1 and 100.';
                            messageDiv.className = 'error';
                            pointsInput.focus();
                        }
                    });

                    li.appendChild(userInfo);
                    li.appendChild(pointsInput);
                    li.appendChild(submitButton);
                    ul.appendChild(li);
                });
                usersToScoreArea.appendChild(ul);

            } catch (error) {
                console.error('Failed to load users:', error);
                const p = document.createElement('p');
                p.textContent = `Error loading users: ${error.message}`;
                p.style.color = 'red';
                usersToScoreArea.appendChild(p);
            }
        }

        async function submitScore(userId, judgeId, points, listItemElement) {
            messageDiv.textContent = ''; // Clear previous messages
            messageDiv.className = '';

            const formData = new URLSearchParams();
            formData.append('user_id', userId);
            formData.append('judge_id', judgeId);
            formData.append('points', points);

            try {
                const response = await fetch('api.php?action=add_score', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });
                
                const result = await response.json();

                if (response.status === 201) {
                    messageDiv.textContent = result.message || 'Score submitted successfully!';
                    messageDiv.className = 'success';
                    listItemElement.remove(); 
                    
                    // Check if no users are left
                    if (usersToScoreArea.querySelectorAll('li').length === 0) {
                        const p = document.createElement('p');
                        p.textContent = 'All users for this judge have been scored.';
                        // Keep the H2, just add this message.
                        // Find if an existing "no users left" message is there
                        const existingP = usersToScoreArea.querySelector('p');
                        if(existingP) existingP.remove();
                        usersToScoreArea.appendChild(p);
                    }
                } else {
                    messageDiv.textContent = 'Error: ' + (result.error || 'Could not submit score.');
                    messageDiv.className = 'error';
                }
            } catch (error) {
                console.error('Failed to submit score:', error);
                messageDiv.textContent = 'An unexpected error occurred while submitting the score. Check console.';
                messageDiv.className = 'error';
            }
        }

        // Initial load
        document.addEventListener('DOMContentLoaded', loadJudges);
    </script>
</body>
</html>
