<?php
// includes/alerts.php
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Setup the standard "Toast" configuration for Success/Info
    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener("mouseenter", Swal.stopTimer)
            toast.addEventListener("mouseleave", Swal.resumeTimer)
        }
    });

    // Success Messages
    <?php if(isset($success_msg) && is_array($success_msg)): ?>
        <?php foreach($success_msg as $msg): ?>
            Toast.fire({
                icon: "success",
                title: "<?php echo addslashes(htmlspecialchars($msg)); ?>"
            });
        <?php endforeach; ?>
    <?php endif; ?>

    // Info Messages
    <?php if(isset($info_msg) && is_array($info_msg)): ?>
        <?php foreach($info_msg as $msg): ?>
            Toast.fire({
                icon: "info",
                title: "<?php echo addslashes(htmlspecialchars($msg)); ?>"
            });
        <?php endforeach; ?>
    <?php endif; ?>

    // Error Messages
    <?php if(isset($error_msg) && is_array($error_msg)): ?>
        <?php foreach($error_msg as $msg): ?>
            Swal.fire({
                icon: "error",
                title: "Error!",
                text: "<?php echo addslashes(htmlspecialchars($msg)); ?>",
                confirmButtonColor: "#d33",
                width: "350px",
                customClass: {
                    popup: "rounded-modal"
                }
            });
        <?php endforeach; ?>
    <?php endif; ?>
});
</script>

<style>
    .rounded-modal {
        border-radius: 20px !important;
    }
</style>