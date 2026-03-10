class CopySizeElement {
    isTargetExit=true;
    selectors_view;
    selectors_target;
    constructor(selectors_view, selectors_target) {
        this.selectors_view = selectors_view;
        this.selectors_target = selectors_target;
        this.init();
    }
    init() {
        let seft = this;
        $( document ).ready(() => {
            const ele_view = document.querySelector(seft.selectors_view);
            const ele_target = document.querySelector(seft.selectors_target);
            
            if(!ele_view) {
                return; 
            }

            if(seft.isTargetExit && !ele_target) {
                return;
            }

            const observer = new ResizeObserver(resizeCallback);
            observer.observe(ele_view);
            function resizeCallback(eles) {
                eles.forEach(ele => {
                    seft.onResize(ele.contentRect,ele)
                });
            }
        })
    }
}

class CopySizeHeader extends CopySizeElement {
    constructor() {
        super('.ec-layoutRole__header','.ec-layoutRole__header_cz');
    }
    onResize(rect) {
        let ele_target =  document.querySelector(this.selectors_target)
        let height = parseInt(rect.height);
        ele_target.style.height = '';
        ele_target.style.height = height.toString() + "px";
    }
}
new CopySizeHeader();