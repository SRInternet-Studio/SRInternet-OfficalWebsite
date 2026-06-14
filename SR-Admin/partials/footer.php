        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuBtn = document.getElementById('mobile-menu-btn');
            const sidebar = document.getElementById('admin-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (menuBtn && sidebar && overlay) {
                const toggleMenu = () => {
                    sidebar.classList.toggle('is-open');
                    overlay.classList.toggle('is-visible');
                    document.body.style.overflow = sidebar.classList.contains('is-open') ? 'hidden' : '';
                };

                menuBtn.addEventListener('click', toggleMenu);
                overlay.addEventListener('click', toggleMenu);
            }
        });
    </script>
</body>
</html>
