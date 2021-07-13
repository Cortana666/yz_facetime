// 设置房间列表页面信息
var user_name = getUrlQuery("user");
var match = user_name.match(/([a-z]+)(\d+)/);
var user_id = match[2];
var html = '';

var room_token = {
    'room1': '006d08b76fcc31a44d5b3974f6607bd9a65IABQpOlxI06W7vrkocJL3q/4ypCrRwFMHXL0wNlN1hYTtSo6c+QAAAAAEAC4541otVfuYAEAAQC1V+5g',
    'room2': '006d08b76fcc31a44d5b3974f6607bd9a65IAA8WbvFEjKdTv6rjazVXdi7q6LgvBiqPh8mmtuZAHWV0ZBren0AAAAAEAC4541oy1fuYAEAAQDLV+5g',
    'room3': '006d08b76fcc31a44d5b3974f6607bd9a65IAAxSmJ7A6MMXvgpGibQ88GxAg+5ACGEIb2SGmVvimD4tAZbfQoAAAAAEAC4541o11fuYAEAAQDXV+5g'
};

if (match[1] == 'teacher') {
    var user_type = 1;
}
if (match[1] == 'student') {
    var user_type = 2;
}

for (let room_name in room_token) {
    html += '<tr>\
            <td>'+ room_name + '</td>\
            <td>\
                <a href="checkstudent.html?user_id='+ user_id + '&name=' + user_name + '&type=' + user_type + '&channel=' + room_name + '&agora_token=' + room_token[room_name] + '">进入房间</a>\
            </td>\
        </tr>';
}

$('title').html(user_name);
$('tbody').html(html);

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