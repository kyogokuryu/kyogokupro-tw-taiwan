class AjaxRequest {
    _url;
    _type;
    _data=null
    _success;
    _fail;
    constructor(config) {
        this._url = config.url;
        this._type = config.type;
        this._data = config.data;
        this.success = config.success
    }
    set url(url) {
        this._url = url;
    }
    set type(type) {
        this._type = type;
    }
    set data(data) {
        this._data = data;
    }
    setCallBack(success, fail=null) {
        this._success = success
        this._fail = fail
    }
    run() {
        let thiz = this;
        $.ajax({
            url: this._url,
            type: this._type,
            data: this._data,
        }).done(function (data) {
            thiz._success(data);
        }).fail(function (data) {
            if(thiz._fail) thiz._fail(data)
        })
    }
}

class PageCountdownInfoRequest extends AjaxRequest {
    static NO_INFO = 0;
    static COUNTDOWN_TIME = 1;
    static INTERVAL_TIME = 2;
    static NO_LOGIN_FINISH = 3;

    _state = 0
    constructor(config) {
        super(config)
        this.init(config);
    }
    init(config){
        let curDate = new Date();
        let curTime = curDate.getTime();
        let timeStartToDay = curDate.setHours(0,0,0,0);
        if(curTime > (parseInt(pageCountdownNotShowAgain.value)  + 86400000))
            pageCountdownNotShowAgain.value = 0;

        if(pageCountdownNoLoginFinish.value && pageCountdownNoLoginFinish.value > 0){
            this._state = PageCountdownInfoRequest.NO_LOGIN_FINISH;
            return;
        }

        let timeCountdown = config.time;
        let keyCountdown = config.key;
        let timeGotAgain = config.timeGetAgain 
        let lastGot = config.lastGot ?? 0;

        let keyArr = keyCountdown ? keyCountdown.split('_') : []
        let timeOfKey = new Date(`${keyArr[0]}-${keyArr[1]}-${keyArr[2]} 00:00:00`).getTime();

        

        this._state = (timeOfKey < timeStartToDay) ? PageCountdownInfoRequest.NO_INFO
                    : (lastGot + timeGotAgain > curTime) ? PageCountdownInfoRequest.INTERVAL_TIME
                    : (!timeCountdown || timeCountdown <= 0) ? PageCountdownInfoRequest.NO_INFO
                    : PageCountdownInfoRequest.COUNTDOWN_TIME         
    }
    get state() {
        return this._state 
    }
}
class PageCountdownRewardRequest extends AjaxRequest {
}
