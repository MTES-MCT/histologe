export const EXPIRATION_DAYS = 395.417; // 395 days ~ 13 month

export function setCookie(name, value, days) {
  let expires = '';
  if (days) {
    const date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    expires = '; expires=' + date.toUTCString();
  }
  const sameSiteFlag = '; SameSite=Lax';
  const secureFlag = location.protocol === 'https:' ? '; Secure' : '';

  document.cookie = name + '=' + value + expires + '; path=/' + sameSiteFlag + secureFlag;
}

export function getCookie(name) {
  const nameCookieWithEqual = name + '=';
  const cookies = document.cookie.split(';').map((cookie) => cookie.trim());
  for (const cookie of cookies) {
    if (cookie.indexOf(nameCookieWithEqual) === 0) {
      return cookie.substring(nameCookieWithEqual.length, cookie.length);
    }
  }
  return null;
}
