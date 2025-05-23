<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Scoring System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Additional styles for login page feedback messages */
        #message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .error-message {
            color: #ffffff; /* White text for better contrast on dark background */
            background-color: #ff4d4d; /* A clear red */
        }
        .success-message {
            color: #1e1e1e; /* Dark text for contrast */
            background-color: #00ff88; /* Primary color */
        }
        label { /* Ensure labels are also visible if not covered by main styles.css */
            display: block;
            margin-bottom: 5px;
            color: #ffffff; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <form id="loginForm">
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <div id="message"></div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const messageDiv = document.getElementById('message');

            // Clear previous messages
            messageDiv.textContent = '';
            messageDiv.className = ''; // Reset class

            const formData = new URLSearchParams();
            formData.append('username', username);
            formData.append('password', password);

            fetch('api.php?action=login', { // This API endpoint will be created later
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(data => {
                if (data.status === 200 && data.body.success) {
                    messageDiv.textContent = 'Login successful! Redirecting...';
                    messageDiv.classList.add('success-message');
                    
                    // Store user info if needed, e.g., in localStorage or rely on session
                    // For now, just redirecting
                    if (data.body.role === 'admin') {
                        window.location.href = 'admin.php';
                    } else if (data.body.role === 'judge') {
                        window.location.href = 'judge.php';
                    } else {
                        // Fallback or error if role is not as expected or missing
                        messageDiv.textContent = 'Login successful, but role is undefined. Redirecting to scoreboard.';
                        messageDiv.className = 'error-message'; // Use error style for unexpected role
                        setTimeout(() => { window.location.href = 'scoreboard.php'; }, 2000);
                    }
                } else {
                    messageDiv.textContent = 'Error: ' + (data.body.error || 'Invalid username or password.');
                    messageDiv.classList.add('error-message');
                }
            })
            .catch(error => {
                console.error('Login Error:', error);
                messageDiv.textContent = 'An unexpected error occurred during login. Please check the console and try again.';
                messageDiv.classList.add('error-message');
            });
        });
    </script>
</body>
</html>
