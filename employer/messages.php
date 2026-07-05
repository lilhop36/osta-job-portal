<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Message;

require_role('employer', '../login.php');

$userId = (int) $_SESSION['user_id'];
$messageModel = new Message();

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
        header('Location: messages.php');
        exit;
    }

    $receiverId = (int) $_POST['receiver_id'];
    $message = sanitize($_POST['message'] ?? '');

    if ($receiverId > 0 && !empty($message)) {
        $messageModel->send($userId, $receiverId, $message);
    }

    header('Location: messages.php?chat=' . $receiverId);
    exit;
}

$chatWith = isset($_GET['chat']) ? (int) $_GET['chat'] : 0;
$conversation = [];
$inbox = $messageModel->getInbox($userId);

// If chatting with someone, load conversation
if ($chatWith > 0) {
    $conversation = $messageModel->getConversation($userId, $chatWith);
    $messageModel->markRead($chatWith, $userId);

    // Get other user info
    $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$chatWith]);
    $otherUser = $stmt->fetch();
} else {
    $otherUser = null;
}

$pageTitle = 'Messages';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
    .chat-container { height: calc(100vh - 200px); min-height: 500px; }
    .chat-sidebar { border-right: 1px solid #e9ecef; overflow-y: auto; }
    .chat-main { display: flex; flex-direction: column; }
    .chat-messages { flex: 1; overflow-y: auto; padding: 1rem; }
    .chat-input { padding: 1rem; border-top: 1px solid #e9ecef; }
    .message-sent { background: var(--osta-green); color: white; border-radius: 16px 16px 4px 16px; max-width: 70%; }
    .message-received { background: #f1f3f5; border-radius: 16px 16px 16px 4px; max-width: 70%; }
    .conversation-item:hover { background: #f8f9fa; }
    .conversation-item.active { background: #e8f5e9; }
</style>

<div class="container-fluid py-3">
    <div class="row">
        <!-- Conversation List -->
        <div class="col-md-4 col-lg-3 chat-sidebar p-0">
            <div class="p-3 border-bottom">
                <h5 class="fw-bold mb-0"><i class="fas fa-comments me-2" style="color: var(--osta-green);"></i>Messages</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($inbox)): ?>
                    <div class="p-3 text-center text-muted">No conversations yet.</div>
                <?php else: ?>
                    <?php foreach ($inbox as $conv): ?>
                    <a href="messages.php?chat=<?php echo $conv['sender_id'] == $userId ? $conv['receiver_id'] : $conv['sender_id']; ?>" 
                       class="conversation-item list-group-item list-group-item-action <?php echo $chatWith == ($conv['sender_id'] == $userId ? $conv['receiver_id'] : $conv['sender_id']) ? 'active' : ''; ?>">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:40px;height:40px;background:var(--osta-green);color:white;font-weight:700;font-size:0.9rem;">
                                <?php echo strtoupper(substr($conv['other_name'] ?? '?', 0, 1)); ?>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-semibold small"><?php echo htmlspecialchars($conv['other_name'] ?? 'Unknown'); ?></div>
                                <div class="text-muted small text-truncate"><?php echo htmlspecialchars(substr($conv['message'] ?? '', 0, 40)); ?></div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="col-md-8 col-lg-9 chat-main p-0">
            <?php if ($chatWith > 0 && $otherUser): ?>
                <div class="p-3 border-bottom bg-white">
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($otherUser['full_name'] ?? $otherUser['username']); ?></h6>
                    <small class="text-muted"><?php echo ucfirst($otherUser['role']); ?></small>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <?php foreach ($conversation as $msg): ?>
                    <div class="mb-3 d-flex <?php echo $msg['sender_id'] == $userId ? 'justify-content-end' : 'justify-content-start'; ?>">
                        <div class="<?php echo $msg['sender_id'] == $userId ? 'message-sent' : 'message-received'; ?> p-3">
                            <div class="small"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                            <div class="small mt-1 <?php echo $msg['sender_id'] == $userId ? 'text-white-50' : 'text-muted'; ?>" style="font-size: 0.7rem;">
                                <?php echo date('M d, h:i A', strtotime($msg['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="chat-input bg-white">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="send">
                        <input type="hidden" name="receiver_id" value="<?php echo $chatWith; ?>">
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type a message..." required autocomplete="off">
                            <button type="submit" class="btn" style="background: var(--osta-green); color: white;"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div class="text-center">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Select a conversation</h5>
                        <p class="text-muted">Choose a conversation from the sidebar to start chatting.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const chat = document.getElementById('chatMessages');
if (chat) chat.scrollTop = chat.scrollHeight;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
