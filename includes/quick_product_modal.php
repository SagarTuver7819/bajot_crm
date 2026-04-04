<!-- Quick Add Product Modal -->
<div class="modal fade" id="quickAddProductModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark-card border border-secondary shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-theme"><i class="fa fa-box text-gold me-2"></i>Quick Add Product</h5>
                <button type="button" class="btn-close <?php echo ($theme === 'dark' ? 'btn-close-white' : ''); ?>" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <form id="quickProductForm">
                    <div class="mb-3">
                        <label class="form-label text-theme small fw-bold">Product Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Full Product Name">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-theme small fw-bold">Unit *</label>
                            <select name="unit" class="form-select border-secondary" required>
                                <?php if ($_SESSION['dept_id'] == 1): ?>
                                <option value="Pcs">Pcs</option>
                                <?php endif; ?>
                                <option value="Kgs" selected>Kgs</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-theme small fw-bold">Default Rate (₹)</label>
                            <input type="number" step="0.01" name="rate" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-theme small fw-bold">Opening Kgs</label>
                            <input type="number" step="0.01" name="opening_kgs" class="form-control" value="0.00">
                        </div>
                        <?php if ($_SESSION['dept_id'] == 1): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-theme small fw-bold">Opening Pcs</label>
                            <input type="number" step="0.01" name="opening_pcs" class="form-control" value="0.00">
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid mt-2">
                        <button type="submit" class="btn btn-gold">Save Product <i class="fa fa-check ms-1"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quickProductForm = document.getElementById('quickProductForm');
    let triggerButton = null;

    // We use event delegation to open the modal from anywhere and know which button triggered it
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-bs-target="#quickAddProductModal"]')) {
            triggerButton = e.target.closest('[data-bs-target="#quickAddProductModal"]');
        }
    });

    quickProductForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('ajax_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // If it was triggered from a dynamic table row button
                if (triggerButton) {
                    const row = triggerButton.closest('.item-row');
                    if (row) {
                        const select = row.querySelector('.product-select');
                        if (select) {
                            const option = new Option(data.name, data.id, true, true);
                            option.dataset.rate = data.rate;
                            select.add(option);
                            
                            // Trigger change event to update rates if needed
                            const event = new Event('change', { bubbles: true });
                            select.dispatchEvent(event);
                        }
                    }
                } else {
                    // Overall fallback: Reload lists if not in a table row
                    // But in our case it's mostly table rows
                    location.reload(); 
                }

                bootstrap.Modal.getInstance(document.getElementById('quickAddProductModal')).hide();
                quickProductForm.reset();
                Swal.fire({
                    icon: 'success',
                    title: 'Product Added!',
                    text: 'New product "' + data.name + '" has been saved.',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        });
    });
});
</script>
