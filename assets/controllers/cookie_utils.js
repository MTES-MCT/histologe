export function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

export function getCookie(name) {
    let nameCookie = name + "=";
    let cookies = document.cookie.split(";");
    for (var i = 0; i < cookies.length; i++) {
        let cookie = cookies[i];
        while (cookie.charAt(0) === " ") {
            cookie = cookie.substring(1, cookie.length);
        }
        if (cookie.indexOf(nameCookie) === 0) {
            return cookie.substring(nameCookie.length, cookie.length);
        }
    }
    return null;
}
