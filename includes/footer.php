<?php if (!isset($hideSidebar)): ?>
        </main>
    </div>
    
    <script>
        function applyMjTheme(theme, persist = true) {
            theme = theme === 'light' ? 'light' : 'dark';
            document.documentElement.classList.add('theme-transition');
            document.documentElement.setAttribute('data-theme', theme);
            if (persist) {
                try { localStorage.setItem('mj_theme', theme); } catch(e) {}
            }
            const icon = document.getElementById('theme-toggle-icon');
            const text = document.getElementById('theme-toggle-text');
            if (icon) icon.className = theme === 'light' ? 'bi bi-sun' : 'bi bi-moon-stars';
            if (text) text.textContent = theme === 'light' ? 'Light' : 'Dark';
            window.setTimeout(() => document.documentElement.classList.remove('theme-transition'), 260);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const mobileToggle = document.getElementById('mobile-toggle');
            const sidebar = document.getElementById('sidebar');
            
            if (mobileToggle && sidebar) {
                mobileToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('open');
                });
            }
            
            // Update online/offline status
            const updateStatus = (state) => {
                const dot = document.getElementById('online-dot');
                const text = document.getElementById('online-text');
                const bar = document.getElementById('offline-bar');
                
                if (state === 'online') {
                    if (dot) dot.classList.remove('offline');
                    if (text) text.innerText = 'Online';
                    if (bar) bar.style.top = '-60px';
                } else {
                    if (dot) dot.classList.add('offline');
                    if (text) text.innerText = 'Offline';
                    if (bar) bar.style.top = '0px';
                }
            };
            
            window.addEventListener('online', () => updateStatus('online'));
            window.addEventListener('offline', () => updateStatus('offline'));
            if (!navigator.onLine) updateStatus('offline');

            let currentTheme = 'dark';
            try { currentTheme = localStorage.getItem('mj_theme') || document.documentElement.getAttribute('data-theme') || 'dark'; } catch(e) {}
            applyMjTheme(currentTheme, false);
            window.toggleMjTheme = function() {
                const active = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
                applyMjTheme(active, true);
            };
        });
    </script>
    <?= $extraJs ?? '' ?>
</body>
</html>
<?php endif; ?>