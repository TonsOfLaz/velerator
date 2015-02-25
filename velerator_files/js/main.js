$(document).ready(function() {
	$("ul.list-expandable > li > span").click(function() {
		$(this).parent().find(".list-expandable-contents").slideToggle();
	});
	
	$("ul.list-expandable > li > span").hover(
		function() {
			$( this ).addClass("hover");
		}, function() {
			$( this ).removeClass("hover");
		}
	);
});