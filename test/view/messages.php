<?php
require_once '../utils/auth_middleware.php';
$user = checkAuth();

session_start();
require_once '../controllers/MessageController.php';

$controller = new MessageController();

// Get pet_id and owner_id from URL if starting new conversation
$petId = $_GET['pet_id'] ?? null;
$ownerId = $_GET['owner_id'] ?? null;

if ($petId && $ownerId) {
    $result = $controller->startConversation($petId);
    if (isset($result['error'])) {
        echo "Error: " . $result['error'];
        exit;
    }
    $pet = $result['pet'];
}

// Get all conversations for the current user
$conversationsResult = $controller->getConversations();
$conversations = $conversationsResult['conversations'] ?? [];

// Get messages for selected conversation
$selectedConversationId = $_GET['conversation_id'] ?? null;
$messages = [];
if ($selectedConversationId) {
    $messagesResult = $controller->getMessages($selectedConversationId);
    $messages = $messagesResult['messages'] ?? [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Pow</title>
    <link rel="stylesheet" href="../stiluri/messages.css">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
</head>
<body>
    <header class="navbar">
        <a href="lista-animale.php" class="back-btn">Back</a>

        <a href="homepage.php" class="logo">Pow</a>

        <?php if ($ownerId == $user->id): ?>
            <a href="profile.php" class="profile-icon">
                <img src="../stiluri/imagini/profileicon.png" alt="Profile" />
            </a>
        <?php else: ?>
            <a href="formular.php?pet_id=<?php echo $petId; ?>" class="adopt-nav-btn">Adopt</a>
        <?php endif; ?>
    </header>

    <div class="messages-container">
        <div class="conversations-list">
            <h2>Conversations</h2>
            <?php if (empty($conversations) && !$petId): ?>
                <p class="no-conversations">No conversations yet.</p>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <a href="?conversation_id=<?php echo $conv['CONVERSATION_ID']; ?>&pet_id=<?php echo $conv['PET_ID']; ?>&owner_id=<?php echo $conv['OTHER_USER_ID']; ?>" 
                       class="conversation-item <?php echo $selectedConversationId == $conv['CONVERSATION_ID'] ? 'active' : ''; ?>">
                        <div class="conversation-info">
                            <div class="user-name">
                                <?php echo htmlspecialchars($conv['OTHER_USER_NAME'] . ' ' . $conv['OTHER_USER_SURNAME']); ?>
                            </div>
                            <div class="pet-name">
                                About: <?php echo htmlspecialchars($conv['PET_NAME']); ?>
                            </div>
                            <div class="last-message">
                                <?php echo htmlspecialchars(substr($conv['LAST_MESSAGE'], 0, 50)) . (strlen($conv['LAST_MESSAGE']) > 50 ? '...' : ''); ?>
                            </div>
                        </div>
                        <?php if ($conv['UNREAD_COUNT'] > 0): ?>
                            <div class="unread-badge"><?php echo $conv['UNREAD_COUNT']; ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="chat-container">
            <?php if ($selectedConversationId || ($petId && $ownerId)): ?>
                <div class="chat-messages" id="chat-messages">
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['SENDER_ID'] == $user->id ? 'sent' : 'received'; ?>">
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($message['MESSAGE_TEXT'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($petId && $ownerId): ?>
                        <div class="start-conversation">
                            <p>Start a conversation about <?php echo htmlspecialchars($pet['NAME']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <form class="message-form" id="message-form" method="POST">
                    <input type="hidden" name="receiver_id" value="<?php 
                        if ($ownerId) {
                            echo $ownerId;
                        } elseif (!empty($messages)) {
                            echo $messages[0]['SENDER_ID'] == $user->id ? $messages[0]['RECEIVER_ID'] : $messages[0]['SENDER_ID'];
                        }
                    ?>">
                    <input type="hidden" name="pet_id" value="<?php 
                        if ($petId) {
                            echo $petId;
                        } elseif (!empty($messages)) {
                            echo $messages[0]['PET_ID'];
                        }
                    ?>">
                    <textarea name="message" placeholder="Type your message..." required></textarea>
                    <button type="submit">Send</button>
                </form>
            <?php else: ?>
                <div class="no-chat-selected">
                    <p>Select a conversation or start a new one from a pet's page.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Call on page load
        scrollToBottom();

        // Handle form submission
        document.getElementById('message-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('../utils/ajax_send_message.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    // Clear the message input
                    form.querySelector('textarea').value = '';
                    
                    // Reload the page to show the new message
                    window.location.reload();
                } else {
                    alert(result.error || 'Failed to send message');
                }
            } catch (error) {
                alert('Failed to send message');
            }
        });
    </script>
</body>
</html> 