// let checkElms=(obj)=>{let elm = obj.length>0 ? obj : obj.prevObject;return elm.length>0 ? elm : null}
function empty(elms) {return elms.length<=0}
let btnTexts = []
function turnstileCallback(e) {
    console.log(e)
    for (let btnText of btnTexts) {
        let btn = $(btnText[0])
        btn.text(btnText[1])
        if (!btn.prop("skipDis") && btn.prop("disabled"))
            btn.prop('disabled',false)
    }
    btnTexts=[]
    // let hidden = $("[name=cf-turnstile-response]")
}
const updateForm = ()=>{
    let forms = $(document.forms)
        .filter(":not(:has(div.cf-turnstile),#search_form)")
    if (forms.length<=0) return
    //append script
    let script = document.createElement("script")
    script.src = "https://challenges.cloudflare.com/turnstile/v0/api.js"
    script.async = true
    script.defer = true
    let body = $(document.body)
    if (empty(body.find(script))) $('body').append(script)


    let div = document.createElement("div")
    let attr =(k,v)=>div.setAttribute("data-"+k,v)
    div.className+="cf-turnstile"
    div.style.width="100%"
    attr("response-field-name","<?php echo tokenParamName ?>")
    attr('sitekey',"<?php echo $config[siteKey]?>")
    attr('callback','turnstileCallback')

    for (let form of forms) {
        let self = $(form)
        let btns = self.find("button,[type=submit],[type=button]")
        let child = self.find(".form-group:last")
        if (empty(child))
            child = self.find(".text-right")
        if (empty(child))
            child = self.find("#advanced_reply")
        if (empty(child))
            child = btns
        if (empty(child)) self.append(div)
        else child.before(div)
        for (let b of btns) {
            let btn = $(b)
            btnTexts.push([btn,btn.text()])
            if (btn.disabled) btn.prop('skipDis',true)
            btn.prop("disabled",true)
            btn.text("正在等待Turnstile...")
            btn.show()
        }
        $("#cf-chl-widget-fsubl").css("width",'100%')
    }
}
$(document).ready(()=>{
    updateForm()
})