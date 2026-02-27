/**
 * Rooster Planner Frontend JavaScript
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initNotifications();
        initChatPolling();
        initServiceWorker();
    });

    /**
     * Notification System
     */
    function initNotifications() {
        const $bell = $('#rp-notification-bell');
        const $panel = $('#rp-notification-panel');
        
        if (!$bell.length) return;
        
        // Show bell only for logged in users on frontend
        $bell.show();
        
        // Toggle panel
        $bell.on('click', function(e) {
            e.stopPropagation();
            $panel.toggle();
            
            if ($panel.is(':visible')) {
                loadNotifications();
            }
        });
        
        // Close panel when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#rp-notification-panel').length && !$(e.target).is('#rp-notification-bell')) {
                $panel.hide();
            }
        });
    }

    function loadNotifications() {
        $.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'rp_get_notifications',
                nonce: rpAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderNotifications(response.data.notifications);
                    updateBadge(response.data.unread_count);
                }
            }
        });
    }

    function renderNotifications(notifications) {
        const $panel = $('#rp-notification-panel');
        
        if (!notifications.length) {
            $panel.html('<div class="rp-no-notifications">Geen notificaties</div>');
            return;
        }
        
        let html = '<div class="rp-notification-list">';
        notifications.forEach(function(n) {
            const isUnread = !n.is_read ? 'rp-unread' : '';
            html += `
                <div class="rp-notification-item ${isUnread}" data-id="${n.id}">
                    <div class="rp-notification-title">${escapeHtml(n.title)}</div>
                    <div class="rp-notification-message">${escapeHtml(n.message)}</div>
                    <div class="rp-notification-time">${formatDate(n.created_at)}</div>
                </div>
            `;
        });
        html += '</div>';
        
        $panel.html(html);
        
        // Mark as read on click
        $panel.find('.rp-notification-item').on('click', function() {
            const id = $(this).data('id');
            markAsRead(id);
            $(this).removeClass('rp-unread');
        });
    }

    function markAsRead(id) {
        $.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'rp_mark_notification_read',
                nonce: rpAjax.nonce,
                notification_id: id
            }
        });
    }

    function updateBadge(count) {
        const $badge = $('.rp-notification-badge');
        if (count > 0) {
            if ($badge.length) {
                $badge.text(count);
            } else {
                $('#rp-notification-bell').append(`<span class="rp-notification-badge">${count}</span>`);
            }
        } else {
            $badge.remove();
        }
    }

    /**
     * Service worker + push subscription
     */
    function initServiceWorker() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

        navigator.serviceWorker.register(rpAjax.pluginUrl + 'assets/js/sw.js').then(function(reg) {
            // Request permission then subscribe
            if (Notification.permission === 'granted') {
                subscribePush(reg);
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(function(permission) {
                    if (permission === 'granted') {
                        subscribePush(reg);
                    }
                });
            }
        });
    }

    function subscribePush(registration) {
        fetch(rpAjax.restUrl + 'roosterplanner/v1/push/public-key')
            .then(res => res.json())
            .then(data => {
                if (!data.publicKey) return;
                const convertedKey = urlBase64ToUint8Array(data.publicKey);
                return registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedKey
                });
            })
            .then(subscription => {
                if (!subscription) return;
                const keys = subscription.toJSON().keys;
                $.post(rpAjax.restUrl + 'roosterplanner/v1/push/subscribe', {
                    endpoint: subscription.endpoint,
                    p256dh: keys.p256dh,
                    auth: keys.auth
                });
            })
            .catch(function(err) {
                console.warn('Push subscribe failed', err);
            });
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Chat Polling
     */
    function initChatPolling() {
        // Only on chat page
        if (!$('.rp-chat-wrapper').length) return;
        
        let lastMessageId = $('.rp-message:last').data('id') || 0;
        
        // Poll every 10 seconds
        setInterval(function() {
            $.ajax({
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
        }, 10000);
    }

    function appendMessage(msg) {
        const isOwn = msg.sender_id === parseInt(rpAjax.currentUserId);
        const isAnnouncement = msg.is_announcement == 1;
        
        const html = `
            <div class="rp-message ${isAnnouncement ? 'rp-announcement' : ''} ${isOwn ? 'rp-own' : ''}">
                <div class="rp-message-avatar">${msg.sender_name.charAt(0)}</div>
                <div class="rp-message-content">
                    <div class="rp-message-header">
                        <span class="rp-sender">${escapeHtml(msg.sender_name)}</span>
                        ${isAnnouncement ? '<span class="rp-badge">📢</span>' : ''}
                        <span class="rp-time">${formatDate(msg.created_at)}</span>
                    </div>
                    <div class="rp-message-text">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</div>
                </div>
            </div>
        `;
        
        $('#chat-messages').append(html);
    }

    function scrollToBottom() {
        const container = document.getElementById('chat-messages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    /**
     * Utility Functions
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        // Less than 1 hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return minutes < 1 ? 'Zojuist' : minutes + ' min geleden';
        }
        
        // Less than 24 hours
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return hours + ' uur geleden';
        }
        
        // Format as date
        return date.getDate().toString().padStart(2, '0') + '-' + 
               (date.getMonth() + 1).toString().padStart(2, '0') + ' ' +
               date.getHours().toString().padStart(2, '0') + ':' + 
               date.getMinutes().toString().padStart(2, '0');
    }

    /**
     * Service Worker for Push Notifications
     */
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register(rpAjax.pluginUrl + 'assets/js/sw.js')
            .then(function(registration) {
                console.log('Service Worker registered:', registration);
            })
            .catch(function(error) {
                console.log('Service Worker registration failed:', error);
            });
    }

})(jQuery);
