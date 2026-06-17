/**
 * ============================================
 * SESSION KEEP-ALIVE UTILITY
 * ============================================
 * 
 * Prevents session expiry during long data entry sessions
 * by periodically pinging the server when user is active.
 * 
 * Usage: Add data-keep-session-alive attribute to any page element
 * Example: <div data-keep-session-alive>...</div>
 */

const SessionKeepAlive = {
    config: {
        pingInterval: 5 * 60 * 1000, // 5 minutes
        activityTimeout: 2 * 60 * 1000, // 2 minutes - consider inactive if no activity
        pingUrl: '/session/ping',
    },

    state: {
        lastActivityTime: Date.now(),
        pingTimer: null,
        isActive: false,
    },

    /**
     * Track user activity
     */
    trackActivity() {
        this.state.lastActivityTime = Date.now();
    },

    /**
     * Check if user has been active recently
     */
    isUserActive() {
        const timeSinceActivity = Date.now() - this.state.lastActivityTime;
        return timeSinceActivity < this.config.activityTimeout;
    },

    /**
     * Ping server to keep session alive
     */
    async pingServer() {
        // Only ping if user has been active
        if (!this.isUserActive()) {
            console.log('[SessionKeepAlive] User inactive, skipping ping');
            return false;
        }

        try {
            const response = await fetch(this.config.pingUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                console.log('[SessionKeepAlive] Session refreshed successfully');
                return true;
            } else {
                console.warn('[SessionKeepAlive] Ping failed:', response.status);
                return false;
            }
        } catch (error) {
            console.error('[SessionKeepAlive] Ping error:', error);
            return false;
        }
    },

    /**
     * Start the keep-alive mechanism
     */
    start() {
        if (this.state.isActive) {
            console.warn('[SessionKeepAlive] Already active');
            return;
        }

        console.log('[SessionKeepAlive] Starting session keep-alive');
        this.state.isActive = true;
        this.state.lastActivityTime = Date.now();

        // Set up activity listeners
        const activityEvents = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        activityEvents.forEach(event => {
            document.addEventListener(event, () => this.trackActivity(), { passive: true });
        });

        // Start periodic pinging
        this.state.pingTimer = setInterval(() => {
            this.pingServer();
        }, this.config.pingInterval);

        // Do an initial ping
        this.pingServer();
    },

    /**
     * Stop the keep-alive mechanism
     */
    stop() {
        if (!this.state.isActive) {
            return;
        }

        console.log('[SessionKeepAlive] Stopping session keep-alive');

        if (this.state.pingTimer) {
            clearInterval(this.state.pingTimer);
            this.state.pingTimer = null;
        }

        this.state.isActive = false;
    },

    /**
     * Initialize keep-alive if element with data attribute exists
     */
    init() {
        if (document.querySelector('[data-keep-session-alive]')) {
            // Wait for DOM to be fully loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.start());
            } else {
                this.start();
            }
        }
    }
};

// Auto-initialize
SessionKeepAlive.init();

// Export for manual control if needed
window.SessionKeepAlive = SessionKeepAlive;
