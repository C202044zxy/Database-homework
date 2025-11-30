/**
 * SummitSphere JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-dismiss alerts after 5 seconds
    var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirm delete actions
    var deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });

    // Form validation
    var forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Search filter functionality
    var searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var filter = this.value.toLowerCase();
            var table = document.querySelector('.searchable-table');
            if (table) {
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            }
        });
    }

    // Quantity input controls
    var quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(function(input) {
        var minusBtn = input.parentElement.querySelector('.qty-minus');
        var plusBtn = input.parentElement.querySelector('.qty-plus');

        if (minusBtn) {
            minusBtn.addEventListener('click', function() {
                var val = parseInt(input.value) || 1;
                if (val > 1) {
                    input.value = val - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }

        if (plusBtn) {
            plusBtn.addEventListener('click', function() {
                var val = parseInt(input.value) || 0;
                var max = parseInt(input.max) || 999;
                if (val < max) {
                    input.value = val + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }
    });

    // Star rating input
    var ratingInputs = document.querySelectorAll('.rating-input');
    ratingInputs.forEach(function(container) {
        var stars = container.querySelectorAll('.star');
        var input = container.querySelector('input[type="hidden"]');

        stars.forEach(function(star, index) {
            star.addEventListener('click', function() {
                var rating = index + 1;
                input.value = rating;

                stars.forEach(function(s, i) {
                    s.classList.toggle('filled', i < rating);
                });
            });

            star.addEventListener('mouseenter', function() {
                var rating = index + 1;
                stars.forEach(function(s, i) {
                    s.style.color = i < rating ? '#ffc107' : '#ddd';
                });
            });
        });

        container.addEventListener('mouseleave', function() {
            var currentRating = parseInt(input.value) || 0;
            stars.forEach(function(s, i) {
                s.style.color = i < currentRating ? '#ffc107' : '#ddd';
            });
        });
    });

    // Dynamic form fields
    var addItemButtons = document.querySelectorAll('.add-item-row');
    addItemButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var container = document.querySelector(this.dataset.container);
            var template = document.querySelector(this.dataset.template);
            if (container && template) {
                var clone = template.content.cloneNode(true);
                var index = container.children.length;
                clone.querySelectorAll('[name]').forEach(function(el) {
                    el.name = el.name.replace('[0]', '[' + index + ']');
                });
                container.appendChild(clone);
            }
        });
    });

    // Remove item row
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item-row')) {
            e.target.closest('.item-row').remove();
        }
    });

    // Print functionality
    var printButtons = document.querySelectorAll('.btn-print');
    printButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            window.print();
        });
    });

    // Export to CSV
    var exportButtons = document.querySelectorAll('.btn-export-csv');
    exportButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var tableId = this.dataset.table;
            var table = document.getElementById(tableId);
            if (table) {
                exportTableToCSV(table, 'export.csv');
            }
        });
    });
});

/**
 * Export table to CSV file
 */
function exportTableToCSV(table, filename) {
    var csv = [];
    var rows = table.querySelectorAll('tr');

    rows.forEach(function(row) {
        var cols = row.querySelectorAll('td, th');
        var rowData = [];
        cols.forEach(function(col) {
            rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });

    var csvContent = csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

/**
 * Update cart totals
 */
function updateCartTotals() {
    var subtotal = 0;
    var cartItems = document.querySelectorAll('.cart-item');

    cartItems.forEach(function(item) {
        var price = parseFloat(item.dataset.price);
        var quantity = parseInt(item.querySelector('.quantity-input').value);
        var itemTotal = price * quantity;
        item.querySelector('.item-total').textContent = formatCurrency(itemTotal);
        subtotal += itemTotal;
    });

    var subtotalEl = document.getElementById('cart-subtotal');
    var taxEl = document.getElementById('cart-tax');
    var totalEl = document.getElementById('cart-total');

    if (subtotalEl) {
        var tax = subtotal * 0.10; // 10% tax
        var total = subtotal + tax;

        subtotalEl.textContent = formatCurrency(subtotal);
        taxEl.textContent = formatCurrency(tax);
        totalEl.textContent = formatCurrency(total);
    }
}

/**
 * AJAX helper function
 */
function ajaxRequest(url, method, data, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch (e) {
                    callback('Invalid JSON response');
                }
            } else {
                callback('Request failed: ' + xhr.status);
            }
        }
    };

    xhr.send(data ? JSON.stringify(data) : null);
}

/**
 * Show loading spinner
 */
function showLoading() {
    var spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    spinner.id = 'loadingSpinner';
    document.body.appendChild(spinner);
}

/**
 * Hide loading spinner
 */
function hideLoading() {
    var spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.remove();
    }
}
