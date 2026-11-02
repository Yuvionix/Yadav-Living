<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_role(array('member', 'admin'), '../../login.php');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// IDOR prevention: regular members can only retrieve their own details
if (isset($_SESSION['account']) && $_SESSION['account'] === 'member') {
	$my_uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
	if ($id !== $my_uid) {
		$id = $my_uid;
	}
}

$date_input = isset($_POST['date']) && preg_match('/^\d{4}-\d{2}$/', $_POST['date']) ? $_POST['date'] : date('Y-m');
$date = $date_input . '-01';
$month_format = date("Y-m", strtotime($date));
$bid = isset($_POST['bid']) && !empty($_POST['bid']) ? (int)$_POST['bid'] : null;

if ($bid !== null && ($_SESSION['account'] ?? '') === 'member') {
	$my_uid = hms_current_member_id();
	$stmt_bill_owner = $con->prepare("SELECT id FROM billing WHERE id = ? AND member_id = ?");
	if (!$stmt_bill_owner) {
		error_log("Billing ownership check prepare failed: " . $con->error);
		http_response_code(500);
		exit("Unable to verify billing access.");
	}
	$stmt_bill_owner->bind_param("ii", $bid, $my_uid);
	if (!$stmt_bill_owner->execute()) {
		error_log("Billing ownership check failed: " . $stmt_bill_owner->error);
		$stmt_bill_owner->close();
		http_response_code(500);
		exit("Unable to verify billing access.");
	}
	$stmt_bill_owner->store_result();
	$owns_bill = $stmt_bill_owner->num_rows > 0;
	$stmt_bill_owner->close();
	if (!$owns_bill) {
		http_response_code(403);
		exit("Unauthorized billing request.");
	}
}

$stmt_chk = $con->prepare("SELECT id FROM billing WHERE member_id = ? AND date_format(billing_date, '%Y-%m') = ?");
$stmt_chk->bind_param("is", $id, $month_format);
$stmt_chk->execute();
$stmt_chk->store_result();
$chk = $stmt_chk->num_rows;
$stmt_chk->close();

if($chk > 0 && empty($bid)){
	echo "<div class='container-fluid'><center><h3><b>Member's Bill already exists for the selected Month.</b></h3></center></div>";
	exit;
}

$monthly_rent = 0;
$prev = array();
$pdate = null;

$stmt_prev_b = $con->prepare("SELECT * FROM billing WHERE member_id = ? AND date(billing_date) < ? ORDER BY date(billing_date) DESC LIMIT 1");
$stmt_prev_b->bind_param("is", $id, $date);
$stmt_prev_b->execute();
$billing_res = $stmt_prev_b->get_result();

if($billing_res->num_rows > 0){
	$result = $billing_res->fetch_assoc(); 
	$pdate = $result['billing_date'];
	$prev_id = (int)$result['id'];

	$stmt_pbills = $con->prepare("SELECT * FROM bills WHERE billing_id = ?");
	$stmt_pbills->bind_param("i", $prev_id);
	$stmt_pbills->execute();
	$prev_res = $stmt_pbills->get_result();
	while($row = $prev_res->fetch_assoc()){
		$prev[$row['type']] = $row;
		if($row['type'] == 2)
			$sys_e_rate = (float)$row['rate'];
		if($row['type'] == 3)
			$sys_w_rate = (float)$row['rate'];
	}
	$stmt_pbills->close();
}
$stmt_prev_b->close();

if(!empty($bid)){
	$stmt_erate = $con->prepare("SELECT * FROM bills WHERE billing_id = ? AND type = 2");
	$stmt_erate->bind_param("i", $bid);
	$stmt_erate->execute();
	$erate_res = $stmt_erate->get_result();
	if($erate_res->num_rows > 0){
		$er = $erate_res->fetch_assoc();
		$sys_e_rate = (float)$er['rate'];
		$econs = (int)$er['consumption'];
	}
	$stmt_erate->close();

	$stmt_wrate = $con->prepare("SELECT * FROM bills WHERE billing_id = ? AND type = 3");
	$stmt_wrate->bind_param("i", $bid);
	$stmt_wrate->execute();
	$wrate_res = $stmt_wrate->get_result();
	if($wrate_res->num_rows > 0){
		$wr = $wrate_res->fetch_assoc();
		$sys_w_rate = (float)$wr['rate'];
		$wcons = (int)$wr['consumption'];
	}
	$stmt_wrate->close();
}

$sys_e_rate = isset($sys_e_rate) ? (float)$sys_e_rate : (isset($_SESSION['sys']['electricity_rate']) ? (float)$_SESSION['sys']['electricity_rate'] : 15.0); 
$sys_w_rate = isset($sys_w_rate) ? (float)$sys_w_rate : (isset($_SESSION['sys']['water_rate']) ? (float)$_SESSION['sys']['water_rate'] : 10.0); 
?> 
<style type="text/css">
	#prev_det p {
		margin:unset;
	}
	.clac-tbl td,.clac-tbl th{
		padding: 3px 5px !important
	}
	.payment{
		display:none
	}
</style>
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-5" id="prev_det">
			<p>Electicity Rate Per kW: <b class="float-right"><?php echo number_format($sys_e_rate,2) ?></b></p>
			<p>Water Rate Per m<sup>3</sup>: <b class="float-right"><?php echo number_format($sys_w_rate,2) ?></b></p>
			<p>Monthly Rate: <b class="float-right"><?php echo number_format($monthly_rent,2) ?></b></p>
			<p>Previous Billing Date: <b class="float-right"><?php echo isset($pdate)? date("M, Y",strtotime($pdate)) : "N/A" ?></b></p>
			<p>Previous Electricty Consumption: <b class="float-right"><?php echo $prev_e_c = isset($prev[2]) && $prev[2]['consumption'] > 0 ? $prev[2]['consumption'] : 0 ?></b></p>
			<p>Previous Electricty Reading: <b class="float-right"><?php echo $prev_e_r = isset($prev[2]) && $prev[2]['reading'] ? $prev[2]['reading'] : 0 ?></b></p>
			<p>Previous Electricty Amount: <b class="float-right"><?php echo $prev_e_a = isset($prev[2]) && $prev[2]['amount'] ? number_format($prev[2]['amount'],2) : 0 ?></b></p>
			<p>Previous Water Consumption: <b class="float-right"><?php echo $prev_w_c = isset($prev[3]) && $prev[3]['consumption'] > 0 ? $prev[3]['consumption'] : 0 ?></b></p>
			<p>Previous Water Reading: <b class="float-right"><?php echo $prev_w_r = isset($prev[3]) && $prev[3]['reading'] ? $prev[3]['reading'] : 0 ?></b></p>
			<p>Previous Water Amount: <b class="float-right"><?php echo $prev_w_a = isset($prev[3]) && $prev[3]['amount'] ? number_format($prev[3]['amount'],2) : 0 ?></b></p>

			<input type="hidden" name="amount[1]" value="<?php echo $monthly_rent ?>">
			<input type="hidden" name="rate[1]" value="<?php echo $monthly_rent ?>">
			<input type="hidden" name="rate[2]" value="<?php echo $sys_e_rate ?>">
			<input type="hidden" name="rate[3]" value="<?php echo $sys_w_rate ?>">
			<input type="hidden" name="previous_amount[1]" value="<?php echo $monthly_rent ?>">

			<input type="hidden" name="previous_consumption[2]" value="<?php echo $prev_e_c ?>">
			<input type="hidden" name="previous_reading[2]" value="<?php echo $prev_e_r ?>">
			<input type="hidden" name="previous_amount[2]" value="<?php echo $prev_e_a ?>">
			<input type="hidden" name="reading[2]" value="0">
			<input type="hidden" name="amount[2]" value="0">

			<input type="hidden" name="previous_consumption[3]" value="<?php echo $prev_w_c ?>">
			<input type="hidden" name="previous_reading[3]" value="<?php echo $prev_w_r ?>">
			<input type="hidden" name="previous_amount[3]" value="<?php echo $prev_w_a ?>">
			<input type="hidden" name="reading[3]" value="0">
			<input type="hidden" name="amount[3]" value="0">
			<input type="hidden" name="o_amount" value="0">

			<hr>
			<div class="form-group">
				<label for="" class="control-label">Electricity Consumption</label>
				<input type="number" value="<?php echo isset($econs) ? $econs :'' ?>" class="form-control" name="consumption[2]">
			</div>
			<div class="form-group">
				<label for="" class="control-label">Water Consumption</label>
				<input type="number" value="<?php echo isset($wcons) ? $wcons :'' ?>" class="form-control" name="consumption[3]">
			</div>
		</div>

		<div class="col-lg-7">
			<table class="table table-condensed clac-tbl">
				<tr>
					<td><b>Monthly Rent</b></td>
					<td class="text-right"><?php echo number_format($monthly_rent,2) ?></td>
				</tr>
		
				<tr>
					<th colspan="2" class="text-center"><b>Electricity</b></th>
				</tr>
				<tr>
					<td>Prev. Consumption</td>
					<td class="text-right"><?php echo number_format($prev_e_c) ?></td>
				</tr>
				<tr>
					<td>Cur. Consumption</td>
					<td class="text-right" id="e_consumption"></td>
				</tr>
				<tr>
					<td>Reading</td>
					<td class="text-right" id="e_reading"></td>
				</tr>
				<tr>
					<td>Rate per (kW)</td>
					<td class="text-right"><?php echo $sys_e_rate ?></td>
				</tr>
				<tr>
					<th>Sub-Total</th>
					<th class="text-right" id="e_total">0.00</th>
				</tr>
				<tr>
					<th colspan="2" class="text-center"><b>Water</b></th>
				</tr>
				<tr>
					<td>Prev. Consumption</td>
					<td class="text-right"><?php echo number_format($prev_w_c) ?></td>
				</tr>
				<tr>
					<td>Cur. Consumption</td>
					<td class="text-right" id="w_consumption"></td>
				</tr>
				<tr>
					<td>Reading</td>
					<td class="text-right" id="w_reading"></td>
				</tr>
				<tr>
					<td>Rate per (m<sup>3</sup>)</td>
					<td class="text-right"><?php echo $sys_w_rate ?></td>
				</tr>
				<tr>
					<th>Sub-Total</th>
					<th class="text-right" id="w_total">0.00</th>
				</tr>
				<tr>
					<th>Amount Due</th>
					<th class="text-right" id="o_total">0.00</th>
				</tr>
				<tr class="payment">
					<th>Amount Payed</th>
					<th class="text-right"><input type="number" class="form-control text-right" step="any" value="0" name="amount_payed"></th>
				</tr>
				<tr class="payment">
					<th>Change</th>
					<th class="text-right"><input type="number" class="form-control text-right" step="any" value="0" name="amount_change" readonly></th>
				</tr>
				<tr class="payment">
					<th class="text-right" colspan="2"><button class="btn btn-primary btn-sm float-right" type="button" onclick="pay()">Save</button></th>
				</tr>
			</table>

		</div>
	</div>

</div>

<script>

	$('[name="amount_payed"]').on('keyup keydown keypress changed',function(){	
		var due = $('[name="o_amount"]').val()
		var tend = $(this).val()
		var change = parseFloat(tend) - parseFloat(due);
		$('[name="amount_change"]').val(change)
	})
	function pay(){
		if($('[name="amount_payed"]').val() <= 0 || $('[name="amount_payed"]').val() == '' ){
			alert_toast("Amount payed must be greater than or equal to the amount due","warning")
			return false;
		}
		start_load()
		$.ajax({
			url:'ajax.php?action=save_payment',
			method:'POST',
			data:{id:<?php echo isset($bid) && $bid > 0 ? $bid :0 ?>,amount_payed:$('[name="amount_payed"]').val(),amount_change:$('[name="amount_change"]').val()},
			success:function(resp){
				if(resp == 1){
					alert_toast("Payment successfully posted.","success");
					setTimeout(function(){
						location.reload()
					},800)
				}
			}
		})
	}
	$('[name="consumption[2]"]').on('keyup keydown keypress',function(){
		var cons = $(this).val();
			cons = cons > 0 ? cons : 0;
		var e_prev_c = <?php echo $prev_e_c ?>;
			e_prev_c = e_prev_c > 0 ? e_prev_c : 0;
		var e_read = parseFloat(cons) - parseFloat(<?php echo $prev_e_c ?>);
			e_read = e_read > 0 ? e_read : 0;
		var e_rate = <?php echo $sys_e_rate ?>;
		var amount = parseFloat(e_read) * parseFloat(e_rate);

		$('#e_consumption').html(cons)
		$('#e_reading').html(parseFloat(e_read))
		$('[name="reading[2]"]').val(parseFloat(e_read))
		$('#e_total').html(parseFloat(amount).toLocaleString('en-US',{style:"decimal",minimumFractionDigits:2,maximumFractionDigits:2}))
		$('[name="amount[2]"]').val(amount)
		calc_total()
	})
	$('[name="consumption[3]"]').on('keyup keydown keypress',function(){
		var cons = $(this).val();
			cons = cons > 0 ? cons : 0;
		var w_prev_c = <?php echo $prev_w_c ?>;
			w_prev_c = w_prev_c > 0 ? w_prev_c : 0;
		var w_read = parseFloat(cons) - parseFloat(<?php echo $prev_w_c ?>);
			w_read = w_read > 0 ? w_read : 0;
		var w_rate = <?php echo $sys_w_rate ?>;
		var amount = parseFloat(w_read) * parseFloat(w_rate);

		$('#w_consumption').html(cons)
		$('#w_reading').html(parseFloat(w_read))
		$('[name="reading[3]"]').val(parseFloat(w_read))
		$('#w_total').html(parseFloat(amount).toLocaleString('en-US',{style:"decimal",minimumFractionDigits:2,maximumFractionDigits:2}))
		$('[name="amount[3]"]').val(amount)
		calc_total()
	})
	function calc_total(){
		var m_amount = $('[name="amount[1]"]').val()
		var e_amount = $('[name="amount[2]"]').val()
		var w_amount = $('[name="amount[3]"]').val()
			m_amount = m_amount > 0 ? m_amount : 0;
			e_amount = e_amount > 0 ? e_amount : 0;
			w_amount = w_amount > 0 ? w_amount : 0;
		var total = parseFloat(m_amount) + parseFloat(e_amount) + parseFloat(w_amount)
		$('[name="o_amount"]').val(total)
		$('#o_total').html(parseFloat(total).toLocaleString('en-US',{style:"decimal",minimumFractionDigits:2,maximumFractionDigits:2}))
	}
	$(document).ready(function(){
		calc_total()
		if('<?php echo isset($bid) ?>' == 1){
				$('[name="consumption[2]"],[name="consumption[3]"]').trigger('keyup');
			}
	})
</script>