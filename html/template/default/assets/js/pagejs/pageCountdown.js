
class PageCountdown {
    static urlInfo = "";
    static urlReward = "";
    static isVersion405 = false;
    static isUserRole = false;
    static user_id = ''

    static _pageCountdown = null;

    ERROR_NO_USER = 1;
    
    isCountdownNotRun = false
    timeCountdown = 0;
    startCountdown = false;
    waitCountDownNextReward = false;
    requestGetInfo = null;
    requestGetReward = null;
    pageCountdownManager = null;
    _docmentReady = false;
    _windownReady = false;

    modalPageCountdown = null;

    timeOutCountDown = null;

    constructor(){
        // if(!PageCountdown.isUserRole){
        //     this.disableModal();
        //     return;
        // }
        
        pageCountdownTime.uid = PageCountdown.user_id;
        pageCountdownKey.uid = PageCountdown.user_id;
        pageCountdownRestrictReward.uid = PageCountdown.user_id;
        pageCountdownTimeAgain.uid = PageCountdown.user_id;
        pageCountdownLastGotReward.uid = PageCountdown.user_id;
        this.init();
    }

    /* functions init */
    init() {
        this.timeCountdown = pageCountdownTime.value ?? 0;      
        this.startCountdown = false;
        this.requestGetInfo = new PageCountdownInfoRequest({
            url:PageCountdown.urlInfo,
            type: 'GET',
            time:pageCountdownTime.value,
            key:pageCountdownKey.value,
            timeGetAgain:pageCountdownTimeAgain.value,
            lastGot:pageCountdownLastGotReward.value,
        })
        this.requestGetInfo.setCallBack((data) => this.requestInfoSuccess(data));

        this.requestGetReward = new PageCountdownRewardRequest({
            url: PageCountdown.urlReward,
            type: 'POST'
        })
        this.requestGetReward.setCallBack((data) => this.requestRewardSuccess(data));

        $(document).ready(() => {
            this.initDocumentReady()
        })
    }

    
    initDocumentReady() {
        this.modalPageCountdown = new ModalPageCountDown();
        this.modalPageCountdown.callBackClose = this.modalClose;

        if(!this.isActiveCountdown())
            return

        this.pageCountdownManager = PageCountdownManager.create(
            document.getElementsByClassName('page_countdown_box')[0])
        
        setTimeout(() => {
            $(window).scroll((scroller) => this.onScroll(scroller))
        }, 3000)
        
        switch(this.requestGetInfo.state){
            case PageCountdownInfoRequest.NO_LOGIN_FINISH:
                if(PageCountdown.user_id != 'abcd'){
                    this.requestGetReward.data = { countdown_no_login: true}
                    this.requestGetReward.run()
                    pageCountdownNoLoginFinish.value = 0;
                }
                else{
                    if(!pageCountdownNotShowAgain.value || pageCountdownNotShowAgain.value == 0)
                        this.modalPageCountdown.showMess(pageCountdownNoLoginFinish.value)
                } 
                break;
            case PageCountdownInfoRequest.NO_INFO:
                this.requestGetInfo.run()
                break;
            case PageCountdownInfoRequest.INTERVAL_TIME:
                this.runCountDownNextGetReward()
                break;
            default:
                this.pageCountdownManager.setCss('.box_active','display',"block")
                this.pageCountdownManager.updateDisplay();
                this.pageCountdownManager.setTime(this.timeCountdown);
                this.setNumRewwardOnDay();
        }
    }

    /* return only PageCountdown*/
    static getInstance() {
        if(!PageCountdown._pageCountdown) {
            PageCountdown._pageCountdown = new PageCountdown()
        }

        return PageCountdown._pageCountdown
    }

    /* functions request callback */
    requestInfoSuccess(data) {
        if(data.active) {
            if(data.last_time_get_reward){
                this.setTimeGotAgain(data.last_time_get_reward,data.next_time_get_reward);
                
                if(pageCountdownTimeAgain.value > 0) {
                    this.runCountDownNextGetReward();
                    return;
                }
            }

            this.timeCountdown = data.second;
            pageCountdownRestrictReward.value = data.time;
            pageCountdownKey.value = data.key; // multi tag => multi sessionStorage => multi request send get gift.
            this.pageCountdownManager.setTime(this.timeCountdown);
            this.setNumRewwardOnDay();

            this.pageCountdownManager.setCss('.box_active','display',"block")
            this.pageCountdownManager.updateDisplay();
        }
    }
    requestRewardSuccess(data) {
        this.pageCountdownManager.setCss('.box_active','display',"none")
        if(data.errorCode){
            switch(data.errorCode){
                case this.ERROR_NO_USER:
                    let pointNumber = this.convertToMoney(data.point);
                    pageCountdownNoLoginFinish.value = pointNumber;

                    this.modalPageCountdown.showMess(pointNumber)
                    break;
                default:
            }
        }
        else if(data.point && data.point > 0) {
            pageCountdownNoLoginFinish.value = 0;

            this.waitCountDownNextReward = true;
            let pointNumber = this.convertToMoney(data.point);
            this.modalPageCountdown.showReward(pointNumber,data.second)

            this.setNumRewwardOnDay();
            
            let userPoint = this.convertToMoney(data.userPoint);
            $("[my-cur-point]").html(userPoint);

            this.setTimeGotAgain(data.last_time_get_reward,data.next_time_get_reward);
            this.runCountDownNextGetReward();

            pageCountdownTime.value = 0;
            pageCountdownKey.removeValue();
        }
    }

    setTimeGotAgain(lastTimeReward, nextTimeReward){
        let lastGetMinisecond = lastTimeReward * 1000;
        let nextTimeRewardMinisecond = nextTimeReward * 60 * 1000;
        let curTime = (new Date).getTime();
        pageCountdownLastGotReward.value = lastGetMinisecond
        pageCountdownTimeAgain.value = lastGetMinisecond + nextTimeRewardMinisecond - curTime
    }

    setNumRewwardOnDay(){
        let curNumberReward = pageCountdownKey.value ? parseInt(pageCountdownKey.value.split('_')[3]) : 0;
        let curNumberRestrict = pageCountdownRestrictReward.value ? parseInt(pageCountdownRestrictReward.value) : 1;

        this.pageCountdownManager.setTimesOnDay(curNumberReward, curNumberRestrict)
     //   $('#page_countdown_times_on_day_modal').html((curNumberReward + 1) + '/' + curNumberRestrict);
    }

    /* orther */
    onScroll(scroller) {
        if(!this.timeCountdown || this.timeCountdown <= 0) return;

        this.pageCountdownManager.updateDisplay();  
        pageCountdownTime.value = this.timeCountdown;
        this.startCountdown = true;
        this.runCountdown()
    }

    runCountdown() {
        if(this.timeCountdown > 0 && !this.timeOutCountDown){
            this.timeOutCountDown = setTimeout(() => {
                this.timeCountdown -= 1;
                this.pageCountdownManager.setTime(this.timeCountdown);
                pageCountdownTime.value = this.timeCountdown;

                this.timeOutCountDown = null;
                if(this.timeCountdown <= 0) {
                    this.requestGetReward.data = { key: pageCountdownKey.value }
                    this.requestGetReward.run()
                }
                
            }, 1000);
        }
    }

    convertToMoney(number) {
        var re = '\\d(?=(\\d{3})+$)';
        return number.toFixed(0).replace(new RegExp(re, 'g'), '$&,');
    }

    runCountDownNextGetReward() {
        let sintCountdown = setInterval(() => {
            if(!pageCountdownTimeAgain.value || pageCountdownTimeAgain.value <= 0) {
                clearInterval(sintCountdown)
                this.requestGetInfo.run()
                return;
            }

            pageCountdownTimeAgain.value = pageCountdownTimeAgain.value - 1000
            console.log(pageCountdownTimeAgain.value);
        },1000)
    }

    isActiveCountdown() {
        if(this.isCountdownNotRun)
            return false;
        
        let pathName = window.location.pathname;
        let listKeys = this.listPartNotShow();
        for(let i = 0; i < listKeys.length; i++) {
            if(pathName.indexOf(listKeys[i]) >= 0)
                return false
        }
        

        return true;
    }
    
    modalClose() {
        this.startCountdown = false;
    }

    showRewardUserNoLogin(point) {
       this.showMess(point);
    }

    listPartNotShow(){
        return ['mypage','entry','forgot']

    }

    listPartNotShow(){
        return ['mypage','entry','forgot']

    }
}

class ModalPageCountDown{
    callBackClose = null;
    isReward;
    constructor(){
        this.init();
    }

    init(){
        let seft = this;

        this._hideMode();

        $('#page_countdown_not_show, #countdown_checkbox_notshow').on('click', function(event) {
            event.stopPropagation()
            seft._hideMode();
            pageCountdownNotShowAgain.value = (new Date()).getTime();
        });

        $('.coundown-modal .ec-modal-overlay, .coundown-modal .ec-inlineBtn--cancel').on('click', function() {
            if(seft.isReward) {
                seft._hideMode();
            }
            // else {
            //    window.location.href = PageCountdown.urlLogin;
            // }
        });

        $('.gift-box-close').click(() => {
            console.log("box-close")
            seft._hideMode();
        })

        $('#ec-modal-countdown').change(function() {
            if(!this.checked) {
                seft._hideMode();
            }
        });
    }

    showReward(reward_str,second = 15) {
        this.isReward = true;
        this._modeReward();
        $('.coundown-modal .title-top').html(`瀏覽頁面滿${second}秒，即可獲得點數！<br>(${reward_str}點)`)
        $('#page_countdown_reward').html(reward_str + 'p');
    }

    showMess(reward_str) {
        this.isReward = false;
        this._modeMessage();
        $('.coundown-modal .title-top').html('立即登入, 領取點數!')
        $('#page_countdown_reward').html('');
    }

    _modeReward() {
        this._showModal();
        $('#page_countdown_times_on_day_modal').css("display", "")
    }

    _modeMessage(){
        this._showModal();
        $('#page_countdown_login').css("display", "")
        $('#page_countdown_not_show').css("display", "")
        $('#page_countdown_times_on_day_modal').css("display", "none")
        $('.coundown-modal .title-top').html("立即登入, 領取點數!")
    }

    _showModal() {
        $('.coundown-modal').css("display", "")

        if(PageCountdown.isVersion405)
            $('.ec-modal .coundown-modal').show()
        else
            $('#ec-modal-countdown').prop('checked', true);
    }

    _hideMode() {
        $('.coundown-modal').css("display", "none")

        if(PageCountdown.isVersion405)
            $('.ec-modal .coundown-modal').hide()
        else
            $('#ec-modal-countdown').prop('checked', false);

        if(this.callBackClose)
            this.callBackClose();
    }
}