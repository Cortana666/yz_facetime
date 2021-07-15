var heartbeat_time = 30000;
var last_message_time;

// websocket操作
ws = new WebSocket("ws://127.0.0.1:2000");
ws.onopen = function () {
    ws.send(ots({ "send_type": "sLogin", "room_id": localStorage.getItem('room_id'), "type": localStorage.getItem('type'), "user_token": localStorage.getItem('user_token') }));
};

// 心跳检测
setInterval(function () {
    ws.send(ots({ "send_type": "ping" }));
    if (new Date().getTime() - last_message_time > heartbeat_time) {
        alert("您已断开连接，请重新进入房间！");
        clearInterval();
        location.href = "list.html";
    }
}, 10000);

ws.onmessage = function (e) {
    // 更新心跳时间
    last_message_time = new Date().getTime();

    data = sto(e.data);
    console.log(data);

    // if (data.send_type == "login") {
    //     if (data.status == 1) {
    //         face_token = data.data.face_token
    //     } else {
    //         alert(data.message);
    //         location.href = "list.html?user=" + name;
    //     }
    // }

    // if (data.send_type == "list") {
    //     student_list = "";
    //     $("#number").html(data.data.list.length);
    //     for (let i = 0; i < data.data.list.length; i++) {
    //         student_list += '<p>' + data.data.list[i]['name'];
    //         student_list += ' <span id="' + data.data.list[i]['user_id'] + '_status">未连接</span><input type="button" value="邀请" onclick="wsInvite(' + data.data.list[i]['user_id'] + ')"><input type="button" value="查看信息" onclick="wsShowInfo(' + data.data.list[i]['user_id'] + ')">';
    //         student_list += '</p>';
    //     }
    //     $('#group').html(student_list);
    // }

    // if (data.send_type == "status") {
    //     online_number = 0;
    //     for (let i = 0; i < data.data.status.length; i++) {
    //         if (data.data.status[i]['status'] == 1) {
    //             online_number++;
    //             $('#' + data.data.status[i]['user_id'] + '_status').html('已连接');
    //         } else if (data.data.status[i]['status'] == 2) {
    //             online_number++;
    //             $('#' + data.data.status[i]['user_id'] + '_status').html('正在面试');
    //         } else if (data.data.status[i]['status'] == 3) {
    //             online_number++;
    //             $('#' + data.data.status[i]['user_id'] + '_status').html('面试完成');
    //         } else {
    //             $('#' + data.data.status[i]['user_id'] + '_status').html('未连接');
    //         }
    //     }
    //     $('#online').html(online_number);
    // }

    //     if (data.send_type == "kick") {
    //         if (is_start) {
    //             leaveCall();
    //         }
    //         alert(data.message);
    //         location.href = "http://127.0.0.1/yz_facetime/html/" + name + ".html";
    //     }

    //     if (data.send_type == "start") {
    //         alert(data.message);
    //         startCall();
    //     }

    //     if (data.send_type == "end") {
    //         alert(data.message);
    //         leaveCall();
    //     }

    function wsInvite(user_id) {
        ws.send(ots({ "send_type": "invite", "face_token": face_token, "user_id": user_id }));
    }
    function wsHung(user_id) {
        ws.send(ots({ "send_type": "end", "face_token": face_token, "user_id": user_id }));
    }
    function wsFinish() {
        ws.send(ots({ "send_type": "kick", "face_token": face_token, "user_id": user_id }));
    }
}