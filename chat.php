<?php
$chatFilename = 'chat_log.txt';
$usersFilename = 'users.txt';

// Function to clear messages older than 1 hour
function clearOldMessages() {
    global $chatFilename;
    if (file_exists($chatFilename)) {
        $fileContents = file($chatFilename, FILE_IGNORE_NEW_LINES);
        $currentTime = time();
        $filteredContents = array_filter($fileContents, function($line) use ($currentTime) {
            $parts = explode('|', $line);
            $timestamp = isset($parts[0]) ? (int)$parts[0] : 0;
            return ($currentTime - $timestamp) < 3600; // Keep messages within the last hour
        });
        file_put_contents($chatFilename, implode(PHP_EOL, $filteredContents) . PHP_EOL);
    }
}

// Function to save a user as online
function saveUser($username) {
    global $usersFilename;
    $existingUsers = file_exists($usersFilename) ? file($usersFilename, FILE_IGNORE_NEW_LINES) : [];
    $existingUsers = array_map('trim', $existingUsers); // Remove any extra whitespace
    if (!in_array($username, $existingUsers)) {
        file_put_contents($usersFilename, $username . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

// Function to remove a user
function removeUser($username) {
    global $usersFilename;
    if (file_exists($usersFilename)) {
        $fileContents = file($usersFilename, FILE_IGNORE_NEW_LINES);
        $filteredContents = array_filter($fileContents, function($line) use ($username) {
            return trim($line) !== $username;
        });
        file_put_contents($usersFilename, implode(PHP_EOL, $filteredContents) . PHP_EOL);
    }
}

// Handle saving new messages and managing users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strip_tags($_POST['username']);
    $message = strip_tags($_POST['message']);
    $message = trim($message);

    if (isset($_POST['logout'])) {
        removeUser($username);
        exit();
    }

    if (!empty($username) && !empty($message)) {
        $formattedMessage = time() . '|' . "$username: $message" . PHP_EOL;
        file_put_contents($chatFilename, $formattedMessage, FILE_APPEND | LOCK_EX);
    }

    if (!empty($username)) {
        saveUser($username);
    }
    exit();
}

// Handle retrieving messages and users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    clearOldMessages(); // Clear old messages before showing chat log

    // Show chat log
    if (isset($_GET['type']) && $_GET['type'] === 'users') {
        if (file_exists($usersFilename)) {
            $usersList = file($usersFilename, FILE_IGNORE_NEW_LINES);
            $usersList = array_map('trim', $usersList); // Clean up whitespace
            // Remove duplicates
            $usersList = array_unique($usersList);
            echo json_encode($usersList);
        }
    } else {
        if (file_exists($chatFilename)) {
            $chatLog = file_get_contents($chatFilename);
            $chatLog = preg_replace('/^\d+\|/m', '', $chatLog); // Remove timestamps for display
            // Convert URLs to images
            $chatLog = preg_replace('/(https?:\/\/[^\s]+\.(?:png|jpg|jpeg|gif))/i', '<img src="$1" style="max-width: 100%; height: auto;"/>', $chatLog);
            echo nl2br($chatLog);
        }
    }
    exit();
}
?>
