var real = $(".detail").offset().left + $(".detail").width();
var detail_left = $(".content").width();
window.onresize = function() {
	var width = -($(".detail").offset().left + $(".detail").width() - $("body").width());
	var boundary_width = $(window).width();
	$(".preview").fadeTo(0, 0.01 * (width-340));
	if (width >= 340){
		$(".preview").width(width - 50);
	}
	if (boundary_width < real + 50) {
		$(".detail").width(boundary_width - detail_left - 110);
	}
}

var page_name_start = location.href.lastIndexOf("/") + 1;
page_name = location.href.substring(page_name_start, location.href.lastIndexOf("."));
$("a[name=content_" + page_name + "]").css("color", "#da8439")
function slide(t, obj) {
	if (obj.css("display") == "none") {
		$(t).children("img:first").attr("src", domain + "/image/tree-extended.png?")
	} else {
		$(t).children("img:first").attr("src", domain + "/image/tree-fold.png")
	}
	obj.slideToggle()
}
$("label[name=folder]").on("click", function() {
	slide(this, $(this).next())
})
$("a[name=folder]").on("click", function() {
	slide(this, $(this).next().next())
})

function toggle_content() {
	if ($(".detail").css("marginLeft") != "-1px") {
		$(".detail").animate({"margin-left": -1, "width": $(".detail").width() - $(".content").width()}, 300)
		if ($(".content").attr("rel")) {
			$(".content").animate({"opacity": 1, "height": $(".content").attr("rel")}, 300, function() {
				$(".content").css("height", "")
				$(".content").attr("rel", "")
			})
		}
	} else {
		$(".detail").animate({"margin-left": -$(".content").width() - 21, "width": $(".detail").width() + $(".content").width()}, 300)
		if ($(".content").height() > $(".detail").height()) {
			$(".content").attr("rel", $(".content").height())
			$(".content").animate({"opacity": 0.01, "height": $(".detail").height()}, 300, function() {
			})
		}
	}
}
