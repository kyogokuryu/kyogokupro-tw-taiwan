var StoreCommonDefine = {
  PAGE_COUNTDOWN_TIME: "page_countdown_time",
  PAGE_COUNTDOWN_KEY: "page_countdown_key",
  PAGE_COUNTDOWN_RESTRICT_REWARD: "page_countdown_restrict_reward",
  PAGE_COUNTDOWN_TIME_AGAIN: "page_countdown_time_again",
  PAGE_COUNTDOWN_LAST_GOT_REWARD: "page_countdown_last_got_reward",
  PAGE_COUNTDOWN_LAST_NO_LOGIN_FINISH: "page_countdown_last_no_login_finish",
  PAGE_COUNTDOWN_NOT_SHOW_AGAIN: "PAGE_COUNTDOWN_NOT_SHOW_AGAIN",
};

class SessionStorage {
  key;
  constructor(key) {
    if (key) this.key = key;
  }
  set value(value) {
    sessionStorage.setItem(this.key, value);
  }
  get value() {
    return sessionStorage.getItem(this.key);
  }
  removeValue() {
    this.value = "";
  }
}

class LocalStorage {
  key;
  constructor(key) {
    if (key) this.key = key;
  }
  set value(value) {
    localStorage.setItem(this.key, value);
  }
  get value() {
    return localStorage.getItem(this.key);
  }
  removeValue() {
    localStorage.removeItem(this.key);
  }
}

class StorePageCountdown extends SessionStorage {
  set uid(uid) {
    this.key = uid + "_" + this.countdownKey;
  }
}
class StorePageCountdownUserId extends StorePageCountdown {
  countdownKey = StoreCommonDefine.PAGE_COUNTDOWN_USER;
}

class StorePageCountdownTime extends StorePageCountdown {
  countdownKey = StoreCommonDefine.PAGE_COUNTDOWN_TIME;
}

class StorePageCountdownKey extends StorePageCountdown {
  countdownKey = StoreCommonDefine.PAGE_COUNTDOWN_KEY;
}

class StorePageCountdownRestrictReward extends StorePageCountdown {
  countdownKey = StoreCommonDefine.PAGE_COUNTDOWN_RESTRICT_REWARD;
}

class StorePageCountdownTimeAgain extends StorePageCountdown {
  countdownKey = StoreCommonDefine.PAGE_COUNTDOWN_TIME_AGAIN;
}

class StorePageCountdownLastGotReward extends SessionStorage {
  countdownKey = StoreCommonDefine.PAGE_COUNTDOWN_LAST_GOT_REWARD;
}
class StorePageCountdownNoLoginFinish extends SessionStorage {
    key = StoreCommonDefine.PAGE_COUNTDOWN_LAST_NO_LOGIN_FINISH;
}

class StorePageCountdownNotShowAgain extends SessionStorage{
  key = StoreCommonDefine.PAGE_COUNTDOWN_NOT_SHOW_AGAIN;
}


const pageCountdownTime = new StorePageCountdownTime();
const pageCountdownKey = new StorePageCountdownKey();
const pageCountdownRestrictReward = new StorePageCountdownRestrictReward();
const pageCountdownTimeAgain = new StorePageCountdownTimeAgain();
const pageCountdownLastGotReward = new StorePageCountdownLastGotReward();
const pageCountdownNoLoginFinish = new StorePageCountdownNoLoginFinish();
const pageCountdownNotShowAgain = new StorePageCountdownNotShowAgain();
