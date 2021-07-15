// 获取url参数
function getUrlQuery(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (let i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        if (pair[0] == variable) { return pair[1]; }
    }
    return (false);
}

// json对象转json字符串
function ots(param) {
    return JSON.stringify(param)
}

// json字符串转json对象
function sto(param) {
    return JSON.parse(param)
}