<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_role(array('staff', 'admin'), '../../login.php');
  include('header.php'); 
?>

<!DOCTYPE html>

<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">

    <link rel="stylesheet" href="staff.css">
    <link rel="stylesheet" href="billing.css">
    <link rel="stylesheet" href="admin_view_mem.css">
    <link rel="stylesheet" href="../../css/master_theme.css">
    <link rel="icon" type="image/svg+xml" href="../../home/logo.svg">


    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="sidebar">
        <div class="logo-details">
            <i><img src="../../home/logo.svg"
                 alt="" width="46" height="46" style="border-radius:10px;box-shadow:0 4px 12px rgba(99,102,241,0.25);"></i>
            <span class="logo_name">Yadav Living</span>
        </div>
        <ul class="nav-links">
            <li>
                <a href="staff.php" class="active">
                <i class='bx bxs-face'></i>
                    <span class="links_name">Gate Registry</span>
                </a>
            </li>
            <li class="log_out">
                <a href="../../logout.php">
                    <i class='bx bx-log-out'></i>
                    <span class="links_name">Log out</span>
                </a>
            </li>
        </ul>
    </div>
    
    <section class="home-section">
        <nav>
            <div class="sidebar-button" style="display: flex; align-items: center; gap: 14px;">
                <i class='bx bx-menu sidebarBtn'></i>
                <span class="dashboard">Security & Gate Registry</span>
                <span style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; padding: 4px 12px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; border: 1px solid #fcd34d; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.15);">
                    <i class='bx bxs-sun' style='color:#f59e0b;'></i> जय श्री कृष्णा
                </span>
            </div>
            
            <div class="profile-details">
                <div class="user-avatar-circle">
                    <?php echo strtoupper(substr(isset($_SESSION["username"]) ? $_SESSION["username"] : 'S', 0, 1)); ?>
                </div>
                <span class="admin_name"><?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : 'Staff'; ?> (Security)</span>
            </div>
        </nav>

        <div style="padding: 92px 24px 24px 24px;">
            <div id="view-panel">
                <?php 
                    $allowed_pages = array('registrylist', 'manage_user');
                    $page = isset($_GET['page']) && in_array($_GET['page'], $allowed_pages, true) ? $_GET['page'] : 'registrylist';
                    include $page . '.php'; 
                ?>
            </div>
        </div>

<div class="modal fade" id="confirm_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Confirmation</h5>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='confirm' onclick="">Continue</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="viewer_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
              <button type="button" class="btn-close" data-dismiss="modal"><span class="fa fa-times"></span></button>
              <img src="" alt="">
      </div>
    </div>
  </div>
</body>
<script>
	 window.start_load = function(){
    $('body').prepend('<di id="preloader2"></di>')
  }
  window.end_load = function(){
    $('#preloader2').fadeOut('fast', function() {
        $(this).remove();
      })
  }
 window.viewer_modal = function($src = ''){
    start_load()
    var t = $src.split('.')
    t = t[1]
    if(t =='mp4'){
      var view = $("<video src='assets/uploads/"+$src+"' controls autoplay></video>")
    }else{
      var view = $("<img src='assets/uploads/"+$src+"' />")
    }
    $('#viewer_modal .modal-content video,#viewer_modal .modal-content img').remove()
    $('#viewer_modal .modal-content').append(view)
    $('#viewer_modal').modal({
                  show:true,
                  backdrop:'static',
                  keyboard:false,
                  focus:true
                })
                end_load()  

}
  window.uni_modal = function($title = '' , $url='',$size=""){
    start_load()
    $.ajax({
        url:$url,
        error:err=>{
            console.log()
            alert("An error occured")
        },
        success:function(resp){
            if(resp){
                $('#uni_modal .modal-title').html($title)
                $('#uni_modal .modal-body').html(resp)
                if($size != ''){
                    $('#uni_modal .modal-dialog').addClass($size)
                }else{
                    $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md")
                }
                $('#uni_modal').modal({
                  show:true,
                  backdrop:'static',
                  keyboard:false,
                  focus:true
                })
                end_load()
            }
        }
    })
}
window._conf = function($msg='',$func='',$params = []){
     $('#confirm_modal #confirm').attr('onclick',$func+"("+$params.join(',')+")")
     $('#confirm_modal .modal-body').html($msg)
     $('#confirm_modal').modal('show')
  }
   window.alert_toast= function($msg = 'TEST',$bg = 'success'){
      $('#alert_toast').removeClass('bg-success')
      $('#alert_toast').removeClass('bg-danger')
      $('#alert_toast').removeClass('bg-info')
      $('#alert_toast').removeClass('bg-warning')

    if($bg == 'success')
      $('#alert_toast').addClass('bg-success')
    if($bg == 'danger')
      $('#alert_toast').addClass('bg-danger')
    if($bg == 'info')
      $('#alert_toast').addClass('bg-info')
    if($bg == 'warning')
      $('#alert_toast').addClass('bg-warning')
    $('#alert_toast .toast-body').html($msg)
    $('#alert_toast').toast({delay:3000}).toast('show');
  }
  $(document).ready(function(){
    $('#preloader').fadeOut('fast', function() {
        $(this).remove();
      })
  })
  $('.datetimepicker').datetimepicker({
      format:'Y/m/d H:i',
      startDate: '+3d'
  })
  $('.select2').select2({
    placeholder:"Please select here",
    width: "100%"
  })

  let sidebar = document.querySelector(".sidebar");
  let sidebarBtn = document.querySelector(".sidebarBtn");
  if (sidebarBtn) {
    sidebarBtn.onclick = function () {
        sidebar.classList.toggle("active");
        if (sidebar.classList.contains("active")) {
            sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
        } else
            sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
    };
  }
</script>	

</body>

</html>