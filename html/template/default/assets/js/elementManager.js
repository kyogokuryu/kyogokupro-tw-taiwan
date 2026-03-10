class ElementManager {
    elementBox;
    constructor(element) {
        this.elementBox = element
    }
    toggerClass(className, isAdd) {
        this._toggerClass(this.elementBox,className,isAdd);
    }
    _toggerClass(element, className, isAdd) {
        if(isAdd) element.classList.add(className);
        else element.classList.remove(className);
    }
    childToggerClass(childkey, className, isAdd) {
        let child = this.elementBox.querySelector(childkey);
        this._toggerClass(child, className, isAdd);
    }
    setCss(childkey, csskey, value) {
        let child = this.elementBox.querySelector(childkey);
        child.style[csskey] = value;
    }
}

function CreateElementManager(element, className) {
    if(!element)
        return null;

    if (element instanceof jQuery){
        return CreateElementManager(element[0],className)
    }

    return new className(element);
}

class PageCountdownManager extends ElementManager {
    static create(element) {
        return CreateElementManager(element, PageCountdownManager)
    }
    setTime(timeCountdown) {
        this.elementBox.querySelector('#page_countdown_time').innerHTML =  timeCountdown.toString();
    }
    setTimesOnDay(curTimes,maxTimes) {
      //  this.elementBox.querySelector('#page_countdown_times_on_day').innerHTML = curTimes + '/' +maxTimes;
    }
    updateDisplay(){
        this.toggerClass(
            "sticky-top",
            (window.pageYOffset && window.pageYOffset > 75));
    }
}