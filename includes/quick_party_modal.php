<!-- Quick Add Party Modal -->
<div class="modal fade" id="quickAddPartyModal" tabindex="-1" aria-labelledby="quickAddPartyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-white border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title fw-bold text-gold" id="quickAddPartyModalLabel">Quick Add New Party</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="quickAddPartyForm">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6 mb-3">
              <label class="form-label">Party Name *</label>
              <input type="text" name="name" class="form-control bg-dark text-white border-secondary" required placeholder="Full Name">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Type *</label>
              <select name="type" id="quick_party_type" class="form-select bg-dark text-white border-secondary" required>
                <option value="customer">Customer</option>
                <option value="supplier">Supplier</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Mobile Number</label>
              <input type="text" name="mobile" class="form-control bg-dark text-white border-secondary" placeholder="10 Digit Mobile">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Opening Balance (₹)</label>
              <input type="number" step="0.01" name="opening_balance" class="form-control bg-dark text-white border-secondary" value="0.00">
            </div>
            <div class="col-12 mb-3">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control bg-dark text-white border-secondary" rows="2" placeholder="Complete Address"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-gold px-4">SAVE PARTY <i class="fa fa-save ms-1"></i></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quickAddForm = document.getElementById('quickAddPartyForm');
    const partySelect = document.querySelector('select[name="party_id"]');
    
    if (quickAddForm) {
        quickAddForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Saving... <i class="fa fa-spinner fa-spin ms-1"></i>';

            fetch('ajax_party.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'SAVE PARTY <i class="fa fa-save ms-1"></i>';
                
                if (data.status === 'success') {
                    // Add new option and select it
                    const newOption = new Option(data.name, data.id, true, true);
                    partySelect.add(newOption);
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('quickAddPartyModal'));
                    modal.hide();
                    
                    // Reset form
                    quickAddForm.reset();
                    
                    Swal.fire('Success', 'Party added successfully!', 'success');
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong', 'error');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'SAVE PARTY <i class="fa fa-save ms-1"></i>';
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to save party', 'error');
            });
        });
    }
});
</script>
