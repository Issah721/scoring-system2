<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); // Redirect to login page
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - User Management</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Specific styles for admin panel if needed, otherwise rely on styles.css */
        .form-section, .admin-section, .judge-section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid rgba(0, 255, 136, 0.2); /* Subtle border to group sections */
            border-radius: 8px;
        }
        table button { margin-right: 5px; }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>
    <div class="container">
        <h1>Admin Panel</h1>

        <!-- Add/Edit User Form -->
        <div id="userFormContainer" class="form-section">
            <h2 id="userFormTitle">Add New User</h2>
            <form id="manageUserForm">
                <input type="hidden" id="editUserId" name="editUserId">
                <div><label for="username">Username:</label><input type="text" id="username" name="username" required></div>
                <div><label for="display_name">Display Name:</label><input type="text" id="display_name" name="display_name" required></div>
                <div><label for="password">Password:</label><input type="password" id="password" name="password"> <small id="passwordHelp">(For new user or to change existing)</small></div>
                <div>
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="judge">Judge</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit">Save User</button>
                <button type="button" id="cancelEditBtn" style="display:none;">Cancel Edit</button>
            </form>
            <div id="userFormMessage" class="message" style="margin-top:10px;"></div>
        </div>

        <!-- Manage Admins Section -->
        <div class="admin-section">
            <h2>Manage Admins</h2>
            <button onclick="loadUsers('admin')" class="button-style">Load/Refresh Admins</button>
            <table id="adminTable">
                <thead><tr><th>Username</th><th>Display Name</th><th>Actions</th></tr></thead>
                <tbody id="adminList"></tbody>
            </table>
        </div>

        <!-- Manage Judges Section -->
        <div class="judge-section">
            <h2>Manage Judges</h2>
            <button onclick="loadUsers('judge')" class="button-style">Load/Refresh Judges</button>
            <table id="judgeTable">
                <thead><tr><th>Username</th><th>Display Name</th><th>Actions</th></tr></thead>
                <tbody id="judgeList"></tbody>
            </table>
        </div>
        <div id="generalMessage" class="message" style="margin-top:15px;"></div>
    </div>

    <script>
    const userForm = document.getElementById('manageUserForm');
    const userFormTitle = document.getElementById('userFormTitle');
    const editUserIdInput = document.getElementById('editUserId');
    const usernameInput = document.getElementById('username');
    const displayNameInput = document.getElementById('display_name');
    const roleInput = document.getElementById('role');
    const passwordInput = document.getElementById('password');
    const passwordHelpText = document.getElementById('passwordHelp');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const userFormMessage = document.getElementById('userFormMessage');
    const generalMessage = document.getElementById('generalMessage');
    const userFormContainer = document.getElementById('userFormContainer'); // For scrolling

    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        // Basic XSS protection: replace problematic characters
        // More robust server-side sanitization is crucial.
        const SCRIPT_REGEX = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi;
        str = str.replace(SCRIPT_REGEX, ""); // Remove script tags

        const replacements = {
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        };
        return str.replace(/[&<>"']/g, match => replacements[match]);
    }

    userForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        const userId = editUserIdInput.value;
        const action = userId ? 'update_user' : 'add_user';
        
        const formData = new URLSearchParams();
        formData.append('username', usernameInput.value);
        formData.append('display_name', displayNameInput.value);
        formData.append('role', roleInput.value);

        const password = passwordInput.value;
        if (!userId && !password) { // Password required for new users
            userFormMessage.textContent = 'Password is required for new users.';
            userFormMessage.className = 'message error-message';
            return;
        }
        if (password) { // Only include password if provided (for new user or to change)
            formData.append('password', password);
        }

        if (userId) {
            formData.append('user_id', userId);
        }

        userFormMessage.textContent = 'Processing...';
        userFormMessage.className = 'message';

        try {
            const response = await fetch(`api.php?action=${action}`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                userFormMessage.textContent = result.message;
                userFormMessage.className = 'message success-message';
                userForm.reset(); // Clear form fields
                editUserIdInput.value = ''; // Clear hidden user ID
                userFormTitle.textContent = 'Add New User';
                passwordHelpText.textContent = '(For new user or to change existing)';
                passwordInput.required = true; // Re-enable required for add mode
                cancelEditBtn.style.display = 'none';
                loadUsers(roleInput.value); // Refresh list for the role just added/edited
            } else {
                userFormMessage.textContent = 'Error: ' + (result.error || 'Operation failed');
                userFormMessage.className = 'message error-message';
            }
        } catch (err) {
            console.error(`${action} error:`, err);
            userFormMessage.textContent = `An error occurred. (${action}). Check console.`;
            userFormMessage.className = 'message error-message';
        }
    });

    cancelEditBtn.addEventListener('click', () => {
        userForm.reset();
        editUserIdInput.value = '';
        userFormTitle.textContent = 'Add New User';
        passwordHelpText.textContent = '(For new user or to change existing)';
        passwordInput.required = true; // Back to required for "Add" mode
        cancelEditBtn.style.display = 'none';
        userFormMessage.textContent = '';
        userFormMessage.className = 'message';
    });

    async function loadUsers(role) {
        const listElementId = role === 'admin' ? 'adminList' : 'judgeList';
        const listElement = document.getElementById(listElementId);
        if (!listElement) {
            console.error(`Table body for role ${role} not found.`);
            return;
        }
        listElement.innerHTML = '<tr><td colspan="3">Loading...</td></tr>'; 
        generalMessage.textContent = ''; // Clear general messages

        try {
            const response = await fetch(`api.php?action=get_users&role=${role}`);
            const result = await response.json(); // Expecting an array of users or an object with an error

            if (!response.ok) {
                // Handle HTTP errors (e.g., 401, 403 from check_session)
                listElement.innerHTML = `<tr><td colspan="3" style="color:red;">Error: ${result.error || `HTTP ${response.status}`}</td></tr>`;
                return;
            }
            
            // Assuming result is directly the array of users if response.ok
            const users = result; 

            listElement.innerHTML = ''; 
            if (users.length === 0) {
                listElement.innerHTML = `<tr><td colspan="3">No users found for role: ${role}.</td></tr>`;
                return;
            }
            users.forEach(user => {
                const row = listElement.insertRow();
                // Sanitize user data before inserting into HTML
                const safeUsername = htmlspecialchars(user.username);
                const safeDisplayName = htmlspecialchars(user.display_name);
                // Pass the original user object to setupEditForm, it will handle its own escaping for form values
                row.innerHTML = `
                    <td>${safeUsername}</td>
                    <td>${safeDisplayName}</td>
                    <td>
                        <button onclick='setupEditForm(${JSON.stringify(user).replace(/'/g, "&apos;").replace(/"/g, "&quot;")})' class="button-style">Edit</button>
                        <button onclick="deleteUser(${user.user_id}, '${user.role}')" class="button-style">Delete</button>
                    </td>
                `;
            });
        } catch (error) {
            console.error(`Error loading ${role}s:`, error);
            listElement.innerHTML = `<tr><td colspan="3" style="color:red;">Failed to load ${role}s. Check console.</td></tr>`;
        }
    }
    
    function setupEditForm(user) {
        userForm.reset(); 
        userFormTitle.textContent = 'Edit User: ' + htmlspecialchars(user.display_name);
        editUserIdInput.value = user.user_id;
        usernameInput.value = htmlspecialchars(user.username);
        displayNameInput.value = htmlspecialchars(user.display_name);
        roleInput.value = user.role;
        passwordInput.placeholder = '(Leave blank to keep current password)';
        passwordInput.required = false; // Password not required for edit
        passwordHelpText.textContent = '(Leave blank to keep current password)';
        userFormMessage.textContent = '';
        userFormMessage.className = 'message';
        cancelEditBtn.style.display = 'inline-block';
        window.scrollTo({ top: userFormContainer.offsetTop - 20, behavior: 'smooth' }); // Scroll to form
    }

    async function deleteUser(userId, role) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }
        generalMessage.textContent = 'Deleting...';
        generalMessage.className = 'message';

        try {
            const formData = new URLSearchParams();
            formData.append('user_id', userId);

            const response = await fetch('api.php?action=delete_user', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                generalMessage.textContent = result.message;
                generalMessage.className = 'message success-message';
                loadUsers(role); // Refresh the list
            } else {
                generalMessage.textContent = 'Error: ' + (result.error || 'Deletion failed');
                generalMessage.className = 'message error-message';
            }
        } catch (error) {
            console.error('Delete user error:', error);
            generalMessage.textContent = 'An error occurred during deletion. Check console.';
            generalMessage.className = 'message error-message';
        }
    }
    
    // Optional: Load users when the page is ready
    // window.addEventListener('DOMContentLoaded', () => {
    //     loadUsers('admin');
    //     loadUsers('judge');
    // });
    </script>
</body>
</html>
