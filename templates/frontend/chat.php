<header class="rp-header">
    <h1>💬 Team Chat</h1>
    <div class="rp-header-actions">
        <a href="<?php echo home_url('/medewerker-dashboard/'); ?>" class="rp-btn rp-btn-secondary">
            ← Terug
        </a>
    </div>
</header>
    
    <div class="rp-chat-wrapper">
        <div class="rp-chat-messages" id="chat-messages">
            <?php foreach ($messages as $msg): ?>
            <div class="rp-message <?php echo $msg->is_announcement ? 'rp-announcement' : ''; ?> <?php echo $msg->sender_id == get_current_user_id() ? 'rp-own' : ''; ?>" data-id="<?php echo $msg->id; ?>">
                <div class="rp-message-avatar">
                    <?php echo substr($msg->sender_name, 0, 1); ?>
                </div>
                <div class="rp-message-content">
                    <div class="rp-message-header">
                        <span class="rp-sender"><?php echo esc_html($msg->sender_name); ?></span>
                        <?php if ($msg->is_announcement): ?>
                        <span class="rp-badge">📢 Mededeling</span>
                        <?php endif; ?>
                        <span class="rp-time"><?php echo date('d-m-Y H:i', strtotime($msg->created_at)); ?></span>
                    </div>
                    <div class="rp-message-text"><?php echo nl2br(esc_html($msg->message)); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="rp-chat-input-area">
            <form id="chat-form" class="rp-chat-form">
                <textarea id="chat-message" rows="2" placeholder="Typ je bericht..." required></textarea>
                <button type="submit" class="rp-btn rp-btn-primary">
                    ➤
                </button>
            </form>
            <p class="rp-chat-hint">Druk op Enter om te versturen. Shift+Enter voor nieuwe regel.</p>
        </div>
    </div>

<script>
// Voorkom dubbele polling vanuit assets/js/frontend.js
window.rpChatInline = true;
let lastMessageId = <?php echo !empty($messages) ? end($messages)->id : 0; ?>;

// Auto-scroll to bottom on load
jQuery(document).ready(function() {
    scrollToBottom();
    
    // Poll for new messages every 10 seconds
    setInterval(pollMessages, 10000);
});

function scrollToBottom() {
    const container = document.getElementById('chat-messages');
    container.scrollTop = container.scrollHeight;
}

function pollMessages() {
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_get_chat_messages',
            nonce: rpAjax.nonce,
            last_id: lastMessageId
        },
        success: function(response) {
            if (response.success && response.data.messages.length > 0) {
                response.data.messages.forEach(function(msg) {
                    appendMessage(msg);
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });
                scrollToBottom();
            }
        }
    });
}

function appendMessage(msg) {
    const isOwn = msg.sender_id == <?php echo get_current_user_id(); ?>;
    const isAnnouncement = msg.is_announcement == 1;
    
    const html = `
        <div class="rp-message ${isAnnouncement ? 'rp-announcement' : ''} ${isOwn ? 'rp-own' : ''}" data-id="${msg.id}">
            <div class="rp-message-avatar">${msg.sender_name.charAt(0)}</div>
            <div class="rp-message-content">
                <div class="rp-message-header">
                    <span class="rp-sender">${escapeHtml(msg.sender_name)}</span>
                    ${isAnnouncement ? '<span class="rp-badge">📢 Mededeling</span>' : ''}
                    <span class="rp-time">${formatDate(msg.created_at)}</span>
                </div>
                <div class="rp-message-text">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</div>
            </div>
        </div>
    `;
    
    jQuery('#chat-messages').append(html);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.getDate().toString().padStart(2, '0') + '-' + 
           (date.getMonth() + 1).toString().padStart(2, '0') + '-' + 
           date.getFullYear() + ' ' +
           date.getHours().toString().padStart(2, '0') + ':' + 
           date.getMinutes().toString().padStart(2, '0');
}

jQuery('#chat-form').on('submit', function(e) {
    e.preventDefault();
    
    const message = jQuery('#chat-message').val().trim();
    if (!message) return;
    
    // Clear input immediately for better UX
    jQuery('#chat-message').val('');
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_send_chat_message',
            nonce: rpAjax.nonce,
            message: message
        },
        success: function(response) {
            if (response.success) {
                // Message sent, will appear on next poll
                pollMessages();
            } else {
                alert('Bericht kon niet worden verstuurd.');
            }
        },
        error: function() {
            alert('Er is een fout opgetreden.');
        }
    });
});

// Allow Enter to submit, Shift+Enter for new line
jQuery('#chat-message').on('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        jQuery('#chat-form').submit();
    }
});
</script>

<style>
.rp-container { max-width: 800px; margin: 0 auto; padding: 20px; }
.rp-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
.rp-btn { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; }
.rp-btn-primary { background: #4F46E5; color: #fff; }
.rp-btn-secondary { background: #f3f4f6; color: #374151; }
.rp-chat-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); height: 600px; display: flex; flex-direction: column; }
.rp-chat-messages { flex: 1; overflow-y: auto; padding: 20px; }
.rp-message { display: flex; gap: 12px; margin-bottom: 15px; }
.rp-message.rp-own { flex-direction: row-reverse; }
.rp-message.rp-own .rp-message-content { background: #4F46E5; color: #fff; }
.rp-message.rp-own .rp-sender { color: #fff; }
.rp-message.rp-announcement { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; }
.rp-message-avatar { width: 40px; height: 40px; border-radius: 50%; background: #4F46E5; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0; }
.rp-message-content { background: #f3f4f6; border-radius: 12px; padding: 12px 16px; max-width: 70%; }
.rp-message-header { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.rp-sender { font-weight: 600; color: #4F46E5; font-size: 13px; }
.rp-badge { background: #f59e0b; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
.rp-time { color: #9ca3af; font-size: 11px; margin-left: auto; }
.rp-message-text { line-height: 1.5; }
.rp-chat-input-area { padding: 15px 20px; border-top: 1px solid #e5e7eb; }
.rp-chat-form { display: flex; gap: 10px; }
.rp-chat-form textarea { flex: 1; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; resize: none; font-size: 15px; }
.rp-chat-form button { padding: 12px 20px; }
.rp-chat-hint { margin: 8px 0 0; font-size: 12px; color: #9ca3af; }
@media (max-width: 600px) {
    .rp-chat-wrapper { height: calc(100vh - 150px); }
    .rp-message-content { max-width: 80%; }
}
</style>
