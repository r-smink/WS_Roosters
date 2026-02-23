<div class="wrap rp-admin-wrap">
    <h1>Chat & Berichten</h1>
    
    <div class="rp-chat-admin">
        <div class="rp-chat-container">
            <div class="rp-chat-messages" id="chat-messages">
                <?php foreach (array_reverse($messages) as $msg): ?>
                <div class="rp-chat-message <?php echo $msg->is_announcement ? 'rp-announcement' : ''; ?>" data-id="<?php echo $msg->id; ?>">
                    <div class="rp-message-header">
                        <span class="rp-sender"><?php echo esc_html($msg->sender_name); ?></span>
                        <?php if ($msg->is_announcement): ?>
                        <span class="rp-badge">📢 Mededeling</span>
                        <?php endif; ?>
                        <span class="rp-time"><?php echo date('d-m-Y H:i', strtotime($msg->created_at)); ?></span>
                    </div>
                    <div class="rp-message-content"><?php echo nl2br(esc_html($msg->message)); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="rp-chat-input-area">
                <form id="chat-form">
                    <div class="rp-input-row">
                        <label style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" id="is_announcement" name="is_announcement">
                            <span>Verstuur als mededeling aan alle medewerkers</span>
                        </label>
                    </div>
                    <div class="rp-input-row">
                        <textarea id="chat-message" rows="3" placeholder="Typ je bericht..."></textarea>
                        <button type="submit" class="button button-primary">Versturen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
jQuery('#chat-form').on('submit', function(e) {
    e.preventDefault();
    
    const message = document.getElementById('chat-message').value.trim();
    if (!message) return;
    
    const isAnnouncement = document.getElementById('is_announcement').checked;
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_send_chat_message',
            nonce: rpAjax.nonce,
            message: message,
            is_announcement: isAnnouncement
        },
        success: function(response) {
            if (response.success) {
                document.getElementById('chat-message').value = '';
                document.getElementById('is_announcement').checked = false;
                location.reload();
            }
        }
    });
});

// Auto-scroll to bottom
jQuery(document).ready(function() {
    const container = document.getElementById('chat-messages');
    container.scrollTop = container.scrollHeight;
});
</script>

<style>
.rp-admin-wrap { max-width: 1000px; }
.rp-chat-container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: 600px;
    display: flex;
    flex-direction: column;
}
.rp-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}
.rp-chat-message {
    margin-bottom: 15px;
    padding: 12px;
    background: #f3f4f6;
    border-radius: 8px;
    border-left: 3px solid #4F46E5;
}
.rp-announcement {
    background: #fef3c7;
    border-left-color: #f59e0b;
}
.rp-message-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.rp-sender {
    font-weight: 600;
    color: #4F46E5;
}
.rp-badge {
    background: #f59e0b;
    color: #fff;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}
.rp-time {
    color: #9ca3af;
    font-size: 12px;
    margin-left: auto;
}
.rp-message-content {
    color: #374151;
    line-height: 1.5;
}
.rp-chat-input-area {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
    border-radius: 0 0 8px 8px;
}
.rp-input-row {
    margin-bottom: 10px;
}
.rp-input-row:last-child {
    margin-bottom: 0;
    display: flex;
    gap: 10px;
}
#chat-message {
    flex: 1;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    resize: vertical;
}
</style>
