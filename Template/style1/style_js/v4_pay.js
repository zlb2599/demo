$(function () {
    /**
     * 左右两侧高度设置
     */
    var oPayRHeight = $('.pay_right_side'),
        oPayLHeight = $('.pay_left_side');
    oPayLHeight.css({height: oPayRHeight.outerHeight() + 'px'});

    /**
     *fm验证
     */
    var oacChkForm = $(".payChkForm").Validform({
        tiptype: 4,
        postonce: true,
        ignoreHidden: true
    });

    /**
     *我的财富-里程牌效果
     */
    var oAchieveLi = $('.achieve_lst li');
    showNode(oAchieveLi, {child0: '.achieve_tips'}, {classes: ''}, 0, 0);

    /**
     * 我的购物车全选效果
     */
    var oAllChb = $('.chb_item'), //全部chb
        oHdChb = $('.hdchb .chb_item'), //头部底部 chb
        oChbItem = $('.chb_item[name=chb_item]'), //课chb
        iCount = oChbItem.length;//课的chb个数
    oHdChb.click(function () {
        var bChb = $(this).is(':checked');
        oAllChb.each(function (index, el) {
            if (bChb) {
                el.checked = true;
            } else {
                el.checked = false;
            }
        });
    });

    oChbItem.click(function (event) {
        var iCountd = $('.chb_item[name=chb_item]:checked').length;//选中课chb的个数
        oHdChb.each(function (index, el) {
            if (iCountd == iCount) {
                el.checked = true;
            } else {
                el.checked = false;
            }
        });

    });

    /**
     * 我的订单-猜你喜欢左右切换效果
     */
    var oTabBtn = $('.like_video .tab_btn a');
    oTabBtn.eq(0).addClass('crt');
    var oScroll = $('#scroll');
    //var dataObj = {};
    //var arrDate = [];
    oTabBtn.click(function () {
        oTabBtn.removeClass('crt');
        $(this).addClass('crt');
        //发送ajax 请求视频
        var ajaxUrl = $(this).attr('href');
        //id = ajaxUrl.substring(ajaxUrl.lastIndexOf('/')+1); //当前切换按钮的id
        //dataObj.id = id;
        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            dataType: 'html',
            success: function (data) {
                //dataObj.html = data;
                //arrDate.push(dataObj);
                oScroll.html(data);
                $("#scroll").jCarouselLite({
                    btnNext: ".like_video .next",
                    btnPrev: ".like_video .pre",
                    visible: scrollNum,
                    scroll: 1,
                    speed: 500
                });
            }
        })
        return false;

    })

    /**
     * 我的订单-订单时间和状态效果
     */
    var oTimeSel = $('.pay_tb .time_sel'),
        oStatusSel = $('.pay_tb .status_sel');
    orderSel(oTimeSel);
    orderSel(oStatusSel);
    function orderSel(obj) {
        obj.change(function () {
            var url = $(this).val();
            window.location.href = url;
        });
    }
    ;

    /**
     * 表单input改变时效果
     */
    var oIpt = $('.order_status_r form .ipt,.fm_item .fm_ipt');
    iptChange(oIpt);

    /**
     * 财富充值-tab切换效果
     */
    /*var oRechTab = $('.recharge_tab'),
     aRechTabBtn = oRechTab.find('.tab_btn'),
     oRechTabLine = oRechTab.find('.line'),
     iLineW = oRechTabLine.width();
     aRechTabBtn.mouseenter(function () {
     var index = $(this).index('.tab_btn');
     oRechTabLine.stop().animate({left: iLineW * index + 'px'}, 200);
     });
     */
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
     * 财富充值-在线充值-支付类型的提交地址改变
     */
    var oPayType = $('.fm_item .paytype input');
    var oPayChkForm = $('.recharge_fm .payChkForm');
    payType($('.fm_item .paytype input:checked'));
    oPayType.click(function () {
        payType($(this));
    });
    function payType(obj) {
        if (obj.val() == '1') {
            oPayChkForm.attr('target', '_blank');
        } else {
            oPayChkForm.attr('target', '_self');
        }
    }

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
    function packet(oType) {
        oPType.show();
        oPacked.hide();
        $("#hb_id").val(0);
        oPType.each(function (i, ele) {
            var money = $(ele).attr('money');
            var iTypeCoin = parseInt(oType);	//充值金额为10元
            var money_float = parseInt(money) * 10;
            if (money_float > iTypeCoin) {
                oPayMent.hide();
                //oPacked.hide();
                $(ele).hide();
            } else {
                oPacked.show();
                $(ele).show();
                if (money_float > iTypeCoin) { //不可用红包隐藏
                    $(ele).hide();
                } else {//可用红包选择
                    oPType.removeClass('crt');
                    $(ele).click(function () {
                        packedType($(this));
                        $("#hb_id").val($(this).attr('attr'));
                    });
                }

            }

        });

    }

    function packedType(obj) {
        var money = obj.attr('money');
        oPType.removeClass('crt');
        obj.addClass('crt');
        oPayMent.show();
        if (obj.hasClass('crt')) {
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
     *    财富兑换-兑换全品验证效果
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

    /**
     * 商品订单-我的订单-提交订单
     */
    var oAddRess = $('.pay_fm .address_info'),
        oAddBtn = $('.address_info .add_btn'),
        oUpdateBtn = $('.address_info .update_btn'),
        unameVal = oAddRess.find('input[name = u_name]').val(),
        uaddrVal = oAddRess.find('input[name = u_addr]').val(),
        uTelVal = oAddRess.find('input[name = u_tel]').val(),
        oTipsFixed = $('.tips_fixed'),
        oClose = oTipsFixed.find('.close'),
        oUnameIpt = oTipsFixed.find('input[name = u_name]'),
        oUAddrIpt = oTipsFixed.find('input[name = u_addr]'),
        oUTelIpt = oTipsFixed.find('input[name = u_tel]'),
        oCover = $('#cover'),
        strHtml = '';
    oAddBtn.click(function () {
        addRess(unameVal, uaddrVal, uTelVal);
    });
    oUpdateBtn.click(function () {
        addRess(unameVal, uaddrVal, uTelVal);
    });
    function addRess(unameVal, uaddrVal, uTelVal) {
        oUnameIpt.val(unameVal);
        oUAddrIpt.val(uaddrVal);
        oUTelIpt.val(uTelVal);
        oCover.show();
        oTipsFixed.show();
        oClose.click(function () {
            oCover.hide();
            oTipsFixed.hide();
        });
        var ajaxPost = $("#addressForm").Validform({
            tiptype: 4,
            ajaxPost: true,
            callback: function (data) {
                if (data.success) {
                    var unameVal = oUnameIpt.val();
                    var uaddrVal = oUAddrIpt.val();
                    var uTelVal = oUTelIpt.val();
                    strHtml = '<h2 class="shop_tt" style="margin-top: 35px;">收货人信息</h2><input type="hidden" name="u_name" value="' + unameVal + '"><input type="hidden" name="u_addr" value="' + uaddrVal + '"><input type="hidden" name="u_tel" value="' + uTelVal + '"><a href="javascript:void(0);" class="update_btn">更改收货人信息</a><label>收货人：<span class="uinfo u_name">' + unameVal + '</span></label><label>收货地址：<span class="uinfo u_addr">' + uaddrVal + '</span></label><label>手机号码：<span class="uinfo u_tel">' + uTelVal + '</span></label>';
                    oAddRess.html(strHtml);
                    oCover.hide();
                    oTipsFixed.hide();
                    oAddRess.find('.update_btn').click(function () {
                        addRess(unameVal, uaddrVal, uTelVal);
                    });
                }
                else {
                    alert(data.msg);

                }
            }
        });
    }


});

/**
 * [iptChange 表单input 改变效果函数]
 * @param  {[type]} obj [当前对象]
 * @return {[type]}     [无]
 */
function iptChange(obj) {
    obj.on('input', function (e) {
        if ($(this).val()) {
            $(this).addClass('focus');
        } else {
            $(this).removeClass('focus');
        }
    });
}

/**
 * [center 对象居中函数]
 * @param  {[type]} obj     [当前对象]
 * @param  {[type]} oParent [父级对象]
 * @return {[type]}         [无]
 */
function center(obj, oParent, isCover) {
    if (isCover) {
        $('#cover').show();
    }
    obj.show();
    $(window).resize(function () {
        obj.css({
            left: (oParent.width() - obj.outerWidth()) / 2,
            top: (oParent.height() - obj.outerHeight() - 100) / 2
        });
    });
    $(window).resize();
}

/**
 * [hideObj 隐藏当前对象]
 * @param  {[type]} obj [当前对象]
 * @return {[type]}     [无]
 */
function hideObj(obj) {
    obj.hide();
    $('#cover').hide();
}
/**
 * [deleteOrder 删除订单记录函数]
 * @param  {[type]} ajaxUrl [路径]
 * @return {[type]}         [无]
 */
function deleteOrder(ajaxUrl) {
    var oTipsPos = $('.tips_pos');
    var oParent = $('.pay_tb');
    center(oTipsPos, oParent, false);
    oTipsPos.find('.close').click(function () {
        hideObj(oTipsPos);
    });
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
}
;

/**
 * [showNode 移入移出函数]
 * @param  {[type]} obj  [移入对象]
 * @param  {[type]} node [切换对象]
 * @return {[type]}      [无]
 */
function showNode(obj, node, classes, speedstart, speedend) {
    obj.mouseenter(function () {
        if (classes) {
            $(this).addClass(classes.class0);
        }
        $(this).find(node.child0).fadeIn(speedstart);
    }).mouseleave(function () {
        if (classes) {
            $(this).removeClass(classes.class0);
        }
        $(this).find(node.child0).fadeOut(speedend);
    });
}
/**
 * [countDown 倒计时跳转函数]
 * @param  {[type]} secs [当前秒数]
 * @param  {[type]} surl [跳转路径]
 */
function countDown(secs, surl) {
    var jumpTo = $('#jumpto');
    if (--secs > 0) {
        jumpTo.text(secs);
        setTimeout("countDown(" + secs + ",'" + surl + "')", 1000);
    }
    else {
        location.href = surl;
    }
}

/**
 * [payForm 余额支付函数]
 * @param  {[type]} curObj [当前点击对象]
 * @return {[type]}        [无]
 */
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
 * 财富兑换表单验证函数
 */
// function exchangeFm(obj){
// 	var oDouText = $('.dou_item .exchange_dou').text();
// 	if(oDouText == '0'){
// 		obj.find('.Validform_checktip').removeClass('Validform_right');
// 		return false;
// 	}
// }

