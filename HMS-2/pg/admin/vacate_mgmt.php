<?php
$page_title = "Vacate Notices";
require_once("header.php");

$msg = "";

// Handle Vacate Approval & Bed Release
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settle_vacate'])) {
    $vac_id = (int)$_POST['vac_id'];
    $student_id = (int)$_POST['student_id'];

    // Mark vacate as Settled
    mysqli_query($con, "UPDATE pg_vacate_intimations SET status = 'Settled' WHERE id = $vac_id");
    // Free the bed
    mysqli_query($con, "UPDATE pg_beds SET student_id = NULL, status = 'vacant' WHERE student_id = $student_id");
    // Update student status to vacated
    mysqli_query($con, "UPDATE pg_students SET status = 'vacated', room_id = NULL, bed_id = NULL WHERE id = $student_id");

    $msg = "Vacate settled! Student checked out and bed released back to vacant inventory.";
}

$vac_q = mysqli_query($con, "SELECT v.*, s.name as student_name, s.phone as student_phone, s.deposit_amount, r.room_number, b.bed_label 
                            FROM pg_vacate_intimations v 
                            JOIN pg_students s ON v.student_id = s.id 
                            LEFT JOIN pg_rooms r ON s.room_id = r.id 
                            LEFT JOIN pg_beds b ON s.bed_id = b.id 
                            ORDER BY v.id DESC");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: #0f172a;">Student Vacate Requests & Checkout Approvals</h2>
        <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Review 30-day move-out intimations, check pending rent, and settle security deposits.</p>
    </div>
</div>

<?php if (!empty($msg)): ?>
    <div style="padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 500; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="pg-table-card" style="background:#fff; border-radius:10px; border:1px solid #e2e8f0; overflow:hidden;">
    <table style="width:100%; border-collapse:collapse; text-align:left;">
        <thead>
            <tr style="background:#f8fafc; border-bottom:1.5px solid #e2e8f0;">
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Resident Student</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Room / Bed</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Intimated On</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Vacating On</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Security Deposit</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Reason / Comments</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b; text-align:right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($vac_q) === 0): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:36px; color:#64748b;">No active vacate requests submitted.</td>
                </tr>
            <?php else: ?>
                <?php while ($v = mysqli_fetch_assoc($vac_q)): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 18px;">
                            <strong style="font-size:14px; color:#0f172a;"><?php echo htmlspecialchars($v['student_name']); ?></strong>
                            <div style="font-size:11px; color:#64748b;"><i class='bx bx-phone'></i> <?php echo htmlspecialchars($v['student_phone']); ?></div>
                        </td>
                        <td style="padding:14px 18px; font-size:13px; font-weight:600; color:#0099e5;">
                            <?php echo !empty($v['room_number']) ? htmlspecialchars($v['room_number'] . (!empty($v['bed_label']) ? ' (' . $v['bed_label'] . ')' : ''), ENT_QUOTES, 'UTF-8') : 'Not assigned'; ?>
                        </td>
                        <td style="padding:14px 18px; font-size:13px; color:#64748b;">
                            <?php echo date("d M Y", strtotime($v['intimated_on'])); ?>
                        </td>
                        <td style="padding:14px 18px; font-size:13px; font-weight:700; color:#ef4444;">
                            <?php echo date("d M Y", strtotime($v['vacating_on'])); ?>
                        </td>
                        <td style="padding:14px 18px; font-size:13px; font-weight:700; color:#059669;">
                            ₹<?php echo number_format($v['deposit_amount']); ?>
                        </td>
                        <td style="padding:14px 18px; font-size:12px; color:#475569; max-width:240px;">
                            <?php echo htmlspecialchars($v['comment']); ?>
                        </td>
                        <td style="padding:14px 18px; text-align:right;">
                            <?php if ($v['status'] === 'Settled'): ?>
                                <span style="background:#dcfce7; color:#15803d; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700;">Settled & Released</span>
                            <?php else: ?>
                                <form method="POST" action="vacate_mgmt.php">
                                    <input type="hidden" name="vac_id" value="<?php echo $v['id']; ?>">
                                    <input type="hidden" name="student_id" value="<?php echo $v['student_id']; ?>">
                                    <button type="submit" name="settle_vacate" value="1" onclick="return confirm('Confirm checkout and release bed? Security deposit should be refunded.')" style="background:#0099e5; color:#fff; border:none; padding:6px 12px; border-radius:6px; font-size:11px; font-weight:600; cursor:pointer;">
                                        Approve & Release Bed
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once("footer.php"); ?>
