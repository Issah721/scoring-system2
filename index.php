<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competition Platform</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="tab-navigation">
        <a href="#admin" id="admin-tab">Admin Panel</a>
        <a href="#judge" id="judge-tab">Judge Portal</a>
        <a href="#scoreboard" id="scoreboard-tab" class="active">Scoreboard</a>
    </div>

    <div class="tab-content-container">
        <div id="admin-content" class="tab-content">
            <div class="container">
                <h1>Add New Judge</h1>
                <form id="addJudgeForm">
                    <div>
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div>
                        <label for="display_name">Display Name:</label>
                        <input type="text" id="display_name" name="display_name" required>
                    </div>
                    <button type="submit">Add Judge</button>
                </form>
                <div id="message" style="margin-top: 15px; font-weight: bold; text-align: center;"></div>
            </div>
        </div>
        <div id="judge-content" class="tab-content">
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

                <div id="message" style="margin-top: 15px; font-weight: bold; text-align: center;"></div>
            </div>
        </div>
        <div id="scoreboard-content" class="tab-content active">
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
                <div id="message" style="margin-top: 15px; font-weight: bold; text-align: center;"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabLinks = document.querySelectorAll('.tab-navigation a');
            const tabContents = document.querySelectorAll('.tab-content');

            tabLinks.forEach(link => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();

                    // Remove active class from all tab links
                    tabLinks.forEach(l => l.classList.remove('active'));
                    // Remove active class from all tab contents
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to the clicked tab link
                    this.classList.add('active');

                    // Add active class to the corresponding tab content
                    const targetId = this.getAttribute('href').substring(1); // Get href value like 'admin', 'judge'
                    const targetContent = document.getElementById(targetId + '-content');
                    if (targetContent) {
                        targetContent.classList.add('active');
                    }
                });
            });

            // Optional: Ensure initial state is correctly set if HTML doesn't guarantee it
            // However, the HTML already sets the 'scoreboard-tab' and 'scoreboard-content' as active.
            // This script will respect that and only change it on click.

            // Admin Panel: Add Judge Form Logic
            const addJudgeForm = document.getElementById('addJudgeForm');
            if (addJudgeForm) { // Check if the form exists on the page
                addJudgeForm.addEventListener('submit', function(event) {
                    event.preventDefault();

                    // It's good practice to get elements like messageDiv inside the event handler
                    // if the content of the tab could be reloaded or is dynamic.
                    // For this specific setup, getting it once outside might be fine if admin-content is static once loaded.
                    const usernameInput = document.getElementById('username');
                    const displayNameInput = document.getElementById('display_name');
                    // Important: Ensure this 'message' ID is unique within the #admin-content
                    // or scoped correctly if other tabs might have a div with id="message".
                    const messageDiv = document.querySelector('#admin-content #message'); 

                    if (!usernameInput || !displayNameInput || !messageDiv) {
                        console.error('Admin form elements not found. Ensure IDs are correct and within #admin-content.');
                        return;
                    }
                    
                    const username = usernameInput.value;
                    const displayName = displayNameInput.value;

                    messageDiv.textContent = '';
                    messageDiv.style.color = '';

                    const formData = new URLSearchParams();
                    formData.append('username', username);
                    formData.append('display_name', displayName);

                    fetch('api.php?action=add_judge', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData
                    })
                    .then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(data => {
                        if (data.status === 201) {
                            messageDiv.textContent = (data.body && data.body.message) ? data.body.message : 'Judge added successfully!';
                            messageDiv.style.color = '#00ff88';
                            addJudgeForm.reset();
                        } else {
                            messageDiv.textContent = 'Error: ' + ((data.body && data.body.error) ? data.body.error : 'Unknown error occurred.');
                            messageDiv.style.color = 'red';
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        messageDiv.textContent = 'An unexpected error occurred. Please check the console and try again.';
                        messageDiv.style.color = 'red';
                    });
                });
            }

            // Judge Portal Logic
            const judgeSelect = document.querySelector('#judge-content #judgeSelect');
            const usersToScoreArea = document.querySelector('#judge-content #usersToScoreArea');
            const judgeMessageDiv = document.querySelector('#judge-content #message'); // Renamed to avoid conflict with admin's messageDiv
            let currentJudgeId = null;

            async function loadJudges() {
                if (!judgeSelect || !judgeMessageDiv) {
                    // console.error("Judge portal elements not found for loading judges.");
                    // This might be called before the tab is visible/fully initialized.
                    // Or if the element IDs are incorrect.
                    return;
                }
                try {
                    const response = await fetch('api.php?action=get_judges');
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ error: `HTTP error ${response.status}` }));
                        throw new Error(errorData.error);
                    }
                    const judges = await response.json();
                    
                    if (judges.error) {
                        judgeMessageDiv.textContent = 'Error loading judges: ' + judges.error;
                        judgeMessageDiv.style.color = 'red';
                        return;
                    }

                    judges.forEach(judge => {
                        const option = new Option(judge.display_name + ' (' + judge.username + ')', judge.judge_id);
                        judgeSelect.add(option);
                    });
                } catch (error) {
                    console.error('Failed to load judges:', error);
                    judgeMessageDiv.textContent = 'Failed to load judges: ' + error.message + '. Check console.';
                    judgeMessageDiv.style.color = 'red';
                }
            }

            if (judgeSelect) {
                judgeSelect.addEventListener('change', function() {
                    currentJudgeId = this.value;
                    if (judgeMessageDiv) judgeMessageDiv.textContent = ''; 
                    if (usersToScoreArea) usersToScoreArea.innerHTML = ''; 
                    if (currentJudgeId) {
                        if (usersToScoreArea) {
                            const header = document.createElement('h3');
                            header.textContent = 'Users to Score';
                            usersToScoreArea.appendChild(header);
                            loadUsersToScore(currentJudgeId);
                        }
                    }
                });
            }

            async function loadUsersToScore(judgeId) {
                if (!judgeId || !usersToScoreArea || !judgeMessageDiv) return;
                
                let header = usersToScoreArea.querySelector('h3');
                if (!header) {
                    header = document.createElement('h3');
                    header.textContent = 'Users to Score';
                    usersToScoreArea.innerHTML = ''; 
                    usersToScoreArea.appendChild(header);
                }
                const loadingP = document.createElement('p');
                loadingP.textContent = 'Loading users...';
                usersToScoreArea.appendChild(loadingP);

                try {
                    const response = await fetch(`api.php?action=get_users_not_scored&judge_id=${judgeId}`);
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ error: `HTTP error ${response.status}` }));
                        throw new Error(errorData.error);
                    }
                    const users = await response.json();

                    if (usersToScoreArea.contains(loadingP)) {
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

                    users.forEach(user => {
                        const li = document.createElement('li');
                        li.setAttribute('data-user-id', user.user_id);
                        li.style.backgroundColor = '#3c3c3c';
                        li.style.padding = '15px';
                        li.style.marginBottom = '10px';
                        li.style.borderRadius = '5px';
                        li.style.border = '1px solid rgba(255, 255, 255, 0.2)';

                        const form = document.createElement('form');
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
                                if(judgeMessageDiv) {
                                    judgeMessageDiv.textContent = 'Points must be between 1 and 100.';
                                    judgeMessageDiv.style.color = 'red';
                                }
                                pointsInput.focus();
                            }
                        });
                        li.appendChild(form);
                        ul.appendChild(li);
                    });
                    usersToScoreArea.appendChild(ul);
                } catch (error) {
                    console.error('Failed to load users:', error);
                    if (usersToScoreArea.contains(loadingP)) {
                        usersToScoreArea.removeChild(loadingP);
                    }
                    const errorP = document.createElement('p');
                    errorP.style.color = 'red';
                    errorP.textContent = 'Failed to load users: ' + error.message + '. Check console.';
                    usersToScoreArea.appendChild(errorP);
                }
            }

            async function submitScore(userId, judgeId, points, listItemElement) {
                if (!judgeMessageDiv) return;

                const formData = new URLSearchParams();
                formData.append('user_id', userId); 
                formData.append('judge_id', judgeId);
                formData.append('points', points);

                judgeMessageDiv.textContent = 'Submitting...';
                judgeMessageDiv.style.color = '';

                try {
                    const response = await fetch('api.php?action=add_score', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });
                    const result = await response.json();

                    if (response.status === 201) { 
                        judgeMessageDiv.textContent = result.message || 'Score submitted successfully!';
                        judgeMessageDiv.style.color = '#00ff88';
                        listItemElement.remove();
                        
                        if (usersToScoreArea) {
                            const remainingItems = usersToScoreArea.querySelectorAll('li');
                            if (remainingItems.length === 0) {
                                let header = usersToScoreArea.querySelector('h3');
                                if (!header) {
                                    header = document.createElement('h3');
                                    header.textContent = 'Users to Score';
                                    usersToScoreArea.innerHTML = ''; 
                                    usersToScoreArea.appendChild(header);
                                } else {
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
                        }
                    } else {
                        judgeMessageDiv.textContent = 'Error: ' + (result.error || 'Could not submit score.');
                        judgeMessageDiv.style.color = 'red';
                    }
                } catch (error) {
                    console.error('Failed to submit score:', error);
                    judgeMessageDiv.textContent = 'An unexpected error occurred while submitting the score. Check console.';
                    judgeMessageDiv.style.color = 'red';
                }
            }

            // Initial call to load judges for the Judge Portal
            // This needs to be called after the DOM is ready and elements are available.
            // If judgeSelect is null here (e.g. if script runs before #judge-content is fully processed by browser),
            // loadJudges() will do nothing.
            if (document.readyState === 'loading') { // If DOM hasn't finished loading
                 //This should not happen as we are inside DOMContentLoaded
            } else { // DOMContentLoaded has already fired or elements are ready
                if(judgeSelect && judgeMessageDiv) { // Ensure elements are there
                    loadJudges();
                } else {
                    // If the elements are not found, it means the querySelector failed.
                    // This might happen if the script is somehow executed before the HTML for judge portal is parsed.
                    // Or if the IDs are incorrect.
                    // console.error("Judge portal elements not found for initial loadJudges call.");
                }
            }

            // Scoreboard Logic
            const scoreboardBody = document.querySelector('#scoreboard-content #scoreboardBody');
            const scoreboardMessageDiv = document.querySelector('#scoreboard-content #message'); // Scoped message div

            async function fetchScoreboard() {
                if (!scoreboardBody || !scoreboardMessageDiv) {
                    // console.error("Scoreboard elements not found.");
                    return;
                }
                try {
                    const response = await fetch('api.php?action=get_scoreboard');
                    const scores = await response.json().catch(() => null);

                    if (response.ok) {
                        if (scores && scores.error) {
                            scoreboardMessageDiv.textContent = 'Error loading scoreboard: ' + scores.error;
                            scoreboardMessageDiv.style.color = 'red';
                            scoreboardBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">Error loading data.</td></tr>';
                            return;
                        }

                        scoreboardMessageDiv.textContent = '';
                        scoreboardMessageDiv.style.color = '';
                        scoreboardBody.innerHTML = '';

                        if (!scores || scores.length === 0) {
                            const row = scoreboardBody.insertRow();
                            row.classList.add('fade-in');
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
                            nameCell.textContent = score.display_name;
                            const pointsCell = row.insertCell();
                            pointsCell.textContent = score.total_points;
                        });
                    } else {
                        const errorMsg = (scores && scores.error) ? scores.error : `HTTP ${response.status} Error`;
                        scoreboardMessageDiv.textContent = 'Error loading scoreboard: ' + errorMsg;
                        scoreboardMessageDiv.style.color = 'red';
                        scoreboardBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">Error loading data.</td></tr>';
                    }
                } catch (error) {
                    console.error('Failed to fetch scoreboard:', error);
                    scoreboardMessageDiv.textContent = 'Failed to fetch scoreboard. Check console for details.';
                    scoreboardMessageDiv.style.color = 'red';
                    scoreboardBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">Error loading data.</td></tr>';
                }
            }

            // Initial fetch for scoreboard (since it's the active tab)
            if (scoreboardBody && scoreboardMessageDiv) {
                 fetchScoreboard();
            } else {
                // console.error("Scoreboard elements not found for initial fetchScoreboard call.");
            }
            // Set interval for scoreboard auto-refresh
            setInterval(fetchScoreboard, 10000);

        });
    </script>
    <script src="scripts.js"></script>
</body>
</html>
