<header class="rp-header">
    <h1>🔔 Alle Berichten</h1>
    <div class="rp-header-actions">
        <a href="<?php echo home_url('/medewerker-dashboard/'); ?>" class="rp-btn rp-btn-secondary">
            ← Terug
        </a>
        <button type="button" class="rp-btn rp-btn-primary" onclick="markAllRead()">Markeer alles als gelezen</button>
    </div>
</header>

<div class="rp-section">
    <div class="rp-notifications-list" id="notifications-list">
        <?php if (empty($notifications)): ?>
        <p class="rp-empty">Je hebt nog geen berichten.</p>
        <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
        <div class="rp-notification-item <?php echo $notif->is_read ? 'rp-read' : 'rp-unread'; ?>" data-id="<?php echo $notif->id; ?>">
            <div class="rp-notification-icon">
                <?php
                $icons = [
                    'swap_request' => '🔄',
                    'swap_response' => '✓',
                    'announcement' => '📢',
                    'admin_notice' => '👤',
                    'replacement_needed' => '🚨',
                    'timeoff' => '📅',
                    'schedule' => '📋'
                ];
                echo $icons[$notif->type] ?? '📌';
                ?>
            </div>
            <div class="rp-notification-content">
                <h4><?php echo esc_html($notif->title); ?></h4>
                <p><?php echo esc_html($notif->message); ?></p>
                <span class="rp-notification-time"><?php echo date('d-m-Y H:i', strtotime($notif->created_at)); ?></span>
            </div>
            <?php if (!$notif->is_read): ?>
            <button type="button" class="rp-btn rp-btn-small" onclick="markAsRead(<?php echo $notif->id; ?>, this)">
                Markeer als gelezen
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function markAsRead(notificationId, btn) {
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_mark_notification_read',
            nonce: rpAjax.nonce,
            notification_id: notificationId
        },
        success: function(response) {
            if (response.success) {
                const item = jQuery(btn).closest('.rp-notification-item');
                item.removeClass('rp-unread').addClass('rp-read');
                jQuery(btn).remove();
            }
        }
    });
}

function markAllRead() {
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_mark_all_notifications_read',
            nonce: rpAjax.nonce
        },
        success: function(response) {
            if (response.success) {
                jQuery('.rp-notification-item').removeClass('rp-unread').addClass('rp-read');
                jQuery('.rp-notification-item .rp-btn-small').remove();
            }
        }
    });
}
</script>

<style>
.rp-container { max-width: 800px; margin: 0 auto; padding: 20px; }
.rp-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
.rp-btn { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; }
.rp-btn-secondary { background: #f3f4f6; color: #374151; }
.rp-btn-small { padding: 6px 12px; font-size: 13px; background: #4F46E5; color: #fff; }
.rp-section { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.rp-notifications-list { display: flex; flex-direction: column; gap: 10px; }
.rp-notification-item { display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 8px; border-left: 4px solid #e5e7eb; }
.rp-notification-item.rp-unread { background: #f0fdf4; border-left-color: #10B981; }
.rp-notification-item.rp-read { background: #f9fafb; border-left-color: #9ca3af; }
.rp-notification-icon { font-size: 24px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 50%; }
.rp-notification-content { flex: 1; }
.rp-notification-content h4 { margin: 0 0 5px; font-size: 16px; color: #1f2937; }
.rp-notification-content p { margin: 0 0 5px; color: #6b7280; font-size: 14px; }
.rp-notification-time { font-size: 12px; color: #9ca3af; }
.rp-empty { color: #9ca3af; font-style: italic; text-align: center; padding: 40px 0; }
@media (max-width: 600px) {
    .rp-notification-item { flex-wrap: wrap; }
    .rp-notification-content { width: 100%; }
}
</style>
