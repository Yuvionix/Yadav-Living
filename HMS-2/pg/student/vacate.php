<?php
$page_title = "Vacate Intimation";
require_once("header.php");

$msg = "";
$msg_type = "";

// Handle vacate notice submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vacate'])) {
    $vacating_on = trim(isset($_POST['vacating_on']) ? $_POST['vacating_on'] : '');
    $comment = trim(isset($_POST['comment']) ? $_POST['comment'] : '');

    if (!empty($vacating_on) && !empty($comment)) {
        // Calculate pending rent if any
        $rent_pending = 0.00;

        $stmt = mysqli_prepare($con, "INSERT INTO pg_vacate_intimations (student_id, intimated_on, vacating_on, rent_pending, comment, status, created_at) VALUES (?, CURDATE(), ?, ?, ?, 'Pending Review', NOW())");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isds", $current_student_id, $vacating_on, $rent_pending, $comment);
            if (mysqli_stmt_execute($stmt)) {
                $msg = "Vacate intimation submitted successfully. Warden will schedule checkout room inspection.";
                $msg_type = "success";
            } else {
                $msg = "Failed to submit vacate intimation.";
                $msg_type = "danger";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $msg = "Please select your planned vacating date and provide a reason.";
        $msg_type = "danger";
    }
}

// Fetch vacate requests for this student
$q_vac = mysqli_query($con, "SELECT * FROM pg_vacate_intimations WHERE student_id = $current_student_id ORDER BY id DESC");
?>

<div class="page-title-banner">
    <h2>VACATE INTIMATION</h2>
    <button type="button" class="btn-create-action" onclick="openVacateModal()">
        <i class='bx bx-plus'></i> Create
    </button>
</div>

<?php if (!empty($msg)): ?>
    <div style="padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 500; <?php echo $msg_type === 'success' ? 'background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;' : 'background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;'; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="pg-table-card">
    <table class="pg-table">
        <thead>
            <tr>
                <th>Intimated On</th>
                <th>Vacating On</th>
                <th>Rent Pending</th>
                <th>Comment</th>
                <th style="text-align: right;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($q_vac) === 0): ?>
                <tr>
                    <td colspan="5" class="no-data-cell">
                        No Data Found
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($q_vac)): ?>
                    <tr>
                        <td style="color: #64748b; white-space: nowrap;">
                            <?php echo date("d/m/Y", strtotime($row['intimated_on'])); ?>
                        </td>
                        <td style="font-weight: 600; color: #0f172a; white-space: nowrap;">
                            <?php echo date("d/m/Y", strtotime($row['vacating_on'])); ?>
                        </td>
                        <td style="color: <?php echo $row['rent_pending'] > 0 ? '#ef4444' : '#10b981'; ?>; font-weight: 600;">
                            ₹<?php echo number_format($row['rent_pending']); ?>
                        </td>
                        <td style="max-width: 320px; color: #475569;">
                            <?php echo htmlspecialchars($row['comment']); ?>
                        </td>
                        <td style="text-align: right;">
                            <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Create Vacate Modal -->
<div class="modal-overlay" id="vacateModal">
    <div class="modal-box">
        <div class="modal-box-header">
            <h4><i class='bx bx-calendar-x'></i> Submit Vacate Intimation</h4>
            <button type="button" class="modal-close-btn" onclick="closeVacateModal()">&times;</button>
        </div>
        <form method="POST" action="vacate.php">
            <div class="modal-box-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 12px; color: #64748b;">
                    <i class='bx bx-info-circle' style="color: #0099e5;"></i> As per PG policy, a 30-day prior notice is required before vacating to process the agreed security deposit refund (₹<?php echo number_format((float)($current_student['deposit_amount'] ?? 0), 2); ?>).
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: 600; color: #334155;">Planned Vacating Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="vacating_on" class="form-control-pg" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 13px; font-weight: 600; color: #334155;">Reason for Leaving & Forwarding Details <span style="color:#ef4444;">*</span></label>
                    <textarea name="comment" rows="4" placeholder="e.g. Course completed / Shifting back to hometown. Please process security deposit refund to UPI..." class="form-control-pg" required></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeVacateModal()" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #64748b; font-size: 13px; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="submit" name="submit_vacate" class="btn-create-action" style="padding: 10px 24px; border-radius: 8px;">
                        <i class='bx bx-send'></i> Submit Notice
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openVacateModal() {
        document.getElementById('vacateModal').style.display = 'flex';
    }
    function closeVacateModal() {
        document.getElementById('vacateModal').style.display = 'none';
    }
</script>

<?php require_once("footer.php"); ?>
