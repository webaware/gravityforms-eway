<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<script>
jQuery(document).on("click", "a.gfeway-dismissable", function() {
	var notice = jQuery(this).closest("div.gfeway-dismissable");

	notice.css({ cursor: "wait" });

	jQuery.getJSON(ajaxurl, { action: "gfeway_dismiss", gfeway_dismiss: notice.data("gfeway-dismiss") }, function(response) {
		jQuery("div.gfeway-dismissable").filter("[data-gfeway-dismiss='" + response.dismissed + "']").hide();
	});

	return false;
});
</script>
