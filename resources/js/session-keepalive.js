const SessionKeepAlive = {
    config: {
        pingInterval: 5 * 60 * 1000,
        activityTimeout: 2 * 60 * 1000,
        pingUrl: '/session/ping',
    },

    state: {
        lastActivityTime: Date.now(),
        pingTimer: null,
        isActive: false,
    },

    trackActivity() {
        this.state.lastActivityTime = Date.now();
    },

    isUserActive() {
        return (Date.now() - this.state.lastActivityTime) < this.config.activityTimeout;
    },

    async pingServer() {
        if (!this.isUserActive()) return false;

        try {
            const response = await fetch(this.config.pingUrl, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            return response.ok;
        } catch (error) {
            return false;
        }
    },

    start() {
        if (this.state.isActive) return;

        this.state.isActive = true;
        this.state.lastActivityTime = Date.now();

        ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => this.trackActivity(), { passive: true });
        });

        this.state.pingTimer = setInterval(() => this.pingServer(), this.config.pingInterval);
        this.pingServer();
    },

    stop() {
        if (!this.state.isActive) return;

        if (this.state.pingTimer) {
            clearInterval(this.state.pingTimer);
            this.state.pingTimer = null;
        }
        this.state.isActive = false;
    },

    init() {
        if (!document.querySelector('[data-keep-session-alive]')) return;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.start());
        } else {
            this.start();
        }
    }
};

SessionKeepAlive.init();
window.SessionKeepAlive = SessionKeepAlive;
