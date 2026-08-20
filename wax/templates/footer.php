</div><!-- .container -->
    <footer style="text-align:center;padding:20px;color:var(--text-muted);font-size:0.8rem;border-top:1px solid var(--border);margin-top:20px;">
        M.J Casting — Wax & 3D Design Module &bull; <?= date('Y') ?>
    </footer>
<script>
(function(){
    function applyWaxTheme(theme, persist) {
        theme = theme === 'light' ? 'light' : 'dark';
        document.documentElement.classList.add('theme-transition');
        document.documentElement.setAttribute('data-theme', theme);
        if (persist) { try { localStorage.setItem('mj_theme', theme); } catch(e) {} }
        var icon = document.getElementById('wax-theme-toggle-icon');
        var text = document.getElementById('wax-theme-toggle-text');
        if (icon) icon.className = theme === 'light' ? 'bi bi-sun' : 'bi bi-moon-stars';
        if (text) text.textContent = theme === 'light' ? 'Light' : 'Dark';
        setTimeout(function(){ document.documentElement.classList.remove('theme-transition'); }, 260);
    }
    document.addEventListener('DOMContentLoaded', function(){
        var theme = 'dark';
        try { theme = localStorage.getItem('mj_theme') || document.documentElement.getAttribute('data-theme') || 'dark'; } catch(e) {}
        applyWaxTheme(theme, false);
        var btn = document.getElementById('wax-theme-toggle');
        if (btn) btn.addEventListener('click', function(){ applyWaxTheme(document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light', true); });
    });
})();
</script>
</body>
</html>