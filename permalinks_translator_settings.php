<?php
if ('permalinks_translator_settings.php' == basename($_SERVER['SCRIPT_FILENAME']))
     die ('<h2>'.__('Direct File Access Prohibited','permalinks_translator').'</h2>');
?>
<div id="permalinkstranslatorpage">
<div class="wrap">
 <h2>Permalinks Translator Settings</h2>
<form method="post">
	<div><label><?php _e('Source Language'); ?>: <input name="langsrc" value="<?php echo get_option('permalinks_translator_langsrc', 'el');?>"/></label></div>
	<div><label><?php _e('Destination Language'); ?>: <input name="langdest" value="<?php echo get_option('permalinks_translator_langdest', 'en');?>"/></label></div>
	<div><input name="submit" class="button" type="submit" value="<?php _e('Update options &raquo;'); ?>"/></div>
</form>
</div>

</div> 
