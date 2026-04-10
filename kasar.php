<?php
$page_title = 'Kasar (Adjustment) Management';
require_once 'includes/header.php';

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Kasar / Adjustment Module</h4>
        <button type="button" class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addKasarModal">
            <i class="fa fa-plus-circle me-1"></i> New Kasar Entry
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card card-bajot mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-4">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-gold w-100"><i class="fa fa-filter me-1"></i> Filter Entries</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-bajot">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-custom table-hover" id="kasarTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Party Name</th>
                        <th class="text-end">Amount</th>
                        <th>Description</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $dept_id = (int)$_SESSION['dept_id'];
                    $sql = "SELECT k.*, p.name as party_name 
                            FROM kasars k 
                            JOIN parties p ON k.party_id = p.id 
                            WHERE k.dept_id = $dept_id AND k.date BETWEEN '$from_date' AND '$to_date'
                            ORDER BY k.date DESC";
                    $res = $conn->query($sql);
                    while($row = $res->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['party_name']; ?></td>
                        <td class="text-end fw-bold"><?php echo format_currency($row['amount']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td class="text-center">
                            <button onclick="deleteKasar(<?php echo $row['id']; ?>)" class="btn btn-sm btn-outline-danger">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Kasar Modal -->
<div class="modal fade" id="addKasarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark-card border-secondary">
            <div class="modal-header">
                <h5 class="modal-title">New Kasar Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addKasarForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Party</label>
                        <select name="party_id" id="kasar_party_id" class="form-select" required onchange="fetchPartyBalance(this.value)">
                            <option value="">-- Choose Party --</option>
                            <?php 
                            $ps = $conn->query("SELECT id, name FROM parties ORDER BY name ASC");
                            while($p = $ps->fetch_assoc()) echo "<option value='{$p['id']}'>{$p['name']}</option>";
                            ?>
                        </select>
                        <div id="partyBalanceDisplay" class="mt-2 small" style="display:none;">
                            Current Balance: <span id="currentBalanceVal" class="fw-bold"></span>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-2 text-info text-decoration-none" onclick="fillSettlementAmount()">Fill Full Amount</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold">Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addKasarForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'add_kasar');

    fetch('ajax_kasar.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
};

function deleteKasar(id) {
    if(!confirm('Are you sure you want to delete this entry?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_kasar');
    formData.append('id', id);

    fetch('ajax_kasar.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

let lastFetchedBalance = 0;
function fetchPartyBalance(partyId) {
    if(!partyId) {
        document.getElementById('partyBalanceDisplay').style.display = 'none';
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'get_balance');
    formData.append('party_id', partyId);

    fetch('ajax_kasar.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            lastFetchedBalance = Math.abs(data.balance);
            const display = document.getElementById('partyBalanceDisplay');
            const valSpan = document.getElementById('currentBalanceVal');
            
            valSpan.innerText = data.formatted_balance + (data.is_debit ? ' (To Collect)' : ' (To Pay)');
            valSpan.className = 'fw-bold ' + (data.is_debit ? 'text-danger' : 'text-success');
            display.style.display = 'block';
        }
    });
}

function fillSettlementAmount() {
    document.querySelector('#addKasarModal input[name="amount"]').value = lastFetchedBalance.toFixed(2);
}
</script>

<?php require_once 'includes/footer.php'; ?>
