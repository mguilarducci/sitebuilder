jQuery(".picture-upload-container").live("click", function(e){
	console.info(e.target);
	if ($(e.target).is("input, .close")) { return;};
	$(this).find("input[type=file]").trigger('click');
})
jQuery(".picture-upload-container .close").live("click", function(e){
	e.preventDefault();
	$.ajax({
		url: this.href,
		type: "GET",
		dataType: "json",
		beforeSend: function(attribute){
			var container = $(e.target).closest(".picture-upload-container");
			if(container.prevAll().length % 3 == 0) {
				container.next().addClass('first');
			}
			else {
				container.next().removeClass('first');
			}
			container.fadeOut();
		},
		success: function(){
			var container = $(e.target).closest("div.field-group");
			$(e.target).closest(".picture-upload-container").remove();
			fixClasses(container);
		},
		error: function(){
			$(e.target).closest(".picture-upload-container").show();
			alert("Não foi possível apagar a sua foto");
		}
	})
})
jQuery(".picture-upload-container input[type='file']").live("change", function(){

	var timestamp = new Date().getTime(),
		container = $(this).closest(".picture-upload-container"),
		iframe_id = "iframe_"+timestamp,
		iframe = $("iframe", container).attr("id", iframe_id).attr("name", iframe_id),
		form = $('<form id="form_'+timestamp+'" target="'+iframe_id+'" action="'+container.data('url')+'" method="post" enctype="multipart/form-data"></form>');

	container.next('.duplicate-previous').click();
	container.next(".picture-upload-container").find("input[type=text]").val("");
	container.addClass("wait").attr('id', 'upload_'+timestamp);
	console.log($(form).attr('action'))
	$("body").append("<div style='display: none' class='hidden' id='container_"+timestamp+"'></div>");

	
	container.find(":input:not(:file)").each(function(){
		var c = $(this).clone(true);
		c.appendTo(form);
	});
	$(this).appendTo(form);
	form.append("<input type='hidden' name='image[timestamp]' value='"+timestamp+"' />");
	$("#container_"+timestamp).append(form);
	console.log(form)
	form.submit();
	
});

$(".picture-upload-container + .duplicate-previous").live('click', function(){
	fixClasses(this);
})

fixClasses = function(target){
	var context = $(target).closest("div.field-group");
	$('.picture-upload-container', context).removeClass("first");
	$(".picture-upload-container:eq(0)", context).addClass("first");
	$('.picture-upload-container:nth-child(4n)', context).addClass("first");
	
}
