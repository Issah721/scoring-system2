<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Add Judge</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Additional styles for message div for better visibility */
        #message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        #message.success {
            color: #1e1e1e; /* Dark text for contrast */
            background-color: #00ff88; /* Primary color */
        }
        #message.error {
            color: #ffffff; /* White text */
            background-color: #ff4d4d; /* A clear red */
        }
        label { /* Ensure labels are also visible */
            display: block;
            margin-bottom: 5px;
            color: #ffffff; 
        }
    </style>
</head>
<body>
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
        <div id="message"></div> <!-- Message div will be styled by JS -->
    </div>

    <script>
        document.getElementById('addJudgeForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const username = document.getElementById('username').value;
            const displayName = document.getElementById('display_name').value;
            const messageDiv = document.getElementById('message');

            // Clear previous messages and styles
            messageDiv.textContent = '';
            messageDiv.className = '';

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
                    messageDiv.textContent = data.body.message || 'Judge added successfully!';
                    messageDiv.classList.add('success');
                    document.getElementById('addJudgeForm').reset(); // Clear form
                } else {
                    messageDiv.textContent = 'Error: ' + (data.body.error || 'Unknown error');
                    messageDiv.classList.add('error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.textContent = 'An unexpected error occurred. Please check the console and try again.';
                messageDiv.classList.add('error');
            });
        });
    </script>
</body>
</html>
