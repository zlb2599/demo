
$(function(){
    var oacChkForm = $(".payChkForm").Validform({
        tiptype: 4,
        postonce: true,
        ignoreHidden: true
    });



    $(".matter div:first").show();
    $(".tabs ul li").on("click",function(){
        var index=$(this).index();
        $(".tabs ul li").removeClass('wealth');
        $(this).addClass('wealth');
        $(".matter .big").hide();
        $(".matter .big").eq(index).show();
    });

   /* $(".wealth_box input").on("click",function(){
    	var index=$(this).index();
    	$(".wealth_box input").removeClass('recharge');
    	$(this).addClass('recharge');
    })*/
    /**
     * 财富充值-在线充值-充值账号切换效果
     */
    var oFmItem = $('.fm_item'),
        oUacc = oFmItem.find('.account'),
        oToggleBtn = oFmItem.find('.toggle_btn');
    oUacc.eq(1).hide();
    oToggleBtn.click(function () {
        var index = $(this).index('.toggle_btn');
        oToggleBtn.removeClass('crt');
        $(this).addClass('crt');
        oUacc.hide();
        oUacc.eq(index).show();
    });


    /**
     * 财富充值-在线充值-充值金额效果
     */
    var oType = $('.type_item span.type'), //充值金额按钮
        oPacked = $('.recharge_fm .packet_item'),//红包对象
        oPType = oPacked.find('span.type'),//红包种类
        oPayMent = $('.recharge_fm .payment_item'),//还需支付
        oPayText = oPayMent.find('strong.payment'),//还需支付钱数
        oCoinText = $('.coin_item strong.coin'),//全品币
        oOtherItem = $('.recharge_fm .other_item'), //其他对象
        oOtherIpt = oOtherItem.find('.other_ipt'), //其他金额
        oHdIpt = $('#coin_hdipt'), //全品币隐藏域
        oHdIpt1 = $('#payment_hdipt'),//还需支付金额隐藏域
        reg = /^([1-9]\d*(\.\d{1,2})?|(0\.\d{1,2}))$/;//大于零的数
    oType.click(function () {
        var index = $(this).index();
        oType.removeClass('crt');
        $(this).addClass('crt');
        if (index < 6) {//选择前5个按钮
            var iCurCoin = parseFloat($(this).text());//当前选择的金额
            toFixeNum(iCurCoin);
            toFixePayNum(iCurCoin);//初始化还需支付
            packet(iCurCoin);//红包函数 2016
            oOtherItem.hide();
        } else {//选择第6个按钮
            oOtherIpt.val('');
            oPType.removeClass('crt');
            oOtherItem.show();
            toFixeNum(0);
            toFixePayNum(0);//初始化还需支付
            oPacked.hide();
        }
    });
    
    /**
     * 可使用红包2016
    */
    function packet(oType){
        oPType.show();
		oPacked.hide();
		$("#hb_id").val(0);
        oPType.each(function(i,ele){
			var money = $(ele).attr('money');
     		var iTypeCoin = parseInt(oType);	//充值金额为10元
 			var money_float = parseInt(money) * 10;
            if(money_float > iTypeCoin){
                oPayMent.hide();
                //oPacked.hide();
                $(ele).hide();
            }else{
                oPacked.show();
				$(ele).show();
                if(money_float > iTypeCoin){ //不可用红包隐藏
                    $(ele).hide();
                }else{//可用红包选择
                    oPType.removeClass('crt');
                    $(ele).click(function(){
                        packedType($(this));
						$("#hb_id").val($(this).attr('attr'));
                    });
                }
                
            }
            
        });
        
    }
    
    function packedType(obj){
        oPType.removeClass('crt');
        obj.addClass('crt');
        oPayMent.show();
        if(obj.hasClass('crt')){
            var iPayText = parseFloat(oCoinText.text()) - parseFloat(obj.text());
            toFixePayNum(iPayText);
        }
    }
    
    oOtherIpt.keyup(function () {
        var coinMoney = $(this).val();
        checkNumCoin($(this), coinMoney);
    });

    /**
     * [checkNumCoin 全品币数字验证函数]
     * @param  {[type]} curObj    [当前输入对象]
     * @param  {[type]} coinMoney [当前输入金额]
     */
    function checkNumCoin(curObj, coinMoney) {
        if (coinMoney == '') {
            oCoinText.text(toFixeNum(0));
        }
        if (!isNaN(coinMoney)) {//是数字
            var iPoint = coinMoney.indexOf('.'); //点的位置
            if (iPoint > 0) { //是小数
                var ihStr = coinMoney.substring(iPoint + 1, iPoint + 3);
                var ifStr = coinMoney.substring(0, iPoint + 1);
                var iNewCoin = parseFloat(ifStr + ihStr);
                if (ihStr) {
                    curObj.val(ifStr + ihStr);
                    if (reg.test(ifStr + ihStr)) {
                        toFixeNum(iNewCoin);
                        toFixePayNum(iNewCoin);
                        packet(iNewCoin);
                    }
                }
            } else {//不是小数
                if (reg.test(coinMoney)) {
                    toFixeNum(coinMoney);
                    toFixePayNum(coinMoney);
                    packet(coinMoney);
                }
            }

        } else {//不是数字
            toFixeNum(0);
            toFixePayNum(0);
        }
    }
    /**
     * [toFixeNum 保留两位小数]
     * @param  {[type]} num [处理数]
     * @return {[type]}     [返回数]
     */
    function toFixeNum(num) {
        var newnum = new Number(num);
        newnum = newnum.toFixed(2);
        oCoinText.text(newnum);
        oHdIpt.val(newnum);
    }
    
    /**
     * 还需支付保留两位小数函数
     */
    function toFixePayNum(num) {
        var newnum = new Number(num);
        newnum = newnum.toFixed(2);
        oPayText.text(newnum);
        oHdIpt1.val(newnum);
    }

    /**
     *  财富兑换-兑换全品验证效果 
     */
    var oDouIpt = $('#dou_ipt'),
            oDouText = $('.dou_item .exchange_dou'),
            oCoin = $('#coin');
    oDouIpt.keyup(function () {
        var iCoinCount = $(this).val();
        checkNum($(this), iCoinCount);
    });

    function checkNum(curObj, iCoinCount) {
        if (iCoinCount == '') {
            oDouText.text(0);
        }
        if (!isNaN(iCoinCount)) {//是数字
            var iPoint = iCoinCount.indexOf('.'); //点的位置
            var iSumCoin = parseFloat(oCoin.text());//总全品币
            var bResult = parseFloat(iCoinCount) - iSumCoin; //输入的全品币和总的全品币差值
            if (iPoint > 0) {//是小数
                var ihStr = iCoinCount.substring(iPoint + 1, iPoint + 3);
                var ifStr = iCoinCount.substring(0, iPoint + 1);
                var iNewCoin = parseFloat(ifStr + ihStr);
                if (ihStr) {
                    curObj.val(ifStr + ihStr);
                    if (reg.test(ifStr + ihStr)) {
                        if (bResult < 0) {//输入的全品币和总的全品币比较
                            oDouText.text(iNewCoin * 100);
                        } else {
                            curObj.val(iSumCoin);
                            oDouText.text(iSumCoin * 100);
                        }
                    }
                }
            } else {//不是小数
                if (reg.test(iCoinCount)) {
                    if (bResult < 0) {//输入的全品币和总的全品币比较
                        oDouText.text(iCoinCount * 100);
                    } else {
                        curObj.val(iSumCoin);
                        oDouText.text(iSumCoin * 100);
                    }
                }
            }
        } else {//不是数字
            oDouText.text(0);
        }
    }

})

$( function(){
    $('#screening').click( function(){
        //alert(111);
        $('.Masking-list').toggle();
    });
    $('.close').click(function(){
        $('.Masking-list').hide();
    })
});

$( function(){
    $(".chose .dropdown:first").show();
    $('.chose').bind('click',function(){
        //$(this).find('.dropdown').show().parent().parent().siblings().find('.dropdown:visible').hide();
        $(this).find('.dropdown').toggle();
        $(this).siblings().find('.dropdown:not(:hidden)').hide();
    })
})


function payForm(curObj) {
    if (!window.console || !console.firebug) {
        var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml", "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];

        window.console = {};
        for (var i = 0; i < names.length; ++i)
            window.console[names[i]] = function () {
            }
    }
    var formData = $('#payform').serialize();
    console.log(formData);
    console.log($(curObj).val('支付中...'));
    this.disabled = true;
    $.ajax({
        type: "POST",
        url: "/shop/index/ajaxpay",
        processData: true,
        data: formData,
        dataType: 'json',
        success: function (data) {
            if (data.status == 'y') {
                window.location.href = "/shop/index/payok?order=" + data.info;
            } else {
                window.location.href = "/shop/index/payerror";
            }
            this.disabled = false;
        }
    });
}

/**
 * [deleteOrder 删除订单记录函数]
 * @param  {[type]} ajaxUrl [路径]
 * @return {[type]}         [无]
 */
function deleteOrder(ajaxUrl) {
    var oTipsPos = $('.tips_pos');
    var oParent = $('.matter');
    oTipsPos.show();
    oTipsPos.find('.sub_btn').on('click', function () {
        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.status == 'y') {
                    location.reload();
                } else {
                    alert(data.info);
                }
            }
        });


    });
};