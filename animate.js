// Improved sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    // Create hamburger button
    const hamburgerBtn = document.createElement('button');
    hamburgerBtn.className = 'hamburger-toggle';
    hamburgerBtn.innerHTML = `
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    `;
    document.body.prepend(hamburgerBtn);
    
    // Create overlay for closing sidebar when clicking outside
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    // Toggle sidebar function
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-open');
    }
    
    // Event listeners
    hamburgerBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleSidebar();
    });
    
    // Close sidebar when clicking on overlay
    overlay.addEventListener('click', function() {
        document.body.classList.remove('sidebar-open');
    });
    
    // Highlight current page in sidebar
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.sidebar-link').forEach(function(link) {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
        }
    });
    
    // Make the logout buttons work
    document.querySelectorAll('.sidebar-logout-btn, .logout-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            window.location.href = 'admin/logout.php';
        });
    });
    
    // Close sidebar on window resize if screen becomes larger
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && document.body.classList.contains('sidebar-open')) {
            // Keep sidebar open on large screens
        } else if (window.innerWidth <= 992 && document.body.classList.contains('sidebar-open')) {
            // Add class to ensure proper styling on smaller screens
            document.body.classList.add('mobile-sidebar-open');
        }
    });
    
    // Load stats using AJAX
    $.get('get_stats.php', function(data) {
        $('#verifiedUsers').text(data.verified);
        $('#activeSessions').text(data.sessions);
        $('#systemHealth').text(data.health + '%');
    }, 'json');
});
        // Sidebar toggle functionality
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-open');
        });
        
        // Close sidebar when clicking overlay
        document.querySelector('.sidebar-overlay').addEventListener('click', function() {
            document.body.classList.remove('sidebar-open');
        });