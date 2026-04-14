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
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Custom Theme Logic -->
    <script>
        $(document).ready(function() {
            // Select2 Global Init
            $('.form-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $(this).data('placeholder'),
            });

            // Auto-focus search field on open
            $(document).on('select2:open', () => {
                document.querySelector('.select2-search__field').focus();
            });
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

    <!-- Silent WhatsApp Share Iframe -->
    <iframe id="silentShareFrame" style="position: absolute; width: 100%; height: 0; border: 0; visibility: hidden; top: -9999px;"></iframe>

    <script>
        function handleWhatsAppShare(url, btn) {
            const originalHtml = btn.innerHTML;
            const spinner = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            
            // Add loading state
            btn.classList.add('disabled');
            btn.innerHTML = spinner;
            
            // Set iframe source with silent flag
            const silentUrl = url + (url.includes('?') ? '&' : '?') + 'silent=1';
            document.getElementById('silentShareFrame').src = silentUrl;
            
            // Listen for message from iframe
            const messageHandler = function(event) {
                if (event.data && event.data.type === 'whatsapp_share_result') {
                    // Restore button
                    btn.classList.remove('disabled');
                    btn.innerHTML = originalHtml;
                    
                    if (event.data.success) {
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        }).fire({
                            icon: 'success',
                            title: 'PDF share successfully'
                        });
                    } else {
                        Swal.fire('Error', event.data.error || 'Failed to share PDF', 'error');
                    }
                    
                    window.removeEventListener('message', messageHandler);
                }
            };
            
            window.addEventListener('message', messageHandler);
            
            // Timeout safety
            setTimeout(() => {
                if (btn.classList.contains('disabled')) {
                    btn.classList.remove('disabled');
                    btn.innerHTML = originalHtml;
                    window.removeEventListener('message', messageHandler);
                }
            }, 30000);
        }
    </script>
</body>
</html>
