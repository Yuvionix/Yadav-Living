<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_role(array('member', 'admin'), '../../login.php');

$id = 0;
$member_id = 0;
$billing_date = date("Y-m");
$status = 0;
$total_amount = 0;
$username = '';

if(isset($_GET['id'])){
	$get_id = (int)$_GET['id'];
	$stmt = $con->prepare("SELECT b.*, m.username FROM billing b INNER JOIN member m ON m.id = b.member_id WHERE b.id = ?");
	$stmt->bind_param("i", $get_id);
	$stmt->execute();
	$res = $stmt->get_result();
	if($row = $res->fetch_assoc()){
		// IDOR check: if member, ensure the bill belongs to them
		if (isset($_SESSION['account']) && $_SESSION['account'] === 'member') {
			$my_uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
			if ((int)$row['member_id'] !== $my_uid) {
				http_response_code(403);
				echo "<div class='alert alert-danger m-3'>Access Denied: You are not authorized to view this bill.</div>";
				exit;
			}
		}
		$id = $row['id'];
		$member_id = $row['member_id'];
		$billing_date = $row['billing_date'];
		$status = $row['status'];
		$total_amount = $row['total_amount'];
		$username = $row['username'];
	} else {
		echo "<div class='alert alert-warning m-3'>Bill not found.</div>";
		exit;
	}
	$stmt->close();
}
?>
<div class="container-fluid" id="vbill">
	<style>
	#vbill .form-group{
		display: none
	}
	#uni_modal .modal-footer{
		display: none;
	}
	#uni_modal .modal-footer.display{
		display: block;
	}
		#pheader { display: none }

	@media print{
		table{
			width: 100%;
			border-collapse: collapse
		}
		tr{
			border-top:1px solid gray;
			border-bottom:1px solid gray;
		}
		.row {
		    display: flex;
		}
		.col-lg-5 {
		    width: 40%;
		    padding-right: 15px;
		}
		.col-lg-7 {
		    padding-left: 15px;
		    width: 55%;
		}
		.float-right {
		    float: right!important;
		}
		.text-right {
		    text-align: right!important;
		}
		.text-center {
		    text-align: center!important;
		}
		.container-fluid{
		    width: 100%;
		    
		}
		#pheader { display: block }
		p{ margin:unset; }
	}
</style>
	<form action="" id="manage-billing">
		<input type="hidden" name="id" value="<?php echo isset($id) ? $id :'' ?>">
		<div class="row form-group">
			<div class="col-md-6">
					<label for="" class="control-label">Member</label>
					<select name="member_id" id="" class="custom-select select2">
						<option value=""></option>
				<?php 
					if (($_SESSION['account'] ?? '') === 'admin') {
						$member = $con->query("SELECT id, username FROM member ORDER BY username ASC");
					} else {
						$member_stmt = $con->prepare("SELECT id, username FROM member WHERE id = ?");
						if (!$member_stmt) {
							error_log("Resident billing member lookup prepare failed: " . $con->error);
							http_response_code(500);
							exit("Unable to load billing details.");
						}
						$my_uid = hms_current_member_id();
						$member_stmt->bind_param("i", $my_uid);
						if (!$member_stmt->execute()) {
							error_log("Resident billing member lookup failed: " . $member_stmt->error);
							$member_stmt->close();
							http_response_code(500);
							exit("Unable to load billing details.");
						}
						$member = $member_stmt->get_result();
					}
					while($row= $member->fetch_assoc()):
				?>
						<option value="<?php echo $row['id'] ?>" <?php echo isset($member_id) && $row['id'] == $member_id ? 'selected' : '' ?> <?php echo isset($id) && $member_id != $row['id'] ? "disabled" :'' ?>><?php echo ucwords($row['username']) ?></option>
				<?php endwhile; ?>
				<?php if (isset($member_stmt)) $member_stmt->close(); ?>
					</select>
			</div>
			<div class="col-md-6">
					<label for="" class="control-label">Billing Date</label>
					<input type="month" value="<?php echo isset($billing_date) ? date('Y-m',strtotime($billing_date)) : date("Y-m") ?>" class="form-control" name="billing_date" <?php echo isset($id) ? "readonly" :'' ?> >
			</div>
		</div>
			<div id="pheader">
				<p class="text-center"><b>Member Bill</b></p>
				<hr>
			</div>
			<div class="col-lg-12">
				<p><b>Member: <?php echo htmlspecialchars(ucwords($username)) ?></b></p>
				<p><b>Billing Month: <?php echo isset($billing_date) ? date('M, Y',strtotime($billing_date)) : date("M, Y") ?></b></p>
			</div>
		<hr>
		<div class="row" id="prev_details">
			
		</div>
		
	</form>
</div>
<div class="modal-footer display">
	<div class="row">
		<div class="col-md-12">
			<button class="btn btn-secondary float-right" type="button" data-dismiss="modal">Close</button>
			<button class="btn btn-success float-right mr-3" type="button" id="print" onclick="printBill()"><i class="fa fa-print"></i> Print</button>
			<?php if($status == 0): ?>
			<button class="btn btn-primary float-right mr-3" type="button" id="pay"><i class="fa fa-money-bill-wave"></i> Pay</button>
			<?php else: ?>
			<span class="badge badge-success float-right mr-3">Paid</span>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
	$(".select2").select2({
		placeholder:"Please select here",
		width:'100%'
	})
	$("[name='member_id'],[name='billing_date']").change(function(){
		if($("[name='member_id']").val() == '' || $("[name='billing_date']").val() == '')
			return false;
		get_det()
	})
	function get_det(){
		start_load()
			$.ajax({
			url:'get_prev_details.php',
			method:"POST",
			data:{id:$("[name='member_id']").val(),date:$("[name='billing_date']").val(),bid:'<?php echo isset($id) ? $id : '' ?>'},
			success:function(resp){
				if(resp){
					$('#prev_details').html(resp)
					end_load()
				}
			}
		})
	}
	$('#manage-billing').submit(function(e){
		e.preventDefault()
		start_load()
		$.ajax({
			url:'ajax.php?action=save_billing',
			data: new FormData($(this)[0]),
		    cache: false,
		    contentType: false,
		    processData: false,
		    method: 'POST',
		    type: 'POST',
			success:function(resp){
				resp=JSON.parse(resp)
				if(resp.status==1){
					alert_toast("Data successfully saved",'success')
					setTimeout(function(){
						location.reload()
					},1500)

				}
				
			}
		})
	})
	$('#pay').click(function(){
		$(this).hide()
		$('.payment').show()
	})
	function printBill(){
		var nw = window.open("","_blank",'width=800,height=600')
		var data = $('#vbill').clone()
		var div = $('<div><div id="vbill" class="container-fluid"></div></div>')
		div.find('#vbill').html(data)
		nw.document.write(div.html())
		nw.document.close()
		nw.print()
		setTimeout(function(){
			nw.close()
		},700)
	}
	$(document).ready(function(){
		if('<?php echo isset($id) ?>' == 1)
			get_det()


		
	})
</script>