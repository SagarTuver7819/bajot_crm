</div> <!-- Close Main Content -->
    </div> <!-- Close d-flex -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom Theme Logic -->
    <script>
        $(document).ready(function() {
            // DataTables Global Init
            $('.datatable').DataTable({
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search records..."
                }
            });

            // Theme Toggle
            $('#themeToggle').click(function() {
                $('body').toggleClass('dark-mode light-mode');
                const isDark = $('body').hasClass('dark-mode');
                $(this).find('i').toggleClass('fa-sun fa-moon');
                
                // Save to session via AJAX
                $.post('theme_toggle.php', { theme: isDark ? 'dark' : 'light' }, function(data) {
                    location.reload();
                });
            });
            
            // Delete confirmation
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--gold)',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });

            // Toast Configuration
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                background: $('body').hasClass('dark-mode') ? '#2b2b40' : '#fff',
                color: $('body').hasClass('dark-mode') ? '#e1e1e3' : '#333',
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // Handle URL-based notifications
            <?php if (isset($_GET['success']) || isset($_GET['added'])): ?>
                Toast.fire({ icon: 'success', title: 'Action completed successfully!' });
            <?php elseif (isset($_GET['updated'])): ?>
                Toast.fire({ icon: 'success', title: 'Updated successfully!' });
            <?php elseif (isset($_GET['deleted'])): ?>
                Toast.fire({ icon: 'error', title: 'Deleted successfully!' });
            <?php endif; ?>

            // Sidebar Toggle
            $('#sidebarToggle').click(function() {
                if (window.innerWidth > 991) {
                    $('#sidebar').toggleClass('collapsed');
                    $('.main-content').toggleClass('expanded');
                    const isCollapsed = $('#sidebar').hasClass('collapsed');
                    document.cookie = "sidebar=" + (isCollapsed ? 'collapsed' : 'expanded') + "; path=/";
                } else {
                    $('#sidebar').addClass('mobile-show');
                    $('#sidebarBackdrop').addClass('show');
                }
            });

            // Close sidebar on backdrop click
            $('#sidebarBackdrop').click(function() {
                $('#sidebar').removeClass('mobile-show');
                $(this).removeClass('show');
            });
            
            // Close mobile sidebar on nav click
            $('.sidebar-nav .nav-link').click(function() {
                if (window.innerWidth <= 991) {
                    $('#sidebar').removeClass('mobile-show');
                    $('#sidebarBackdrop').removeClass('show');
                }
            });

            // Initialize Sidebar state from cookie
            if (document.cookie.includes('sidebar=collapsed') && window.innerWidth > 991) {
                $('#sidebar').addClass('collapsed');
                $('.main-content').addClass('expanded');
            }
        });
    </script>
</body>
</html>
