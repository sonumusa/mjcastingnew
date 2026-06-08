<?php if (!isset($hideSidebar)): ?>
        </main>
    </div>
    
    <script>
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
        });
    </script>
    <?= $extraJs ?? '' ?>
</body>
</html>
<?php endif; ?>