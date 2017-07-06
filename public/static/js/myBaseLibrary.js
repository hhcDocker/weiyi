function buildDialogWindow(obj){
	var obj=obj;
		obj.isCancel=obj.isCancel||0;
		obj.defaultBtn=obj.defaultBtn||"取消";
		obj.primaryBtn=obj.primaryBtn||"确认";
	if(!document.getElementById("dialog_mongolia_layer")){
		var $dialogWindow=$("<div id='dialog_mongolia_layer'>\
			<div id='dialog_window'>\
				<div id='dialog_hd'>\
					<strong id='dialog_title'>弹窗标题</strong>\
				</div>\
				<div id='dialog_bd'>\
					弹窗内容，告知当前状态、信息和解决方法，描述文字尽量控制在三行内</div>\
				<div id='dialog_ft'>\
	                <a href='javascript:;' id='dialog_cancel_btn'></a>\
	                <a href='javascript:;' id='dialog_confirm_btn'></a>\
	            </div>\
	        </div>\
		</div>");
		$("body").append($dialogWindow);
	}
	$("#dialog_mongolia_layer").show();
	$("#dialog_title").html(obj.title);
	$("#dialog_bd").html(obj.content);
	$("#dialog_cancel_btn").html(obj.defaultBtn);
	$("#dialog_confirm_btn").html(obj.primaryBtn);
	if(obj.isCancel){
		$("#dialog_cancel_btn").css("display","block").click(function(){
			$("#dialog_mongolia_layer").hide();
		});
	}else{
		$("#dialog_cancel_btn").hide();
	}
	$("#dialog_confirm_btn").off('click').on("click", function(){
		if(obj.callback){
				obj.callback(obj.parameter);
		}
		$("#dialog_mongolia_layer").hide();
	});
}
function buildToast(flag){
	if(flag){
		if(!document.getElementById("toast_mongolia_layer")){
			var $toastWindow=$("<div id='toast_mongolia_layer'>\
				<div id='toast_window'>\
					<i class='success_i'></i>\
					<p>已完成</p>\
		        </div>\
			</div>");
			$("body").append($toastWindow);
			
		}else{
			$("#toast_window i").addClass("success_i").removeClass("wait_i").siblings("p").html("已完成");
		}
	}else{
		if(!document.getElementById("toast_mongolia_layer")){
			var $toastWindow=$("<div id='toast_mongolia_layer'>\
				<div id='toast_window'>\
					<i class='wait_i'></i>\
					<p>数据加载中</p>\
		        </div>\
			</div>");
			$("body").append($toastWindow);
			
		}else{
			$("#toast_window i").addClass("wait_i").removeClass("success_i").siblings("p").html("数据加载中");
		}
	}
	
}
