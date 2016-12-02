<!--本文件需要放置在common.js之后-->
<link rel="stylesheet" href="<{'js/webuploader/webuploader.css'|static}>" />
<link rel="stylesheet" href="<{'css/uploader.min.css'|static}>" />
<script src="<{'js/webuploader/webuploader.nolog.min.js'|static}>"></script>
<script src="<{'js/mimetype.min.js'|static}>"></script>
<script>jQuery.session_id = <{''|@session_id|encrypt|json_encode nofilter}>;</script>
<script src="<{'js/jquery.uploader.min.js'|static}>"></script>
