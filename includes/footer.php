    </main>
    
    <footer>
        <div class="container">
            <p>Foro Comunal CTI - Tecnologías Libres para el Poder Popular 🚀</p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem; opacity: 0.8;">
                Construyendo comunidad desde 2024
            </p>
        </div>
    </footer>

    <!-- Script para mejoras de UX móvil -->
    <script>
    // Cerrar menú al hacer clic en un enlace (móvil)
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.nav-link');
        const menuToggle = document.getElementById('menu-toggle');
        
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    menuToggle.checked = false;
                }
            });
        });
        
        // Mejorar experiencia táctil
        const cards = document.querySelectorAll('.card, .btn');
        cards.forEach(element => {
            element.style.cursor = 'pointer';
        });
    });
    
    // Detectar si es móvil
    function isMobile() {
        return window.innerWidth <= 768;
    }
    </script>
</body>
</html>