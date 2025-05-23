<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="main-nav">
    <ul>
        <li><a href="scoreboard.php">Scoreboard</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="admin.php">Admin Panel</a></li>
            <?php elseif ($_SESSION['role'] === 'judge'): ?>
                <li><a href="judge.php">Judge Portal</a></li>
            <?php endif; ?>
            <li><a href="#" id="logoutLink">Logout</a></li> 
            <li style="color: #00ff88; margin-left: 20px; padding: 8px 15px; font-weight: bold; display: inline-block;">
                Welcome, <?php echo htmlspecialchars($_SESSION['display_name']); ?>!
            </li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<script>
// Ensure this script runs after the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const logoutLink = document.getElementById('logoutLink');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior

            fetch('api.php?action=logout', {
                method: 'POST', 
                headers: {
                    // If your API expects a specific content type for POST, add it here
                    // 'Content-Type': 'application/x-www-form-urlencoded', 
                }
                // No body is needed for a simple logout action if it's just clearing session
            })
            .then(response => {
                // Check if the response is ok, then try to parse JSON
                if (!response.ok) {
                    // If response is not ok, try to get error text or default error
                    return response.text().then(text => { 
                        throw new Error(text || 'Logout request failed with status: ' + response.status); 
                    });
                }
                return response.json(); // If response is ok, parse JSON
            })
            .then(data => {
                if (data.success) {
                    // Optionally display a message before redirecting
                    // alert('Logout successful!'); 
                    window.location.href = 'login.php'; // Redirect to login page
                } else {
                    // Display error message from server if available
                    alert('Logout failed: ' + (data.error || 'Please try again.'));
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                alert('An error occurred during logout. ' + error.message);
            });
        });
    }
});
</script>
