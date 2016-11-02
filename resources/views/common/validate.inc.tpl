<{if !empty($_validates)}>
<script>(function($){$.validates = <{if !empty($_validates)}><{$_validates|@json_encode nofilter}><{else}>{}<{/if}>;})(jQuery)</script>
<script src="<{'js/jquery.validate.min.js'|static}>"></script>
<script src="<{'js/jquery.validate.addons.min.js'|static}>"></script>
<{/if}>
<{if !empty($errors)}>
<script>(function($){$.error_bags = <{if !empty($errors)}><{$errors->toArray()|@json_encode nofilter}><{else}>{}<{/if}>;})(jQuery)</script>
<{/if}>
<{if !empty($_validates) || !empty($errors)}>
<script src="<{'js/laravel.validation.min.js'|static}>"></script>
<{/if}>
<{function name=validate ajax=true selector='#form'}>
jQuery('<{$selector}>')<{if !empty($_validates)}>.validate_addons($.validates)<{/if}><{if !empty($errors)}>.trigger_error_bags($.error_bags)<{/if}><{if $ajax}>.query()<{/if}>;
<{/function}>