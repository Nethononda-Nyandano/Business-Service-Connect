    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>Business Service Connect</h5>
                    <p>Connecting businesses and customers for seamless service delivery.</p>
                </div>
                <div class="col-md-4 mb-3" style="border-left: 1px solid gray;">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="http://localhost/BusinessServiceTracker/index.php" class="text-decoration-none text-light">Home</a></li>
                        <li><a href="http://localhost/BusinessServiceTracker/customer/search.php" class="text-decoration-none text-light">Find Services</a></li>
                        <li><a href="http://localhost/BusinessServiceTracker/auth/register.php" class="text-decoration-none text-light">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3" style="border-left: 1px solid gray;">
                    <h5>Contact Us</h5>
                    <address>
                        <i class="fas fa-map-marker-alt me-2"></i> 18 Biccard street, Polokwane 0700<br>
                        <i class="fas fa-phone me-2"></i> 0729549330<br>
                        <i class="fas fa-envelope me-2"></i> maswanganyi@businessserviceconnect.com
                    </address>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Business Service Connect. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end mb-3">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <a href="#" class="text-light">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-light">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-light">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-light">
                                <i class="fab fa-instagram"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>

    <?php if (isLoggedIn()): ?>
        <script>
            $(document).ready(function() {

                $('#notificationsDropdown').on('show.bs.dropdown', function() {
                    loadNotifications();
                });


                $('#mark-all-read').on('click', function(e) {
                    e.preventDefault();
                    markAllNotificationsAsRead();
                });
            });

            function loadNotifications() {
                $.ajax({
                    url: 'http://localhost/BusinessServiceTracker/api/notifications.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        displayNotifications(data);
                    },
                    error: function() {
                        $('#notifications-container').html('<div class="text-center p-2">Failed to load notifications</div>');
                    }
                });
            }

            function displayNotifications(notifications) {
                var html = '';

                if (notifications.length === 0) {
                    html = '<div class="text-center p-2">No notifications</div>';
                } else {
                    notifications.forEach(function(notification) {
                        var readClass = notification.is_read ? '' : 'bg-light';
                        html += '<a class="dropdown-item ' + readClass + '" href="#" data-id="' + notification.id + '">';
                        html += '<small class="text-muted">' + notification.created_at + '</small>';
                        html += '<p class="mb-0">' + notification.message + '</p>';
                        html += '</a>';
                    });
                }

                $('#notifications-container').html(html);

                s
                $('#notifications-container a').on('click', function(e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    markNotificationAsRead(id);
                    $(this).removeClass('bg-light');
                });
            }

            function markNotificationAsRead(id) {
                $.ajax({
                    url: 'http://localhost/BusinessServiceTracker/api/notifications.php',
                    type: 'POST',
                    data: {
                        action: 'mark_read',
                        notification_id: id
                    },
                    success: function() {

                        updateNotificationCount();
                    }
                });
            }

            function markAllNotificationsAsRead() {
                $.ajax({
                    url: 'http://localhost/BusinessServiceTracker/api/notifications.php',
                    type: 'POST',
                    data: {
                        action: 'mark_all_read'
                    },
                    success: function() {

                        $('#notifications-container a').removeClass('bg-light');

                        updateNotificationCount();
                    }
                });
            }

            function updateNotificationCount() {
                $.ajax({
                    url: 'http://localhost/BusinessServiceTracker/api/notifications.php',
                    type: 'GET',
                    data: {
                        action: 'count_unread'
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.count > 0) {
                            $('#notificationsDropdown').html('<i class="fas fa-bell"></i> <span class="badge rounded-pill bg-danger">' + data.count + '</span>');
                        } else {
                            $('#notificationsDropdown').html('<i class="fas fa-bell"></i>');
                        }
                    }
                });
            }
        </script>
    <?php endif; ?>
    </body>

    </html>