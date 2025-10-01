<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if not logged in
if (!checkLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle actions
if ($action === 'start' && isset($_GET['user_id'])) {
    $conversation_id = (int)$_GET['user_id'];
}

// Get user's conversations
$conversations = $pdo->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.user_id, u.first_name, u.last_name, u.user_type, u.profile_image,
        (SELECT message FROM messages WHERE (sender_id = ? AND receiver_id = other_user_id) OR (sender_id = other_user_id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE (sender_id = ? AND receiver_id = other_user_id) OR (sender_id = other_user_id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = other_user_id AND is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END = u.user_id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY last_message_time DESC
");
$conversations->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $conversations->fetchAll();

// Get messages for selected conversation
$messages = [];
$other_user = null;

if ($conversation_id) {
    // Verify the user has permission to view this conversation
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->execute([$user_id, $conversation_id, $conversation_id, $user_id]);
    
    if ($stmt->fetchColumn() > 0) {
        // Get messages for this conversation
        $stmt = $pdo->prepare("
            SELECT m.*, u.first_name, u.last_name, u.user_type, u.profile_image 
            FROM messages m 
            JOIN users u ON m.sender_id = u.user_id 
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
            OR (m.sender_id = ? AND m.receiver_id = ?) 
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user_id, $conversation_id, $conversation_id, $user_id]);
        $messages = $stmt->fetchAll();
        
        // Get other user info
        $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, user_type, profile_image FROM users WHERE user_id = ?");
        $stmt->execute([$conversation_id]);
        $other_user = $stmt->fetch();
        
        // Mark messages as read
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->execute([$conversation_id, $user_id]);
    } else {
        $conversation_id = null;
    }
}

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message']) && $conversation_id) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $conversation_id, $message]);
        
        // Redirect to prevent form resubmission
        redirect("messages.php?conversation_id=$conversation_id");
    }
}

// Handle starting new conversation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_conversation'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $message = trim($_POST['message']);
    
    if (!empty($message) && $receiver_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);
        
        // Redirect to the new conversation
        redirect("messages.php?conversation_id=$receiver_id");
    }
}

// Get users for new conversation (excluding current user and existing conversations)
$stmt = $pdo->prepare("
    SELECT user_id, first_name, last_name, user_type 
    FROM users 
    WHERE user_id != ? 
    AND user_id NOT IN (
        SELECT DISTINCT 
            CASE 
                WHEN sender_id = ? THEN receiver_id 
                ELSE sender_id 
            END as other_user_id
        FROM messages 
        WHERE sender_id = ? OR receiver_id = ?
    )
    ORDER BY first_name, last_name
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$new_users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        .messages-container {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .conversations-sidebar {
            flex: 0 0 300px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            height: calc(100vh - 200px);
            overflow-y: auto;
        }
        
        .messages-area {
            flex: 1;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px);
        }
        
        .conversations-list {
            margin-top: 1rem;
        }
        
        .conversation-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            text-decoration: none;
            color: var(--dark-color);
            transition: var(--transition);
        }
        
        .conversation-item:hover,
        .conversation-item.active {
            background: #f5f5f5;
        }
        
        .conversation-avatar {
            position: relative;
            margin-right: 1rem;
        }
        
        .conversation-avatar img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .unread-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .conversation-info {
            flex: 1;
        }
        
        .conversation-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
        }
        
        .last-message {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .message-time {
            color: #999;
            font-size: 0.8rem;
        }
        
        .messages-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .messages-list {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .message-item {
            display: flex;
            max-width: 70%;
        }
        
        .message-item.sent {
            align-self: flex-end;
        }
        
        .message-item.received {
            align-self: flex-start;
        }
        
        .message-content {
            padding: 0.75rem 1rem;
            border-radius: 18px;
            position: relative;
        }
        
        .message-item.sent .message-content {
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message-item.received .message-content {
            background: #f0f0f0;
            color: var(--dark-color);
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 0.7rem;
            margin-top: 0.25rem;
            opacity: 0.8;
        }
        
        .message-input {
            padding: 1rem;
            border-top: 1px solid #eee;
        }
        
        .input-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .input-group input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
        }
        
        .input-group button {
            padding: 0.75rem 1.5rem;
            border-radius: 20px;
        }
        
        .no-conversation,
        .no-messages {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            color: #999;
            text-align: center;
        }
        
        .no-conversation i,
        .no-messages i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .messages-container {
                flex-direction: column;
            }
            
            .conversations-sidebar {
                flex: 0 0 auto;
                height: auto;
                max-height: 300px;
            }
            
            .messages-area {
                height: 400px;
            }
            
            .message-item {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="messages">
        <div class="container">
            <h1 class="page-title">Messages</h1>
            
            <div class="messages-container">
                <!-- Conversations Sidebar -->
                <div class="conversations-sidebar">
                    <h2>Conversations</h2>
                    <button class="btn-primary btn-block" id="start-conversation-btn">
                        <i class="fas fa-plus"></i> New Conversation
                    </button>
                    
                    <div class="conversations-list">
                        <?php if (!empty($conversations)): ?>
                            <?php foreach ($conversations as $conversation): 
                                $profileImage = !empty($conversation['profile_image']) ? '../assets/images/profiles/' . $conversation['profile_image'] : '../assets/images/default-avatar.jpg';
                            ?>
                                <a href="messages.php?conversation_id=<?php echo $conversation['other_user_id']; ?>" 
                                   class="conversation-item <?php echo $conversation_id == $conversation['other_user_id'] ? 'active' : ''; ?>">
                                    <div class="conversation-avatar">
                                        <img src="<?php echo $profileImage; ?>" alt="<?php echo $conversation['first_name']; ?>" 
                                             onerror="this.src='../assets/images/default-avatar.jpg'">
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-info">
                                        <h4><?php echo $conversation['first_name'] . ' ' . $conversation['last_name']; ?></h4>
                                        <p class="last-message"><?php echo !empty($conversation['last_message']) ? substr($conversation['last_message'], 0, 30) . '...' : 'No messages yet'; ?></p>
                                        <?php if (!empty($conversation['last_message_time'])): ?>
                                            <small class="message-time"><?php echo date('M d, H:i', strtotime($conversation['last_message_time'])); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center" style="padding: 2rem; color: #999;">No conversations yet</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Messages Area -->
                <div class="messages-area">
                    <?php if ($conversation_id && $other_user): 
                        $otherUserImage = !empty($other_user['profile_image']) ? '../assets/images/profiles/' . $other_user['profile_image'] : '../assets/images/default-avatar.jpg';
                    ?>
                        <div class="messages-header">
                            <div class="user-info">
                                <img src="<?php echo $otherUserImage; ?>" alt="<?php echo $other_user['first_name']; ?>" 
                                     onerror="this.src='../assets/images/default-avatar.jpg'">
                                <div>
                                    <h3><?php echo $other_user['first_name'] . ' ' . $other_user['last_name']; ?></h3>
                                    <small><?php echo ucfirst($other_user['user_type']); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="messages-list" id="messages-list">
                            <?php if (!empty($messages)): ?>
                                <?php foreach ($messages as $message): 
                                    $senderImage = !empty($message['profile_image']) ? '../assets/images/profiles/' . $message['profile_image'] : '../assets/images/default-avatar.jpg';
                                ?>
                                    <div class="message-item <?php echo $message['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                        <div class="message-content">
                                            <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                            <span class="message-time"><?php echo date('M d, H:i', strtotime($message['created_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-messages">
                                    <i class="fas fa-comment-slash"></i>
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="message-input">
                            <form action="" method="POST">
                                <div class="input-group">
                                    <input type="text" name="message" placeholder="Type your message..." required>
                                    <button type="submit" name="send_message" class="btn-primary">
                                        <i class="fas fa-paper-plane"></i> Send
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="no-conversation">
                            <i class="fas fa-comments"></i>
                            <h3>Select a conversation</h3>
                            <p>Choose a conversation from the sidebar to start messaging</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- New Conversation Modal -->
    <div class="modal" id="new-conversation-modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Start New Conversation</h2>
            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label" for="receiver_id">Select User</label>
                    <select class="form-select" id="receiver_id" name="receiver_id" required>
                        <option value="">Select a user</option>
                        <?php if (!empty($new_users)): ?>
                            <?php foreach ($new_users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo $user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['user_type'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No users available</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="message">Message</label>
                    <textarea class="form-input" id="message" name="message" rows="4" required placeholder="Type your message..."></textarea>
                </div>
                <button type="submit" name="start_conversation" class="btn-primary btn-block">Start Conversation</button>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script>
        // Modal functionality
        const modal = document.getElementById('new-conversation-modal');
        const startBtn = document.getElementById('start-conversation-btn');
        const closeBtn = document.querySelector('.modal-close');
        
        startBtn.addEventListener('click', () => {
            modal.style.display = 'block';
        });
        
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Auto-scroll to bottom of messages
        const messagesList = document.getElementById('messages-list');
        if (messagesList) {
            messagesList.scrollTop = messagesList.scrollHeight;
        }
        
        // Auto-focus on message input
        const messageInput = document.querySelector('.message-input input');
        if (messageInput) {
            messageInput.focus();
        }
    </script>
</body>
</html>