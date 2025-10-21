// Main JavaScript file
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const body = document.body;
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
            body.classList.toggle('sidebar-active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target) && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                body.classList.remove('sidebar-active');
            }
        });

        // Prevent closing when clicking inside sidebar
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Handle sidebar on mobile devices
    const handleResize = () => {
        if (window.innerWidth <= 768) {
            body.classList.remove('sidebar-active');
        }
    };

    window.addEventListener('resize', handleResize);
    handleResize(); // Initial check

    // Loading Animation
    const loading = document.querySelector('.loading');
    if (loading) {
        window.addEventListener('load', function() {
            loading.style.opacity = '0';
            setTimeout(() => {
                loading.style.display = 'none';
            }, 500);
        });
    }

    // Product Image Error Handler
    const productImages = document.querySelectorAll('.product-image img');
    productImages.forEach(img => {
        img.onerror = function() {
            this.src = 'assets/images/placeholder.jpg';
            this.style.padding = '2rem';
        }
    });

    // Enhanced Add to Cart Animation
    window.addToCart = function(productId) {
        const btn = event.target.closest('.btn');
        if (btn) {
            btn.classList.add('adding');
            btn.disabled = true;
        }

        // Your existing addToCart logic here
        // After success:
        setTimeout(() => {
            if (btn) {
                btn.classList.remove('adding');
                btn.disabled = false;
            }
        }, 1000);
    }

    // Smooth Scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});
