<script type="text/javascript">
    (function(){
        console.log(<?php echo json_encode($deBugLogData, PHP_VERSION >= '5.4.0' ? JSON_UNESCAPED_UNICODE : 0);?>);
    })();
</script>