/**
 * LPM Notification Center Handler
 * Polling interval: 30 seconds
 */
(function() {
    'use strict';

    const ntWrap = document.getElementById('ntWrap');
    const ntBell = document.getElementById('ntBell');
    const ntBadge = document.querySelector('.nt-badge');
    const ntList = document.querySelector('.nt-drop');
    const soundToggle = document.getElementById('ntSoundToggle');
    
    // Base64 short "ding" sound (non-intrusive)
    const notifSound = new Audio("data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU"); // Placeholder, akan kita ganti dengan sound yang valid di bawah
    
    // Gunakan sound "pop" yang sangat pendek dan ringan
    const popSound = new Audio("data:audio/mp3;base64,SUQzBAAAAAABAFRYWFgAAAASAAADbWFqb3JfYnJhbmQAbXA0MgBUWFhYAAAAEQAAA21pbm9yX3ZlcnNpb24AMABUWFhYAAAAHAAAA2NvbXBhdGlibGVfYnJhbmRzAGlzb21tcDQyAFRTU0UAAAAPAAADTGF2ZjU4LjI5LjEwMAAAAAAAAAAAAAAA//uQZAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAASW5mbwAAAA8AAAAEAAABIADAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDV1dXV1dXV1dXV1dXV1dXV1dXV1dXV1dXV6urq6urq6urq6urq6urq6urq6urq6urq6v////////////////////////////////8AAAAATGF2YzU4LjU0AAAAAAAAAAAAAAAAJAAAAAAAAAAAASDs90hvAAAAAAAAAAAAAAAAAAAA//sQZAAP8AAAaQAAAAgAAA0gAAABAAABpAAAACAAADSAAAAETEFNRTMuMTAwVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVQ==");

    let currentCount = parseInt(ntBadge ? ntBadge.textContent.replace('+', '') : 0);
    let isSoundEnabled = localStorage.getItem('lpm_notif_sound') === '1';

    // Initialize sound toggle state
    if (soundToggle) {
        soundToggle.textContent = isSoundEnabled ? '🔊' : '🔇';
        soundToggle.title = isSoundEnabled ? 'Matikan Suara Notifikasi' : 'Nyalakan Suara Notifikasi';
    }

    // Toggle Dropdown
    if (ntBell) {
        ntBell.addEventListener('click', (e) => {
            e.stopPropagation();
            ntWrap.classList.toggle('open');
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (ntWrap && !ntWrap.contains(e.target)) {
            ntWrap.classList.remove('open');
        }
    });

    // Toggle Sound
    if (soundToggle) {
        soundToggle.addEventListener('click', (e) => {
            e.preventDefault();
            isSoundEnabled = !isSoundEnabled;
            localStorage.setItem('lpm_notif_sound', isSoundEnabled ? '1' : '0');
            soundToggle.textContent = isSoundEnabled ? '🔊' : '🔇';
            soundToggle.title = isSoundEnabled ? 'Matikan Suara Notifikasi' : 'Nyalakan Suara Notifikasi';
        });
    }

    // Fetch Notifications
    async function fetchNotifications() {
        try {
            const response = await fetch('/sim/api/get-notifications.php');
            const data = await response.json();

            if (data.success) {
                // Check if new notifications arrived
                if (data.count > currentCount && currentCount > 0 && isSoundEnabled) {
                    popSound.play().catch(() => {}); // Ignore autoplay restrictions
                }
                
                currentCount = data.count;

                // Update Badge
                if (ntBadge) {
                    if (currentCount > 0) {
                        ntBadge.textContent = currentCount > 9 ? '9+' : currentCount;
                        ntBadge.style.display = 'grid';
                    } else {
                        ntBadge.style.display = 'none';
                    }
                }

                // Update List HTML
                if (ntList) {
                    // Preserve the header
                    const header = ntList.querySelector('.nt-head');
                    ntList.innerHTML = '';
                    if (header) ntList.appendChild(header);
                    
                    // Append new list
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;
                    while (tempDiv.firstChild) {
                        ntList.appendChild(tempDiv.firstChild);
                    }

                    // Re-attach event listeners to new "Mark all as read" link
                    const markAllLink = ntList.querySelector('.nt-head a');
                    if (markAllLink) {
                        markAllLink.addEventListener('click', async (e) => {
                            e.preventDefault();
                            await markAllAsRead();
                        });
                    }
                }
            }
        } catch (error) {
            console.error('Notification fetch failed:', error);
        }
    }

    // Mark single as read (via click)
    document.addEventListener('click', async (e) => {
        const item = e.target.closest('.nt-item[data-id]');
        if (item && item.classList.contains('unread')) {
            item.classList.remove('unread');
            try {
                const formData = new FormData();
                formData.append('id', item.dataset.id);
                await fetch('/sim/api/mark-as-read.php', { method: 'POST', body: formData });
                
                // Decrement count locally for instant feedback
                if (currentCount > 0) {
                    currentCount--;
                    if (ntBadge) {
                        ntBadge.textContent = currentCount > 9 ? '9+' : currentCount;
                        if (currentCount === 0) ntBadge.style.display = 'none';
                    }
                }
            } catch (err) {
                console.error('Mark as read failed:', err);
            }
        }
    });

    // Mark all as read
    async function markAllAsRead() {
        try {
            const response = await fetch('/sim/api/mark-all-read.php', { method: 'POST' });
            const data = await response.json();
            if (data.success) {
                currentCount = 0;
                if (ntBadge) ntBadge.style.display = 'none';
                
                // Clear list visually
                const items = ntList.querySelectorAll('.nt-item');
                items.forEach(item => item.classList.remove('unread'));
            }
        } catch (err) {
            console.error('Mark all as read failed:', err);
        }
    }

    // Initial fetch
    fetchNotifications();

    // Polling every 30 seconds
    setInterval(fetchNotifications, 30000);

})();