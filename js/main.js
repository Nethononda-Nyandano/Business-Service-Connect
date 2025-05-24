

document.addEventListener('DOMContentLoaded', function () {
    
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // validation
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });

    // Service search functionality
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        const categoryFilter = document.getElementById('categoryFilter');
        const locationFilter = document.getElementById('locationFilter');
        const searchInput = document.getElementById('searchInput');


        function debounce(func, timeout = 300) {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => { func.apply(this, args); }, timeout);
            };
        }

        // search
        const performSearch = debounce(() => {
            const searchParams = new URLSearchParams();

            if (searchInput && searchInput.value) {
                searchParams.append('q', searchInput.value);
            }

            if (categoryFilter && categoryFilter.value) {
                searchParams.append('category', categoryFilter.value);
            }

            if (locationFilter && locationFilter.value) {
                searchParams.append('location', locationFilter.value);
            }

            // Show loading state
            document.getElementById('searchResults').innerHTML = `
                <div class="text-center my-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Searching for services...</p>
                </div>
            `;

            // Fetch search results
            fetch('http://localhost/BusinessServiceTracker/api/search.php?' + searchParams.toString())
                .then(response => response.json())
                .then(data => {
                    displaySearchResults(data);
                })
                .catch(error => {
                    console.error('Error performing search:', error);
                    document.getElementById('searchResults').innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            An error occurred while searching. Please try again.
                        </div>
                    `;
                });
        });


        if (searchInput) {
            searchInput.addEventListener('input', performSearch);
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', performSearch);
        }

        if (locationFilter) {
            locationFilter.addEventListener('change', performSearch);
        }


        function displaySearchResults(results) {
            const resultsContainer = document.getElementById('searchResults');

            if (!results || results.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="text-center my-5">
                        <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                        <h3>No services found</h3>
                        <p>Try adjusting your search criteria or browse categories.</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="row">';

            results.forEach(service => {
                html += `
                    <div class="col-md-4 mb-4">
                        <div class="card service-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">${service.title}</h5>
                                <h6 class="card-subtitle mb-2 text-muted">${service.business_name}</h6>
                                <p class="card-text">${service.description.substring(0, 100)}...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-secondary">${service.category_name}</span>
                                    <small class="text-muted">${service.city}</small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <a href="http://localhost/BusinessServiceTracker/customer/service-details.php?id=${service.id}" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            resultsContainer.innerHTML = html;
        }


        if (window.location.pathname.includes('http://localhost/BusinessServiceTracker/customer/search.php')) {
            performSearch();
        }
    }

    // Service request form validation
    const requestForm = document.getElementById('serviceRequestForm');
    if (requestForm) {
        requestForm.addEventListener('submit', function (event) {
            if (!requestForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            requestForm.classList.add('was-validated');
        });
    }


    const ajaxRequestForm = document.getElementById('ajaxServiceRequestForm');
    if (ajaxRequestForm) {
        ajaxRequestForm.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!ajaxRequestForm.checkValidity()) {
                ajaxRequestForm.classList.add('was-validated');
                return;
            }

            const formData = new FormData(ajaxRequestForm);


            const submitButton = ajaxRequestForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

            fetch('http://localhost/BusinessServiceTracker/api/requests.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {

                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;


                    const modal = bootstrap.Modal.getInstance(document.getElementById('requestServiceModal'));
                    if (modal) {
                        modal.hide();
                    }


                    const alertContainer = document.createElement('div');
                    alertContainer.className = 'alert alert-success alert-dismissible fade show';
                    alertContainer.role = 'alert';
                    alertContainer.innerHTML = `
                    <strong>Success!</strong> Your service request has been submitted.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;

                    document.querySelector('.container').prepend(alertContainer);


                    ajaxRequestForm.reset();
                    ajaxRequestForm.classList.remove('was-validated');


                    setTimeout(() => {
                        window.location.href = 'http://localhost/BusinessServiceTracker/customer/requests.php';
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error submitting request:', error);


                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;


                    const alertContainer = document.createElement('div');
                    alertContainer.className = 'alert alert-danger alert-dismissible fade show';
                    alertContainer.role = 'alert';
                    alertContainer.innerHTML = `
                    <strong>Error!</strong> There was a problem submitting your request. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;

                    document.querySelector('.container').prepend(alertContainer);
                });
        });
    }


    const statusUpdateButtons = document.querySelectorAll('.update-request-status');
    if (statusUpdateButtons.length > 0) {
        statusUpdateButtons.forEach(button => {
            button.addEventListener('click', function () {
                const requestId = this.getAttribute('data-request-id');
                const status = this.getAttribute('data-status');

                if (confirm('Are you sure you want to update this request to ' + status + '?')) {

                    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                    this.disabled = true;

                    const formData = new FormData();
                    formData.append('request_id', requestId);
                    formData.append('status', status);
                    formData.append('action', 'update_status');

                    fetch('http://localhost/BusinessServiceTracker/api/requests.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {

                                window.location.reload();
                            } else {
                                alert('Failed to update request status: ' + data.message);
                                this.innerHTML = status.charAt(0).toUpperCase() + status.slice(1);
                                this.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error updating status:', error);
                            alert('An error occurred. Please try again.');
                            this.innerHTML = status.charAt(0).toUpperCase() + status.slice(1);
                            this.disabled = false;
                        });
                }
            });
        });
    }


    const deleteServiceButtons = document.querySelectorAll('.delete-service');
    if (deleteServiceButtons.length > 0) {
        deleteServiceButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const serviceId = this.getAttribute('data-service-id');

                if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
                   
                    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                    this.disabled = true;

                    const formData = new FormData();
                    formData.append('service_id', serviceId);
                    formData.append('action', 'delete');

                    fetch('http://localhost/BusinessServiceTracker/api/services.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {

                                const serviceCard = this.closest('.service-card');
                                if (serviceCard) {
                                    serviceCard.remove();
                                } else {

                                    window.location.reload();
                                }
                            } else {
                                alert('Failed to delete service: ' + data.message);
                                this.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
                                this.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting service:', error);
                            alert('An error occurred. Please try again.');
                            this.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
                            this.disabled = false;
                        });
                }
            });
        });
    }
});
