$(function() {

	jQuery.support.cors = true;
	
	//登录页面记住密码效果
	var oLoginChk = $(".login_con .login_right p.rember_pwd .checkbox");
	//登录框记住密码
	var oBorChk = $("#login_border .checkbox");
	pwdPrompt(oBorChk);
	pwdPrompt(oLoginChk);
	function pwdPrompt(obj){
		obj.click(function() {
			if (this.checked == true) {
				$(this).siblings('.safe').show(200,function() {
					$(this).delay(4000).animate({
						'display' : 'none'
					}, 0);
				});
			} else {
				$(this).siblings('.safe').hide();
			}
		});
	}
	// 导航年级总按钮经过效果
	$('.welcome .top_grade').hover(function(){
		$(this).find('.menu_pulldown').show();
	},function(){
		$(this).find('.menu_pulldown').hide();
	});

	//登录表单input改变时效果
	var oLogIpt = $('.log_item .logipt');
	iptChange(oLogIpt);

	//绑定登录
	top_login_qp();

});
//绑定登录
function top_login_qp() {
	$("div").data("url", location.href);
	var oLogBtn = $('.log_btn');
	oLogBtn.hover(function() {
		$(this).addClass('hover_btn');
	}, function() {														
		$(this).removeClass('hover_btn');
	});
	//登录页面验证效果
	var ajaxPost = $("#loginIndex").Validform({
		tiptype: 4,
		ajaxPost: true,
		beforeSubmit:function(curform){
			curform.find('.log_btn').addClass('after_btn').val('登录中...');
			curform.find('.log_btn').hover(function() {
				$(this).removeClass('hover_btn');
			});
		},
		callback: function(data,curform) {
			
			if (data.status == "n"){
				$('.log_btn').removeClass('after_btn').val('登录');
				$('.log_btn').hover(function() {
					$(this).addClass('hover_btn');
				});  
			}
			if (data.status == "y") {
				var st = setTimeout(function() {
						if(data.url){
							location.href = data.url;
						}else{
							location.href = curUrl;
						}
					},0);
				}else{
					$('.tips').text(data.info).addClass('Validform_wrong').removeClass('Validform_right');
				}
			}
	});
	ajaxPost.addRule([
		{
		    ele:".shortipt",
		    datatype:"*"
		}
	]);


	// 登录框验证效果
	var ajaxPost2 = $("#login").Validform({
		tiptype: 4,
		ajaxPost: true,
		callback: function(data) {
			if (data.status == "y") {
				var st = setTimeout(function() {
						if(data.url){
							location.href = data.url;
						}else{
							location.reload() ;
						}

					},0);
			}else{
					$('#login_border .message').show();
					$('#login_border .sbt_img').click(function() {
					$('#login_border .message').hide();
					$('.login_con p.pucode span.message').show();
				});
			}
		}
	});

	$('#cover').click(function() {
		$('#login_border').hide();
		$('#cover').hide();
	});
	$('.cuohao').click(function() {
		$('#login_border').hide();
		$('#cover').hide();
	});
	
}
//头部搜索表单验证方法
function searchForm(obj){
	var keyWord = $(obj).find('input[name=kw]').val();
    if(!$.trim(keyWord) || keyWord =="老师名/课程名" || keyWord == " "){
        alert('老师名或者课程名不能为空');
        return false;
   }
}

/**
 * [iptChange 表单input 改变效果函数]
 * @param  {[type]} obj [当前对象]
 * @return {[type]}     [无]
 */
function iptChange(obj){
	obj.on('input',function(e){
	   if($(this).val()){
			$(this).addClass('focus');
		}else{
			$(this).removeClass('focus');
		}
	});
}





