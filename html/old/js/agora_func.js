var rtc = {
    client: null,
    localAudioTrack: null,
    localVideoTrack: null,
};
var options = {
    appId: "d08b76fcc31a44d5b3974f6607bd9a65",
    channel: localStorage.getItem('room_id'),
    token: room_token[localStorage.getItem('room_id')].token,
};

// 开始网络质量检测
async function agoraNetCheck() {
    // 禁用所有功能
    $(".func").attr('disabled', true);

    setTimeout(async function () {
        // 启用所有功能
        $(".func").attr('disabled', false);

        // 清空状态
        $('#uplink_status').html('');
        $('#downlink_status').html('');

        // 销毁本地音视频轨道。
        localAudioTrack.close();
        localVideoTrack.close();

        // 离开频道。
        await downlinkClient.leave();
        await uplinkClient.leave();
    }, 10 * 1000);

    uplinkClient = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
    downlinkClient = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

    const [localAudioTrack, localVideoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();
    const uplinkClientUid = await uplinkClient.join(options.appId, options.channel, options.token, null);
    const downlinkClientUid = await downlinkClient.join(options.appId, options.channel, options.token, null);

    await uplinkClient.publish([localAudioTrack, localVideoTrack]);
    downlinkClient.on("user-published", async (user, mediaType) => {
        await downlinkClient.subscribe(user, mediaType);
    })

    const networkQuality = [
        '质量未知',
        '质量极好',
        '用户主观感觉和极好差不多，但码率可能略低于极好',
        '用户主观感受有瑕疵但不影响沟通',
        '勉强能沟通但不顺畅',
        '网络质量非常差，基本不能沟通',
        '网络连接断开，完全无法沟通'
    ]

    // 获取上行网络质量
    uplinkClient.on("network-quality", (quality) => {
        console.log("uplink network quality", quality.uplinkNetworkQuality);
        $('#uplink_status').html("上行：" + networkQuality[quality.uplinkNetworkQuality]);
    });

    // 获取下行网络质量
    downlinkClient.on("network-quality", (quality) => {
        console.log("downlink network quality", quality.downlinkNetworkQuality);
        $('#downlink_status').html("下行：" + networkQuality[quality.uplinkNetworkQuality]);
    });

    // 获取上行统计数据
    uplinkVideoStats = uplinkClient.getLocalVideoStats();
    // 获取下行统计数据
    downlinkVideoStats = downlinkClient.getRemoteVideoStats()[uplinkClientUid];

    console.log("uplink video stats", uplinkVideoStats);
    console.log("downlink video stats", downlinkVideoStats);
}

// 获取所有设备
async function agoraGetDevice() {
    // 获取所有音视频设备
    AgoraRTC.getDevices()
        .then(devices => {
            const audioDevices = devices.filter(function (device) {
                return device.kind === "audioinput";
            });
            const videoDevices = devices.filter(function (device) {
                return device.kind === "videoinput";
            });

            for (let index = 0; index < audioDevices.length; index++) {
                $("#microphone_device").append('<option value="' + audioDevices[index]['deviceId'] + '">' + audioDevices[index]['label'] + '</option>')
            }

            for (let index = 0; index < videoDevices.length; index++) {
                $("#camera_device").append('<option value="' + videoDevices[index]['deviceId'] + '">' + videoDevices[index]['label'] + '</option>')
            }

            localStorage.setItem('selectedMicrophoneId', $("#microphone_device").val());
            localStorage.setItem('selectedCameraId', $("#camera_device").val());
        })
}

// 设备切换
$("#microphone_device").change(function () {
    localStorage.setItem('selectedMicrophoneId', $(this).val());
});
$("#camera_device").change(function () {
    localStorage.setItem('selectedCameraId', $(this).val());
});

// 开始设备检测
async function agoraDeviceCheck() {
    // 禁用所有功能
    $(".func").attr('disabled', true);

    AgoraRTC.getDevices()
        .then(devices => {
            return Promise.all([
                AgoraRTC.createCameraVideoTrack({ cameraId: localStorage.getItem('selectedCameraId') }),
                AgoraRTC.createMicrophoneAudioTrack({ microphoneId: localStorage.getItem('selectedMicrophoneId') }),
            ]);
        })
        .then(track => {
            track[0].play("video");
            var interval = setInterval(() => {
                const level = track[1].getVolumeLevel();
                console.log("local stream audio level", level);
                $('#device_status').html("音量：" + level * 100);
            }, 1000);

            setTimeout(async function () {
                // 启用所有功能
                $(".func").attr('disabled', false);

                // 清空状态
                $('#device_status').html("");

                // 销毁本地音视频轨道。
                clearInterval(interval)
                track[0].close();
                track[1].close();
            }, 5 * 1000);
        });
}

// 创建并加入频道
async function agoraStartCall() {
    is_start = true;

    // 创建本地客户端
    rtc.client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

    // 订阅远端用户
    rtc.client.on("user-published", async (user, mediaType) => {
        // 开始订阅远端用户。
        await rtc.client.subscribe(user, mediaType);
        console.log("subscribe success");

        // 表示本次订阅的是视频。
        if (mediaType === "video") {
            // 订阅完成后，从 `user` 中获取远端视频轨道对象。
            const remoteVideoTrack = user.videoTrack;
            // 动态插入一个 DIV 节点作为播放远端视频轨道的容器。
            const playerContainer = document.createElement("div");
            // 给这个 DIV 节点指定一个 ID，这里指定的是远端用户的 UID。
            playerContainer.id = user.uid.toString();
            playerContainer.style.width = "640px";
            playerContainer.style.height = "480px";
            document.getElementById("video_div").append(playerContainer);

            // 订阅完成，播放远端音视频。
            // 传入 DIV 节点，让 SDK 在这个节点下创建相应的播放器播放远端视频。
            remoteVideoTrack.play(playerContainer);
        }

        // 表示本次订阅的是音频。
        if (mediaType === "audio") {
            // 订阅完成后，从 `user` 中获取远端音频轨道对象。
            const remoteAudioTrack = user.audioTrack;
            // 播放音频因为不会有画面，不需要提供 DOM 元素的信息。
            remoteAudioTrack.play();
        }
    });

    // 取消订阅远端用户
    rtc.client.on("user-unpublished", (user, mediaType) => {
        if (mediaType === "video") {
            // 获取刚刚动态创建的 DIV 节点。
            const playerContainer = document.getElementById(user.uid.toString());
            // 销毁这个节点。
            playerContainer.remove();
        }
    });

    // 加入目标频道
    const uid = await rtc.client.join(options.appId, options.channel, options.token, null);

    // 创建并发布本地音视频轨道
    const [localAudioTrack, localVideoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks({ cameraId: localStorage.getItem('selectedCameraId') }, { microphoneId: localStorage.getItem('selectedMicrophoneId') });
    rtc.localAudioTrack = localAudioTrack;
    rtc.localVideoTrack = localVideoTrack;
    rtc.localVideoTrack.play("video");
    $("#microphone_status").html("麦克风开启");
    $("#camera_status").html("摄像头开启");

    await rtc.client.publish([rtc.localAudioTrack, rtc.localVideoTrack]);
}

// 暂时停用启用麦克风采集
async function agoraMicrophoneSet(flag) {
    await rtc.localAudioTrack.setEnabled(flag);
    if (flag) {
        $("#microphone_status").html("麦克风开启");
    } else {
        $("#microphone_status").html("麦克风关闭");
    }
}

// 暂时停用启用摄像头采集
async function agoraCameraSet(flag) {
    await rtc.localVideoTrack.setEnabled(flag);
    if (flag) {
        $("#camera_status").html("摄像头开启");
    } else {
        $("#camera_status").html("摄像头关闭");
    }
}

// 开启屏幕共享
async function agoraStartShare(flag) {

}

// 结束屏幕共享
async function agoraEndShare(flag) {

}

// 离开频道
async function agoraLeaveCall() {
    // 销毁本地音视频轨道。
    rtc.localAudioTrack.close();
    rtc.localVideoTrack.close();

    // 遍历远端用户。
    rtc.client.remoteUsers.forEach(user => {
        // 销毁动态创建的 DIV 节点。
        const playerContainer = document.getElementById(user.uid);
        playerContainer && playerContainer.remove();
    });

    // 清空状态
    $("#microphone_status").html("");
    $("#camera_status").html("");

    // 离开频道。
    await rtc.client.leave();
}