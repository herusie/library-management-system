</div><!-- /.content -->
</div><!-- /.app-main -->
<script>
document.getElementById('menu-btn').addEventListener('click', () => document.body.classList.toggle('menu-open'));
document.addEventListener('click', e => {
    const m = document.querySelector('.user-menu');
    if (m && m.open && !m.contains(e.target)) m.open = false;
    if (document.body.classList.contains('menu-open') && !e.target.closest('.sidebar') && e.target.id !== 'menu-btn') {
        document.body.classList.remove('menu-open');
    }
});
</script>
</body>
</html>