/**
 * Redirects to orders page,
 * init_show_orders triggers 
 * php script that will once 
 * again redirect to the same 
 * page, but with the user id.
 */
function redirect_orders() {
    let url = new URL('https://pynkiwi.wpcomstaging.com/?page_id=3294');
    url.searchParams.append('init_show_orders', '1');
    window.location.href = url;
}