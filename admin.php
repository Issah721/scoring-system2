<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Add Judge</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <!-- Inline styles for messages are not needed here if using JS to set color -->
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
        <div id="message" style="margin-top: 15px; font-weight: bold; text-align: center;"></div> <!-- Basic message div -->
    </div>

    <script>
        document.getElementById('addJudgeForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const username = document.getElementById('username').value;
            const displayName = document.getElementById('display_name').value;
            const messageDiv = document.getElementById('message');
            
            // Clear previous message and style
            messageDiv.textContent = '';
            messageDiv.style.color = ''; // Reset color

            const formData = new URLSearchParams();
            formData.append('username', username);
            formData.append('display_name', displayName);

            fetch('api.php?action=add_judge', { // V1 endpoint
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(data => {
                if (data.status === 201) { // V1 add_judge returned 201 for success
                    messageDiv.textContent = (data.body && data.body.message) ? data.body.message : 'Judge added successfully!';
                    messageDiv.style.color = '#00ff88'; // V1 success color (greenish)
                    document.getElementById('addJudgeForm').reset(); 
                } else {
                    // Try to get specific error from body, otherwise generic
                    messageDiv.textContent = 'Error: ' + ((data.body && data.body.error) ? data.body.error : 'Unknown error occurred.');
                    messageDiv.style.color = 'red'; // V1 error color
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                messageDiv.textContent = 'An unexpected error occurred. Please check the console and try again.';
                messageDiv.style.color = 'red';
            });
        });
    </script>
</body>
</html>
