function showMessage(title,message,url='') {
    if (Notification.permission === 'granted') {
        var notification = new Notification(title, {
            icon: './img/avatar.png',
            body: message,
        });

        notification.onclick = function () {
            window.open(url);
        };

    } else {
        if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(function(result) {
                if ((result=== 'denied') || (result === 'default')) {
                    $('#alertTitle').text(title);
                    $('#alertBody p').text(message);
                    $('#alertModal').modal('show');
                }
            });
        } else {
            $('#alertTitle').text(title);
            $('#alertBody p').text(message);
            $('#alertModal').modal('show');
        }
    }
}