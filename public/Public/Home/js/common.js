//....判断是否为pc
			function IsPC() {
    var userAgentInfo = navigator.userAgent;
    var Agents = ["Android", "iPhone",
                "SymbianOS", "Windows Phone",
                "iPad", "iPod"];
    var flag = true;
    for (var v = 0; v < Agents.length; v++) {
        if (userAgentInfo.indexOf(Agents[v]) > 0) {
            flag = false;
            break;
        }
    }
    return flag;
}
		if(IsPC()){
			document.getElementsByTagName('body')[0].style.maxWidth='447px';
			document.getElementsByTagName('body')[0].style.margin='0 auto';

}