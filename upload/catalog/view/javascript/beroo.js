(function() {
 function getCookie(name) {
    var matches = document.cookie.match(new RegExp(
      "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, "\\$1") + "=([^;]*)"
    ))
    return matches ? decodeURIComponent(matches[1]) : undefined
 }

 function setCookie(name, value, props) {
    props = props || {}
    var exp = props.expires
    if (typeof exp == "number" && exp) {
       var d = new Date()
       d.setTime(d.getTime() + exp * 1000 * 86400)
       exp = props.expires = d
    }
    if(exp && exp.toUTCString) { props.expires = exp.toUTCString() }

    value = decodeURIComponent(value)
    var updatedCookie = name + "=" + value
    for(var propName in props){
       updatedCookie += "; " + propName
       var propValue = props[propName]
       if(propValue !== true){ updatedCookie += "=" + propValue }
    }
    document.cookie = updatedCookie
 }

 function parseURL(url) {
    var a =  document.createElement("a");
    a.href = url;
    return {
       source: url,
       protocol: a.protocol.replace(":",""),
       host: a.hostname,
       port: a.port,
       query: a.search,
       params: (function(){
          var ret = {},
             seg = a.search.replace(/^\?/,"").split("&"),
             len = seg.length, i = 0, s;
          for (;i<len;i++) {
             if (!seg[i]) { continue; }
             s = seg[i].split("=");
             ret[s[0]] = s[1];
          }
          return ret;
       })(),
       file: (a.pathname.match(/\/([^\/?#]+)$/i) || [,""])[1],
       hash: a.hash.replace("#",""),
       path: a.pathname.replace(/^([^\/])/,"/$1"),
       relative: (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [,""])[1],
       segments: a.pathname.replace(/^\//,"").split("/")
    };
 }

 function parseGetParams() {
    var $_GET = {};
    var __GET = window.location.search.substring(1).split("&");
    for(var i=0; i<__GET.length; i++) {
       var getVar = __GET[i].split("=");
       $_GET[getVar[0]] = typeof(getVar[1])=="undefined" ? "" : decodeURIComponent(getVar[1]);
    }

    return $_GET;
 }

 function setCookieThisDomain(hostname_arr, i)
 {
    var this_domain_tmp = hostname_arr.slice(i).join(".");
    var cookie_param = {expires: 365, path: "/", domain: "." + this_domain_tmp}
    setCookie("prodex24cur_domain", this_domain_tmp, cookie_param);
    var tmp_m = getCookie("prodex24cur_domain");
    if (typeof(tmp_m) == "undefined")
    {
       i--;
       if (i < 0)
          return;

       setCookieThisDomain(hostname_arr, i);
    }
 }

 var hostname = location.host || location.hostname;
 var hostname_arr = hostname.split(".");
 var len = hostname_arr.length - 2;
 setCookieThisDomain(hostname_arr, len);
 this_domain = getCookie("prodex24cur_domain");
 var cookie_param = {expires: 365, path: "/", domain: "." + this_domain};
 
 var refer = document.referrer || location.referrer;
 
 var get = parseGetParams();
 if (typeof(get.utm_expid) != "undefined")
 {
    //setCookie("prodex24experiment", get.utm_expid, cookie_param);
 }

 var myURL = parseURL(refer);
 myURLhost = myURL.host;

 if (typeof(get.utm_source) != "undefined" || typeof(get.utm_medium) != "undefined" || typeof(get.utm_campaign) != "undefined" || typeof(get.utm_content) != "undefined" || typeof(get.utm_term) != "undefined" || typeof(get.yclid) != "undefined" || typeof(get.gclid) != "undefined")
 {
    setCookie("prodex24source", get.utm_source || "", cookie_param);
    if (typeof(get.utm_source) == "undefined")
    {
       if (typeof(get.gclid) != "undefined")
       {setCookie("prodex24source", "google", cookie_param);}
    }

    setCookie("prodex24medium", get.utm_medium || "", cookie_param);
    if (typeof(get.utm_medium) == "undefined")
    {
       if (typeof(get.yclid) != "undefined")
       {setCookie("prodex24medium", "cpc", cookie_param);}
       if (typeof(get.gclid) != "undefined")
       {setCookie("prodex24medium", "cpc", cookie_param);}
    }
    setCookie("prodex24campaign", get.utm_campaign || "", cookie_param);
    setCookie("prodex24content", get.utm_content || "", cookie_param);
    setCookie("prodex24term", get.utm_term || "", cookie_param);

    if (refer && myURLhost.indexOf(window.location.hostname) == -1 && window.location.hostname.indexOf(myURLhost) == -1)
    {setCookie("prodex24source_full", refer || "", cookie_param);}
 }
 else if (refer)
 {
    if  (myURLhost.indexOf(window.location.hostname) == -1 && window.location.hostname.indexOf(myURLhost) == -1)
    {
       setCookie("prodex24source_full", refer, cookie_param);
       var domain = myURL.host.replace(/^www\./i, "");
       setCookie("prodex24source", domain, cookie_param);

       if (typeof(get.gclid) != "undefined")
       {setCookie("prodex24medium", "cpc", cookie_param);}
       else if (/^(((google|search\.yahoo|yandex|bing)(\.[^.]+)+)|(rambler\.ru)|(ukr\.net)|(mail\.ru))$/i.test(domain))
       {setCookie("prodex24medium", "organic", cookie_param);}
       else
       {setCookie("prodex24medium", "referral", cookie_param);}

       setCookie("prodex24campaign", "", cookie_param);
       setCookie("prodex24content", "", cookie_param);
       setCookie("prodex24term", "", cookie_param);

    }
 }
})();