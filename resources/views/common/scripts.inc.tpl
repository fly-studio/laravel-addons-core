<{block "head-scripts-laravel"}>
	<script>var Laravel = { csrfToken: '<{csrf_token()}>' };</script>
<{/block}>
<{block "head-scripts-debug"}>
	<script src="<{'js/debug/eruda.debug.js'|static}>"></script>
<{/block}>
<{block "head-scripts-jquery"}>
	<script src="<{'js/jquery-1.11.3.min.js'|static}>"></script>
	<script>if(typeof jQuery != 'undefined') jQuery.noConflict();</script>
<{/block}>

<{block "head-scripts-bootstrap"}>
	<script src="<{'js/bootstrap3/bootstrap.min.js'|static}>"></script>
<{/block}>

<{block "head-scripts-inner"}><{/block}>

<{block "head-scripts-common"}>
	<script src="<{'js/common.js'|static}>"></script>
<{/block}>