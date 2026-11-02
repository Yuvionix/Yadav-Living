<?php
$page_title = "Mess Menu";
require_once("header.php");

$q_menu = mysqli_query($con, "SELECT * FROM pg_mess_menu ORDER BY id ASC");
$current_day = date('l');
?>

<div class="page-title-banner">
    <div>
        <h2>WEEKLY MESS & MEAL MENU</h2>
        <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Nutritious, hygienic food schedule prepared daily at Yadav Living PG Kitchen.</p>
    </div>
    <div style="background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
        Today is: <b><?php echo $current_day; ?></b>
    </div>
</div>

<div class="pg-table-card">
    <table class="pg-table">
        <thead>
            <tr>
                <th style="width: 120px;">Day</th>
                <th>Breakfast (07:30 - 09:30 AM)</th>
                <th>Lunch (12:30 - 02:30 PM)</th>
                <th>Snacks & Tea (05:00 - 06:00 PM)</th>
                <th>Dinner (08:00 - 10:00 PM)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($day = mysqli_fetch_assoc($q_menu)): 
                $is_today = ($day['day_name'] === $current_day);
            ?>
                <tr style="<?php echo $is_today ? 'background: #f0fdf4;' : ''; ?>">
                    <td style="font-weight: 700; color: <?php echo $is_today ? '#166534' : '#0099e5'; ?>; white-space: nowrap;">
                        <?php echo htmlspecialchars($day['day_name']); ?>
                        <?php if ($is_today): ?>
                            <div style="font-size: 10px; color: #16a34a; font-weight: 700;">(TODAY)</div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 13px; color: #334155;">
                        <?php echo htmlspecialchars($day['breakfast']); ?>
                    </td>
                    <td style="font-size: 13px; color: #334155;">
                        <?php echo htmlspecialchars($day['lunch']); ?>
                    </td>
                    <td style="font-size: 13px; color: #334155;">
                        <?php echo htmlspecialchars($day['evening_snacks']); ?>
                    </td>
                    <td style="font-size: 13px; color: #334155; font-weight: 500;">
                        <?php echo htmlspecialchars($day['dinner']); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 20px; padding: 16px; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">
    <b>Mess Guidelines:</b> Meals are strictly served during scheduled hours. Packed lunch requests for college students must be intimated to the Mess Manager before 07:00 AM.
</div>

<?php require_once("footer.php"); ?>
