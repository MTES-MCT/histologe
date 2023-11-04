export const EXPIRATION_DAYS = 395.417; // 395 days ~ 13 month

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
    let nameCookieWithEqual = name + "=";
    let cookies = document.cookie.split(";").map(cookie => cookie.trim());
    for (let cookie of cookies) {
        if (cookie.indexOf(nameCookieWithEqual) === 0) {
            return cookie.substring(nameCookieWithEqual.length, cookie.length);
        }
    }
    return null;
}
